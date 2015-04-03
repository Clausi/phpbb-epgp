<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


class main_controller implements main_interface
{
	protected $config;
	protected $helper;
	protected $template;
	protected $user;
	protected $auth;
	protected $cp;
	protected $container;
	protected $db;
	protected $u_action;
	
	protected $guild;
	
	protected $snapshotsTable;
	protected $guildsTable;
	protected $charactersTable;
	protected $standingsTable;
	protected $itemsTable;
	

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
		
		$this->snapshotsTable = $this->container->getParameter('tables.clausi.epgp_snapshots');
		$this->guildsTable = $this->container->getParameter('tables.clausi.epgp_guilds');
		$this->charactersTable = $this->container->getParameter('tables.clausi.epgp_characters');
		$this->standingsTable = $this->container->getParameter('tables.clausi.epgp_standings');
		$this->itemsTable = $this->container->getParameter('tables.clausi.epgp_items');
		
		$this->guild = $this->getGuildById($this->config['clausi_epgp_guild']);
	}
	
	
	public function snapshot($snap_id = 0)
	{
		if( ! is_numeric($snap_id) || $snap_id < 0 )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_SNAPSHOT']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		if($snap_id === 0) $snap_id = $this->getCurrentSnapId();

		// Standings
		$current_snapshot = $this->getSnapshotById($snap_id);
		
		if( ! $current_snapshot )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_SNAPSHOT']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		$this->template->assign_vars(array(
			'SNAP_ID' => $snap_id,
			'DECAY' => $this->guild['decay_p'],
			'BASE_GP' => $this->guild['base_gp'],
			'MIN_EP' => $this->guild['min_ep'],
			'EXTRAS' => $this->guild['extras_p'],
			'SNAPSHOT_DATE' => date('d.m.Y, H:i', $current_snapshot['snapshot_time']),
			'EPGP_PAGE' => true,
			'BELOW_MINEP' => sprintf($this->user->lang['BELOW_MINEP'], $this->guild['min_ep']),
		));
		
		$sql_ary = array(
			'SELECT' => 'snap_id',
			'FROM' => array(
				$this->snapshotsTable => 's',
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
		if( ! $current_standings )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_SNAPSHOT']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		$current_items = $this->getItems($snap_id);
		$i = 1;
		$below_minep = 0;
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
			
			if($standing['ep'] < $this->guild['min_ep']) $below_minep++;
		
			$this->template->assign_block_vars('epgp_standings', array(
				'NO' => $i,
				'NAME' => $this->getCharacterById($standing['char_id'])['name'],
				'U_CHAR' => $this->helper->route('clausi_epgp_controller_character', array('char_id' => $standing['char_id'])),
				'EP' => $standing['ep'],
				'EP_CHANGE' => ($ep_change != 0) ? $ep_change : '',
				'GP' => $standing['gp'],
				'GP_CHANGE' => ($gp_change != 0) ? $gp_change : '',
				'PR' => $this->calcPR($standing['ep'], $standing['gp']),
				'BELOW_MINEP' => ($below_minep == 1) ? true : false,
			));
			
			$i++;
		}
		
		if(is_array($current_items))
		{
			$items = $current_items;
		}
		else $items = array();
		
		foreach($items as $item)
		{
			$bonus = $this->getBonusString($item['itemstring']);
						
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
			" . $this->snapshotsTable . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			ORDER BY snapshot_time DESC
			";
		$result = $this->db->sql_query_limit($sql, 10);

		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_snapshots', array(
				'ID' => $row['snap_id'],
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
				
		$character = $this->getCharacterById($char_id);
		$charStandings = $this->getStandingsByCharacterId($char_id);
		
		if(is_array($charStandings))
		{
			foreach($charStandings as $standing)
			{
				$snapshot = $this->getSnapshotById($standing['snap_id']);
				$this->template->assign_block_vars('n_standings', array(
					'DATE' => date('d.m.Y', $snapshot['snapshot_time']),
					'TIMESTAMP' => $snapshot['snapshot_time']*1000,
					'EP' => $standing['ep'],
					'GP' => $standing['gp'],
					'PR' => $this->calcPR($standing['ep'], $standing['gp']),
				));
			}
		}
		
		$items = $this->getItemsByCharacterId($char_id);
		
		if( ! is_array($items)) $items = array();
		
		foreach($items as $item)
		{
			$bonus = $this->getBonusString($item['itemstring']);
						
			$this->template->assign_block_vars('epgp_items', array(
				'GAME_ID' => $item['game_id'],
				'ITEM_BONUS' => $bonus,
				'ITEM_GP' => $item['gp'],
				'LOOTED' => $this->user->format_date($item['looted']),
				'LOOTER' => $this->getCharacterById($item['char_id'])['name'],
			));
		}
		
		$this->template->assign_vars(array(
			'CHARNAME' => $character['name'],
			'MIN_EP' => $this->guild['min_ep'],
			'EPGP_CHAR_PAGE' => true,
			'EPGP_PAGE' => true,
		));
		
		$this->u_action = $this->helper->route('clausi_epgp_controller_character');
		
		return $this->helper->render('epgp_character.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	private function getBonusString($itemstring)
	{
		$itemstring = explode(':', $itemstring);
		$bonus = '';
		if($itemstring[12] > 0)
		{
			for($i = 13; $i < 13+$itemstring[12]; $i++)
			{
				if($i > 13) $bonus .= ':';
				$bonus .= $itemstring[$i];
			}
		}
		
		return $bonus;
	}
	
	
	private function calcPR($ep, $gp)
	{
		if( $gp != 0 ) return number_format(round($ep / $gp, 4), 4);
		else return 0;
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
			" . $this->guildsTable . "
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
			" . $this->guildsTable . "
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
			" . $this->charactersTable . "
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
			" . $this->charactersTable . "
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
			" . $this->snapshotsTable . "
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
			" . $this->snapshotsTable . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}
	
	
	private function getCurrentSnapId()
	{
		$sql_ary = array(
			'SELECT' => 'snap_id, guild_id, snapshot_time, note',
			'FROM' => array(
				$this->snapshotsTable => 's',
			),
			'WHERE' => 's.guild_id = '.$this->guild['guild_id'].' AND s.deleted = 0',
			'ORDER_BY' => 's.snapshot_time DESC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query_limit($sql, 1);
		
		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $row[0]['snap_id'];
	}
	
	
	private function getStandings($snap_id)
	{
		// Above min_ep
		$sql_ary_above_minep = array(
			'SELECT' => '*',
			'FROM' => array(
				$this->standingsTable => 's',
				$this->charactersTable => 'c',
			),
			'WHERE' => 's.snap_id = '.$snap_id.' AND s.deleted = 0 AND c.char_id = s.char_id AND s.ep >= '.$this->guild['min_ep'].'',
			'ORDER_BY' => 's.ep / s.gp DESC, c.name ASC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary_above_minep);
		$result = $this->db->sql_query($sql);
		$row_above_minep = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		
		// Below min_ep
		$sql_ary_below_minep = array(
			'SELECT' => '*',
			'FROM' => array(
				$this->standingsTable => 's',
				$this->charactersTable => 'c',
			),
			'WHERE' => 's.snap_id = '.$snap_id.' AND s.deleted = 0 AND c.char_id = s.char_id AND s.ep < '.$this->guild['min_ep'].'',
			'ORDER_BY' => 's.ep / s.gp DESC, s.ep DESC, s.gp ASC, c.name ASC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary_below_minep);
		$result = $this->db->sql_query($sql);
		$row_below_minep = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$row = array_merge((array)$row_above_minep, (array)$row_below_minep);

		if( count($row) > 0 ) return $row;
		
		return false;
	}
	
	
	private function getStandingsByCharacterId($char_id)
	{
		$sql_ary = array(
			'char_id' => $char_id,
			'deleted' => 0,
		);
		$sql = "SELECT * FROM 
			" . $this->standingsTable . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . " 
			ORDER BY created ASC
			";
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
			" . $this->itemsTable . "
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
	
	
	private function getItemsByCharacterId($char_id)
	{
		$sql_ary = array(
			'char_id' => $char_id,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->itemsTable . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . " 
			ORDER BY looted DESC
			";
		$result = $this->db->sql_query_limit($sql, 50);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row;
		
		return false;
	}
	
	
	public function getItem($char_id, $game_id, $looted)
	{
		$sql_ary = array(
			'char_id' => $char_id,
			'game_id' => $game_id,
			'looted' => $looted,
			'deleted' => 0,
		);

		$sql = "SELECT * FROM 
			" . $this->itemsTable . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query_limit($sql, 1);

		$row = $this->db->sql_fetchrow($result);
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
