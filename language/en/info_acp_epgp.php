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
	'ACP_EPGP_TITLE' => 'EPGP Modul',
	'ACP_EPGP_SETTINGS' => 'Settings',
	'ACP_EPGP_UPLOAD' => 'Upload',
));
