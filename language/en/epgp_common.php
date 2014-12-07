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
	'EPGP_PAGE' => 'EPGP',
	'EPGP_INACTIVE' => 'No access',
	'EPGP_INVALID_CHARACTER' => 'Invalid character id',
	'EPGP_INVALID_SNAPSHOT' => 'Invalid snapshot id',
));
