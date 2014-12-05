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
	'ACP_EPGP_UPLOAD_SAVED' => 'EPGP uploaded',
));
