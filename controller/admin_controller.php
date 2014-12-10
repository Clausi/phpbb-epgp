<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

class admin_controller implements admin_interface
{
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\request\request */
	protected $request;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\user */
	protected $user;
	/** @var ContainerInterface */
	protected $container;
	/** string Custom form action */
	protected $auth;
	
	protected $epgp;
	
	protected $guild;
	protected $log;
	protected $snap_id;
	protected $snapshot;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, ContainerInterface $container, \clausi\epgp\controller\main_controller $epgp)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->container = $container;
		$this->epgp = $epgp;
	}
	
	public function display_options()
	{
		add_form_key('clausi/epgp');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/epgp'))
			{
				trigger_error('FORM_INVALID');
			}

			$this->set_options();
			trigger_error($this->user->lang('ACP_EPGP_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
			'CLAUSI_EPGP_ACTIVE' => $this->config['clausi_epgp_active'],
			'CLAUSI_EPGP_BNETKEY' => $this->config['clausi_epgp_bnetkey'],
		));
	}
	
	
	public function display_upload()
	{
		add_form_key('clausi/epgp');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/epgp'))
			{
				trigger_error('FORM_INVALID');
			}
			
			$log_text = htmlspecialchars_decode($this->request->variable('epgp_log', '', true));
			if($log_text == '') trigger_error($this->user->lang('ACP_EPGP_UPLOAD_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);

			$this->log = json_decode($log_text);

			if( ! $this->log || $this->log == NULL ) 
			{
				switch (json_last_error()) {
					case JSON_ERROR_NONE:
						$error = ' - No errors';
					break;
					case JSON_ERROR_DEPTH:
						$error = ' - Maximum stack depth exceeded';
					break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = ' - Underflow or the modes mismatch';
					break;
					case JSON_ERROR_CTRL_CHAR:
						$error = ' - Unexpected control character found';
					break;
					case JSON_ERROR_SYNTAX:
						$error = ' - Syntax error, malformed JSON';
					break;
					case JSON_ERROR_UTF8:
						$error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
					default:
						$error = ' - Unknown error';
					break;
				}
				trigger_error($this->user->lang('ACP_EPGP_UPLOAD_JSONERROR') . $error . adm_back_link($this->u_action),  E_USER_WARNING);
			}
			
			$this->guild = $this->epgp->getGuild($this->log->guild, $this->log->realm, $this->log->region);
			if($this->guild === false) $this->createGuild();
			else $this->updateGuild();

			$this->snapshot = $this->epgp->getSnapshot($this->guild['guild_id'], $this->log->timestamp);
			if($this->snapshot === false) 
			{
				$note = $this->request->variable('epgp_note', '', true);
				$this->createSnapshot($log_text, $note);
			}
			else trigger_error($this->user->lang('ACP_EPGP_UPLOAD_SNAPSHOT_EXISTS') . adm_back_link($this->u_action), E_USER_WARNING);

			$this->cleanCharacters();
			$this->createStandings();
			$this->createItems();

			trigger_error($this->user->lang('ACP_EPGP_UPLOAD_SAVED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	
	public function display_snapshots()
	{
		$sql_ary = array(
			'deleted' => 0,
		);
		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_snapshots') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			ORDER BY snapshot_time DESC
			";
		$result = $this->db->sql_query($sql);

		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_snapshots', array(
				'ID' => $row['snap_id'],
				'DATE' => date('d.m.Y - H:i:s', $row['snapshot_time']),
				'NOTE' => $row['note'],
				'U_DELETE' => $this->u_action . '&amp;snap_id=' . $row['snap_id'] . '&amp;action=delete',
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	
	private function set_options()
	{
		$this->config->set('clausi_epgp_active', $this->request->variable('clausi_epgp_active', 0));
		$this->config->set('clausi_epgp_bnetkey', $this->request->variable('clausi_epgp_bnetkey', ''));
	}

	
	private function createGuild()
	{
		$sql_ary = array(
			'name' => $this->log->guild,
			'realm' => $this->log->realm,
			'region' => $this->log->region,
			'min_ep' => $this->log->min_ep,
			'base_gp' => $this->log->base_gp,
			'extras_p' => $this->log->extras_p,
			'decay_p' => $this->log->decay_p,
			'created' => time(),
			'modified' => time()
		);
		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_guilds') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		
		$this->guild = $this->epgp->getGuild($this->log->guild, $this->log->realm, $this->log->region);
	}


	private function updateGuild()
	{
		$sql_ary = array(
			'min_ep' => $this->log->min_ep,
			'base_gp' => $this->log->base_gp,
			'extras_p' => $this->log->extras_p,
			'decay_p' => $this->log->decay_p,
			'modified' => time()
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.epgp_guilds') . ' SET 
			' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE guild_id = ' . $this->guild['guild_id'];
		$this->db->sql_query($sql);
		
		$this->guild = $this->epgp->getGuild($this->log->guild, $this->log->realm, $this->log->region);
	}
	
	
	private function createSnapshot($log_text, $note = '')
	{
		$sql_ary = array(
			'guild_id' => $this->guild['guild_id'],
			'snapshot_time' => $this->log->timestamp,
			'log' => $log_text,
			'note' => $note,
			'created' => time(),
			'modified' => time()
		);

		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_snapshots') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		
		$this->snap_id = $this->db->sql_nextid();
	}
	
	
	public function deleteSnapshot($snap_id)
	{
		if(!$snap_id || $snap_id == 0) trigger_error('INVALID_ID');
		
		$sql_ary = array(
			'deleted' => time(),
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.epgp_snapshots') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE snap_id = ' . $snap_id;
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.epgp_standings') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE snap_id = ' . $snap_id;
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.epgp_items') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE snap_id = ' . $snap_id;
		$this->db->sql_query($sql);
	}
	
	
	private function createStandings()
	{
		foreach($this->log->roster as $roster)
		{
			$character = explode('-', $roster[0]);
			$char_name = $character[0];
			if( ! empty($character[1]) ) $char_realm = $character[1];
			else $char_realm = $this->guild['realm'];
			
			if( ! $char = $this->epgp->getCharacter($char_name, $char_realm, $this->guild['region']) ) $char = $this->createCharacter($char_name, $char_realm, $this->guild['region']);
			if( $char['deleted'] > 0 ) $char = $this->activateCharacter($char['char_id']);

			$sql_ary = array(
				'char_id' => $char['char_id'],
				'guild_id' => $this->guild['guild_id'],
				'snap_id' => $this->snap_id,
				'ep' => $roster[1],
				'gp' => $roster[2],
				'created' => time(),
				'modified' => time()
			);

			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_standings') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function createItems()
	{
		foreach($this->log->loot as $loot)
		{
			$character = explode('-', $loot[1]);
			$char_name = $character[0];
			if( ! empty($character[1]) ) $char_realm = $character[1];
			else $char_realm = $this->guild['realm'];
			
			if( ! $char = $this->epgp->getCharacter($char_name, $char_realm, $this->guild['region']) ) $char = $this->createCharacter($char_name, $char_realm, $this->guild['region']);
			if( $char['deleted'] > 0 ) $char = $this->activateCharacter($char['char_id']);
			
			$game_id = explode(':', $loot[2]);
			$game_id = $game_id[1];
			
			$sql_ary = array(
				'snap_id' => $this->snap_id,
				'char_id' => $char['char_id'],
				'game_id' => $game_id,
				'itemstring' => $loot[2],
				'looted' => $loot[0],
				'gp' => $loot[3],
				'created' => time(),
				'modified' => time()
			);

			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_items') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function createCharacter($name, $realm, $region)
	{
		$sql_ary = array(
			'guild_id' => $this->guild['guild_id'],
			'name' => $name,
			'realm' => $realm,
			'region' => $region,
			'created' => time(),
			'modified' => time()
		);
		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_characters') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		
		return $this->epgp->getCharacter($name, $realm, $region);
	}
	
	
	public function activateCharacter($char_id)
	{
		$sql_ary = array(
			'deleted' => 0,
		);
		
		$sql = "UPDATE 
			" . $this->container->getParameter('tables.clausi.epgp_characters') . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE char_id = " . $char_id . "";
		$result = $this->db->sql_query($sql);
		
		return $this->epgp->getCharacterById($char_id);
	}
	
	
	public function deactivateCharacter($char_id)
	{
		$sql_ary = array(
			'deleted' => time(),
		);

		$sql = "UPDATE 
			" . $this->container->getParameter('tables.clausi.epgp_characters') . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE char_id = " . $char_id . "";
		$result = $this->db->sql_query($sql);
		
		return $this->epgp->getCharacterById($char_id);
	}
	
	
	private function cleanCharacters()
	{
		$sql_ary = array(
			'guild_id' => $this->guild['guild_id'],
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_characters') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);
		$this->db->sql_freeresult($result);

		while($row = $this->db->sql_fetchrow($result))
		{
			$found = false;
			$char_id = 0;
			foreach($this->log->roster as $roster)
			{
				$character = explode('-', $roster[0]);
				$char_name = $character[0];
				if( ! empty($character[1]) ) $char_realm = $character[1];
				else $char_realm = $this->guild['realm'];
				
				$char_id = $row['char_id'];
				
				if($row['name'] == $char_name && $row['realm'] == $char_realm) 
				{
					$found = true;
					$char_id = 0;
					break;
				}
			}
			
			if( $found == false ) $this->deactivateCharacter($char_id);
		}
	}

	
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
	
	private function var_display($var)
	{
		$error = "<pre>";
		print_r($var);
		$error = "</pre>";
	}
	
}
