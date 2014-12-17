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
	'EPGP' => 'EPGP',
	'EPGP_PAGE' => 'EPGP',
	'EPGP_STANDINGS' => 'EPGP Standings',
	'EPGP_INACTIVE' => 'No access!',
	'EPGP_INVALID_CHARACTER' => 'Invalid character ID',
	'EPGP_INVALID_SNAPSHOT' => 'Invalid snapshot ID',
	
	'NAME' => 'Name',
	'LATEST_ITEMS' => 'Latest Items',
	
	'LATEST_SNAPSHOTS' => 'Latest Snapshots',
	
	'EPGP_SETTINGS' => 'EPGP Settings',
	'DECAY' => 'Decay',
	'BASE_GP' => 'Base GP',
	'MIN_EP' => 'Min EP',
	'EXTRAS' => 'Extras',
	
	'EP' => 'EP',
	'GP' => 'GP',
	'PR' => 'PR',
	'EPGP_CHARACTER' => 'EPGP Character',
	'EPGP_BACK' => 'Back to index',
));
