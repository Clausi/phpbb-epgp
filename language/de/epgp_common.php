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
	'EPGP_STANDINGS' => 'EPGP Tabelle',
	'EPGP_INACTIVE' => 'Kein Zugriff!',
	'EPGP_INVALID_CHARACTER' => 'Ungültige Charakter ID',
	'EPGP_INVALID_SNAPSHOT' => 'Ungültige Snapshot ID',
	
	'NAME' => 'Name',
	'LATEST_ITEMS' => 'Neueste Gegenstände',
	'AS_FROM' => 'Stand',
	'LATEST_SNAPSHOTS' => 'Neueste Snapshots',
	
	'EPGP_SETTINGS' => 'EPGP Einstellungen',
	'DECAY' => 'Verfall',
	'BASE_GP' => 'Basis GP',
	'MIN_EP' => 'Minimum EP',
	'EXTRAS' => 'Ersatzbank',
	
	'EP' => 'EP',
	'GP' => 'GP',
	'PR' => 'PR',
	'BELOW_MINEP' => 'Unter %s Min EP',
	'EPGP_CHARACTER' => 'EPGP Charakter',
	'EPGP_BACK' => 'Zurück zur Übersicht',
));
