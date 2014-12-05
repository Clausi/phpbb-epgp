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
	'ACL_A_EPGP' => 'Can manage EPGP',
	'ACL_U_EPGP' => 'Can view EPGP',
));
