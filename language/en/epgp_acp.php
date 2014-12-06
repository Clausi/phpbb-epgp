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
	'ACP_EPGP_UPLOAD_SAVED' => 'EPGP log uploaded',
	'ACP_EPGP_UPLOAD_ERROR' => 'EPGP log empty',
	'ACP_EPGP_UPLOAD_JSONERROR' => 'EPGP Log is not valid',
	
	'LOG_TEXT' => 'Insert EPGP export here',
	'ACP_EPGP_LOG' => 'EPGP snapshot',
	
	'ACP_EPGP_NOTE' => 'Optional snapshot note',
	'NOTE_TEXT' => 'Add a optional note for this snapshot',
));
