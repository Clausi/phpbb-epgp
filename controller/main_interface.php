<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface main_interface
{
	public function snapshot($snap_id);
	public function character($char_id);
	public function getGuild($name, $realm, $region);
}
