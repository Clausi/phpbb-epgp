<?php

namespace clausi\epgp\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\rc5');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('clausi_epgp_active', 0)),
			array('config.add', array('clausi_epgp_bnetkey', '')),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_EPGP_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_EPGP_TITLE',
				array(
					'module_basename' => '\clausi\epgp\acp\main_module',
					'modes' => array('settings', 'upload', 'snapshots'),
				),
			)),
			
			// Add permission
			array('permission.add', array('a_epgp', true)),
			array('permission.add', array('u_epgp', true)),
			// Set permissions
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_epgp')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'a_epgp')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'u_epgp')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'u_epgp')),
		);
	}
	
	// Create epgp tables
	public function update_schema()
	{
		return array(
			'add_tables' => array(
			
				$this->table_prefix . 'epgp_snapshots' => array(
					'COLUMNS' => array(
						'snap_id' => array('UINT', NULL, 'auto_increment'),
						'guild_id' => array('UINT', NULL),
						'snapshot_time' => array('TIMESTAMP', 0),
						'log' => array('MTEXT', NULL),
						'note' => array('TEXT', NULL),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'snap_id',
				),
				
				$this->table_prefix . 'epgp_standings' => array(
					'COLUMNS' => array(
						'epgp_id' => array('UINT', NULL, 'auto_increment'),
						'char_id' => array('UINT', NULL),
						'guild_id' => array('UINT', NULL),
						'snap_id' => array('UINT', NULL),
						'ep' => array('UINT', 0),
						'gp' => array('UINT', 0),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'epgp_id',
				),
				
				$this->table_prefix . 'epgp_items' => array(
					'COLUMNS' => array(
						'item_id' => array('UINT', NULL, 'auto_increment'),
						'snap_id' => array('UINT', NULL),
						'char_id' => array('UINT', NULL),
						'game_id' => array('UINT', NULL),
						'itemstring' => array('VCHAR:100', NULL),
						'gp' => array('UINT', 0),
						'looted' => array('TIMESTAMP', 0),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'item_id',
				),
				
				$this->table_prefix . 'epgp_characters' => array(
					'COLUMNS' => array(
						'char_id' => array('UINT', NULL, 'auto_increment'),
						'guild_id' => array('UINT', NULL),
						'name' => array('VCHAR:100', NULL),
						'realm' => array('VCHAR:50', NULL),
						'region' => array('VCHAR:20', NULL),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'char_id',
				),
				
				$this->table_prefix . 'epgp_guilds' => array(
					'COLUMNS' => array(
						'guild_id' => array('UINT', NULL, 'auto_increment'),
						'name' => array('VCHAR:50', NULL),
						'realm' => array('VCHAR:50', NULL),
						'region' => array('VCHAR:20', NULL),
						'min_ep' => array('UINT', 0),
						'base_gp' => array('UINT', 0),
						'decay_p' => array('TINT:4', 0),
						'extras_p' => array('TINT:4', 0),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'guild_id',
				),
				
			),

		);
	}
	
	// Remove epgp tables
	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'epgp_snapshots',
				$this->table_prefix . 'epgp_standings',
				$this->table_prefix . 'epgp_guilds',
				$this->table_prefix . 'epgp_characters',
				$this->table_prefix . 'epgp_items',
			),
		);
	}

}
