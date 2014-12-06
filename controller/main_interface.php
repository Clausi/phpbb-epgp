<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface main_interface
{
	public function index();
	public function raid($id);
	public function user($id);
	public function getGuild($name, $realm, $region);
}
