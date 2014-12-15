<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


class main_controller implements main_interface
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;
	protected $auth;
	protected $cp;
	protected $container;
	
	/* @var \phpbb\db\driver\driver_interface */
	protected $db;
	protected $u_action;
	
	protected $guild;


	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request, ContainerInterface $container)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->db = $db;
		$this->request = $request;
		$this->container = $container;
	}

	
	public function index()
	{
		$this->guild = $this->getGuildById($this->config['clausi_epgp_guild']);
		
		$this->template->assign_vars(array(
			'DECAY' => $this->guild['decay_p'],
			'BASE_GP' => $this->guild['base_gp'],
			'MIN_EP' => $this->guild['min_ep'],
			'EXTRAS' => $this->guild['extras_p'],
		));
		
		$sql_ary = array(
			'SELECT' => 'snap_id, guild_id, snapshot_time, note',
			'FROM' => array(
				$this->container->getParameter('tables.clausi.epgp_snapshots') => 's',
			),
			'WHERE' => 's.guild_id = '.$this->guild['guild_id'].' AND s.deleted = 0',
			'ORDER_BY' => 's.snapshot_time DESC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query_limit($sql, 2);
		
		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$current_snap_id = $row[0]['snap_id'];
		if( ! empty($row[1])) {
			$previous_snap_id = $row[1]['snap_id'];
			$previous_standings = $this->getStandings($previous_snap_id);
			$previous_items = $this->getItems($previous_snap_id);
		}
		else $previous_snap_id = false;
		
		$current_standings = $this->getStandings($current_snap_id);
		$current_items = $this->getItems($current_snap_id);
		$i = 1;
		foreach($current_standings as $standing)
		{
			if($previous_snap_id != false)
			{
				foreach($previous_standings as $previous)
				{
					if($standing['char_id'] == $previous['char_id'])
					{
						$ep_change = $standing['ep'] - $previous['ep'];
						$gp_change = $standing['gp'] - $previous['gp'];
					}
				}
			}
			else 
			{
				$ep_change = '';
				$gp_change = '';
			}
		
			$this->template->assign_block_vars('epgp_standings', array(
				'NO' => $i,
				'NAME' => $this->getCharacterById($standing['char_id'])['name'],
				'EP' => $standing['ep'],
				'EP_CHANGE' => ($ep_change != 0) ? $ep_change : '',
				'GP' => $standing['gp'],
				'GP_CHANGE' => ($gp_change != 0) ? $gp_change : '',
				'PR' => ($standing['gp'] > 0) ? number_format(round($standing['ep'] / $standing['gp'], 3), 3) : 0,
			));
			
			$i++;
		}
		
		if(is_array($current_items))
		{
			$items = $current_items;
		}
		elseif(is_array($previous_items))
		{
			$items = $previous_items;
		}
		else $items = array();
		
		foreach($items as $item)
		{
			$itemstring = explode(':', $item['itemstring']);
			$bonus = '';
			if($itemstring[12] > 0)
			{
				for($i = 13; $i < 13+$itemstring[12]; $i++)
				{
					if($i > 13) $bonus .= ':';
					$bonus .= $itemstring[$i];
				}
			}
			
			$this->template->assign_block_vars('epgp_items', array(
				'GAME_ID' => $item['game_id'],
				'ITEM_BONUS' => $bonus,
				'ITEM_GP' => $item['gp'],
				'LOOTED' => $this->user->format_date($item['looted']),
				'LOOTER' => $this->getCharacterById($item['char_id'])['name'],
			));
		}
		
		// Snapshots
		$sql_ary = array(
			'deleted' => 0,
		);
		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_snapshots') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			ORDER BY snapshot_time DESC
			";
		$result = $this->db->sql_query_limit($sql, 7);

		$latest = true;
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_snapshots', array(
				'ID' => $row['snap_id'],
				'LATEST' => $latest,
				'DATE' => $this->user->format_date($row['snapshot_time']),
				'U_SNAPSHOT' => $this->helper->route('clausi_epgp_controller_snapshot', array('snap_id' => $row['snap_id'])),
			));
			if($latest === true) $latest = false;
		}
		$this->db->sql_freeresult($result);
		
		$this->u_action = $this->helper->route('clausi_epgp_controller');
		return $this->helper->render('epgp_index.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function snapshot($snap_id)
	{
		if( ! is_numeric($snap_id) || $snap_id <= 0 )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_SNAPSHOT']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		// Standings
		$this->guild = $this->getGuildById($this->config['clausi_epgp_guild']);

		$current_snapshot = $this->getSnapshotById($snap_id);
		
		$this->template->assign_vars(array(
			'DECAY' => $this->guild['decay_p'],
			'BASE_GP' => $this->guild['base_gp'],
			'MIN_EP' => $this->guild['min_ep'],
			'EXTRAS' => $this->guild['extras_p'],
			'SNAPSHOT_DATE' => $this->user->format_date($current_snapshot['snapshot_time']),
		));
		
		$sql_ary = array(
			'SELECT' => 'snap_id',
			'FROM' => array(
				$this->container->getParameter('tables.clausi.epgp_snapshots') => 's',
			),
			'WHERE' => 's.snapshot_time < ' . $current_snapshot['snapshot_time'] . ' AND s.deleted = 0',
			'ORDER_BY' => 'snapshot_time DESC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query_limit($sql, 1);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
				
		if( ! empty($row[0])) $previous_snapshot = $this->getSnapshotById($row[0]['snap_id']);
		else $previous_snapshot = false;

		if( $previous_snapshot != false ) {
			$previous_snap_id = $previous_snapshot['snap_id'];
			$previous_standings = $this->getStandings($previous_snap_id);
		}
		else $previous_snap_id = false;
		
		$current_standings = $this->getStandings($snap_id);
		$current_items = $this->getItems($snap_id);
		$i = 1;
		foreach($current_standings as $standing)
		{
			if($previous_snap_id != false)
			{
				foreach($previous_standings as $previous)
				{
					if($standing['char_id'] == $previous['char_id'])
					{
						$ep_change = $standing['ep'] - $previous['ep'];
						$gp_change = $standing['gp'] - $previous['gp'];
					}
				}
			}
			else 
			{
				$ep_change = '';
				$gp_change = '';
			}
		
			$this->template->assign_block_vars('epgp_standings', array(
				'NO' => $i,
				'NAME' => $this->getCharacterById($standing['char_id'])['name'],
				'EP' => $standing['ep'],
				'EP_CHANGE' => ($ep_change != 0) ? $ep_change : '',
				'GP' => $standing['gp'],
				'GP_CHANGE' => ($gp_change != 0) ? $gp_change : '',
				'PR' => ($standing['gp'] > 0) ? number_format(round($standing['ep'] / $standing['gp'], 3), 3) : 0,
			));
			
			$i++;
		}
		
		if(is_array($current_items))
		{
			$items = $current_items;
		}
		// elseif(is_array($previous_items))
		// {
			// $items = $previous_items;
		// }
		else $items = array();
		
		foreach($items as $item)
		{
			$itemstring = explode(':', $item['itemstring']);
			$bonus = '';
			if($itemstring[12] > 0)
			{
				for($i = 13; $i < 13+$itemstring[12]; $i++)
				{
					if($i > 13) $bonus .= ':';
					$bonus .= $itemstring[$i];
				}
			}
			
			$this->template->assign_block_vars('epgp_items', array(
				'GAME_ID' => $item['game_id'],
				'ITEM_BONUS' => $bonus,
				'ITEM_GP' => $item['gp'],
				'LOOTED' => $this->user->format_date($item['looted']),
				'LOOTER' => $this->getCharacterById($item['char_id'])['name'],
			));
		}
		
		// Snapshots
		$sql_ary = array(
			'deleted' => 0,
		);
		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_snapshots') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			ORDER BY snapshot_time DESC
			";
		$result = $this->db->sql_query_limit($sql, 6);

		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_snapshots', array(
				'ID' => $row['snap_id'],
				'CURRENT' => ($row['snap_id'] == $snap_id) ? true : false,
				'DATE' => $this->user->format_date($row['snapshot_time']),
				'U_SNAPSHOT' => $this->helper->route('clausi_epgp_controller_snapshot', array('snap_id' => $row['snap_id'])),
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->u_action = $this->helper->route('clausi_epgp_controller_snapshot');
		return $this->helper->render('epgp_snapshot.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function character($char_id)
	{
		if( ! is_numeric($char_id) || $char_id <= 0 )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_CHARACTER']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		$this->u_action = $this->helper->route('clausi_epgp_controller_character');
		
		return $this->helper->render('epgp_character.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function getGuild($name, $realm, $region)
	{
		$sql_ary = array(
			'name' => $name,
			'realm' => $realm,
			'region' => $region,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_guilds') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	public function getGuildById($guild_id)
	{
		$sql_ary = array(
			'guild_id' => $guild_id,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_guilds') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	public function getCharacter($name, $realm, $region)
	{
		$sql_ary = array(
			'name' => $name,
			'realm' => $realm,
			'region' => $region,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_characters') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	public function getCharacterById($char_id)
	{
		$sql_ary = array(
			'char_id' => $char_id,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_characters') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	public function getSnapshot($guild_id, $snaphot_time)
	{
		$sql_ary = array(
			'guild_id' => $guild_id,
			'snapshot_time' => $snaphot_time,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_snapshots') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	public function getSnapshotById($snap_id)
	{
		$sql_ary = array(
			'snap_id' => $snap_id,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_snapshots') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	private function getStandings($snap_id)
	{
		$sql_ary = array(
			'SELECT' => '*',
			'FROM' => array(
				$this->container->getParameter('tables.clausi.epgp_standings') => 's',
				$this->container->getParameter('tables.clausi.epgp_characters') => 'c',
			),
			'WHERE' => 's.snap_id = '.$snap_id.' AND s.deleted = 0 AND c.char_id = s.char_id',
			'ORDER_BY' => 's.ep / s.gp DESC, c.name ASC',
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row;
		
		return false;
	}
	
	
	private function getItems($snap_id)
	{
		$sql_ary = array(
			'snap_id' => $snap_id,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_items') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . " 
			ORDER BY looted DESC
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row;
		
		return false;
	}

	
	private function var_display($var)
	{
		$error = "<pre>";
		print_r($var);
		$error = "</pre>";
	}
	
}
