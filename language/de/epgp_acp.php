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
	'ACP_EPGP_ACTIVE' => 'EPGP aktiv?',
	'ACP_EPGP_SETTING_SAVED' => 'Einstellungen gespeichert',
	'ACP_EPGP_BNETKEY' => 'Battle.net API Key',
	'ACP_EPGP_BNETKEY_EXPLAIN' => 'While uploading a snapshot guildroster is pulled from the battle.net api to deactivate left guild characters. Needs php setting "allow_url_fopen = true". You can get a free key here: <a href="http://dev.battle.net" target="_blank">http://dev.battle.net</a>',
	'ACP_EPGP_ACTIVE_GUILD' => 'Aktive Gilde',
	
	'ACP_EPGP_UPLOAD_SAVED' => 'EPGP Log hochgeladen',
	'ACP_EPGP_UPLOAD_ERROR' => 'EPGP Log ist leer',
	'ACP_EPGP_UPLOAD_JSONERROR' => 'Kein gültiges EPGP Log',
	'ACP_EPGP_UPLOAD_SNAPSHOT_EXISTS' => 'Dieses EPGP Log existiert bereits.',
	'LOG_TEXT' => 'Füge hier das EPGP Log ein',
	'ACP_EPGP_LOG' => 'EPGP Snapshot',
	'ACP_EPGP_NOTE' => 'Optionale Snapshot Notiz',
	'SNAPSHOT_TIME' => 'Snapshot Zeit',
	'SNAPSHOT_NOTE' => 'Notiz',
	'ACP_SNAPSHOTS' => 'Snapshots',
	'NOTE_TEXT' => 'Füge eine optionale Notiz zu diesem Snapshot hinzu.',
));
