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
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->db = $db;
		$this->request = $request;
		$this->container = $container;
	}

	
	public function index()
	{
		$this->u_action = $this->helper->route('clausi_epgp_controller');
		
		return $this->helper->render('epgp_index.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function snapshot($snap_id)
	{
		if( ! is_numeric($snap_id) || $snap_id <= 0 )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_SNAPSHOT']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		$this->u_action = $this->helper->route('clausi_epgp_controller_snapshot');
		
		return $this->helper->render('epgp_snapshot.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function character($char_id)
	{
		if( ! is_numeric($char_id) || $char_id <= 0 )
		{
			$this->template->assign_var('EPGP_MESSAGE', $this->user->lang['EPGP_INVALID_CHARACTER']);
			return $this->helper->render('epgp_error.html', $this->user->lang['EPGP_PAGE'], 404);
		}
		
		$this->u_action = $this->helper->route('clausi_epgp_controller_character');
		
		return $this->helper->render('epgp_character.html', $this->user->lang['EPGP_PAGE']);
	}
	
	
	public function getGuild($name, $realm, $region)
	{
		$sql_ary = array(
			'name' => $name,
			'region' => $region,
			'realm' => $realm,
		);

		$sql = "SELECT * FROM 
			" . $this->container->getParameter('tables.clausi.epgp_guilds') . "
			WHERE 
				" . $this->db->sql_build_array('SELECT', $sql_ary) . "
			";
		$result = $this->db->sql_query($sql);

		$row = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if( count($row) > 0 ) return $row[0];
		
		return false;
	}

	
	private function var_display($var)
	{
		$error = "<pre>";
		print_r($var);
		$error = "</pre>";
	}
	
}
