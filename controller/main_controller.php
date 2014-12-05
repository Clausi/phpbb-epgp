<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


class main_controller implements main_interface
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;
	protected $auth;
	protected $cp;
	protected $container;
	
	/* @var \phpbb\db\driver\driver_interface */
	protected $db;
	protected $u_action;


	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request, ContainerInterface $container)
	{
		$this->config = $config;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->container = $container;
	}

	
	public function index()
	{
		if($this->config['clausi_epgp_active'] == 0) 
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INACTIVE']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		$this->u_action = $this->helper->route('clausi_epgp_controller');
		
		return $this->helper->render('epgp_index.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function raid($id)
	{
		if($this->config['clausi_epgp_active'] == 0) 
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INACTIVE']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		$this->u_action = $this->helper->route('clausi_epgp_controller');
		
		return $this->helper->render('epgp_raid.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function user($id)
	{
		if($this->config['clausi_epgp_active'] == 0) 
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INACTIVE']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		$this->u_action = $this->helper->route('clausi_epgp_controller');
		
		return $this->helper->render('epgp_user.html', $this->user->lang['EPGP_PAGE']);
	}
	
}
