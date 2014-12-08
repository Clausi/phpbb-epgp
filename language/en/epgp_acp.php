<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_EPGP_ACTIVE' => 'EPGP active?',
	'ACP_EPGP_SETTING_SAVED' => 'Settings saved',
	'ACP_EPGP_BNETKEY' => 'Battle.net API Key',
	'ACP_EPGP_BNETKEY_EXPLAIN' => 'While uploading a snapshot guildroster is pulled from the battle.net api to deactivate left guild characters. Needs php setting "allow_url_fopen = true". You can get a free key here: <a href="http://dev.battle.net" target="_blank">http://dev.battle.net</a>',
	
	'ACP_EPGP_UPLOAD_SAVED' => 'EPGP log uploaded',
	'ACP_EPGP_UPLOAD_ERROR' => 'EPGP log empty',
	'ACP_EPGP_UPLOAD_JSONERROR' => 'EPGP log is not valid',
	'ACP_EPGP_UPLOAD_SNAPSHOT_EXISTS' => 'This EPGP log already exists',
	'LOG_TEXT' => 'Insert EPGP export here',
	'ACP_EPGP_LOG' => 'EPGP snapshot',
	'ACP_EPGP_NOTE' => 'Optional snapshot note',
	'SNAPSHOT_TIME' => 'Snapshot time',
	'SNAPSHOT_NOTE' => 'Note',
	'ACP_SNAPSHOTS' => 'Snapshots',
	'NOTE_TEXT' => 'Add a optional note for this snapshot',
));
