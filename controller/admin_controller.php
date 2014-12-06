<?php

namespace clausi\epgp\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

class admin_controller implements admin_interface
{
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\request\request */
	protected $request;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\user */
	protected $user;
	/** @var ContainerInterface */
	protected $container;
	/** @var \phpbb\boardrules\operators\rule */
	protected $rule_operator;
	/** string Custom form action */
	protected $auth;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, ContainerInterface $container)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->container = $container;
	}
	
	public function display_options()
	{
		add_form_key('clausi/epgp');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/epgp'))
			{
				trigger_error('FORM_INVALID');
			}

			$this->set_options();
			trigger_error($this->user->lang('ACP_EPGP_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
			'CLAUSI_EPGP_ACTIVE' => $this->config['clausi_epgp_active'],
		));
	}
	
	
	public function display_upload()
	{
		add_form_key('clausi/epgp');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/epgp'))
			{
				trigger_error('FORM_INVALID');
			}
			
			$epgp_log = $this->request->variable('epgp_log', '', true);
			if($epgp_log == '') trigger_error($this->user->lang('ACP_EPGP_UPLOAD_ERROR') . adm_back_link($this->u_action));
			// $epgp_log = '{"guild":"Tenebrae","region":"EU","min_ep":3000,"base_gp":1000,"timestamp":1417853100,"extras_p":100,"realm":"Anetheron"}';
			$this->var_display($epgp_log);

			$test = json_decode($epgp_log);
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					echo ' - No errors';
				break;
				case JSON_ERROR_DEPTH:
					echo ' - Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					echo ' - Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					echo ' - Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					echo ' - Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				default:
					echo ' - Unknown error';
				break;
			}
				 
			// if(! $test || $test == NULL) trigger_error($this->user->lang('ACP_EPGP_UPLOAD_JSONERROR') . adm_back_link($this->u_action));
			
			$this->var_display($test);
			
			$epgp_note = $this->request->variable('epgp_note', '', true);

			trigger_error($this->user->lang('ACP_EPGP_UPLOAD_SAVED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	
	public function display_snapshots()
	{
		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	
	private function set_options()
	{
		$this->config->set('clausi_epgp_active', $this->request->variable('clausi_epgp_active', 0));
	}

	
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
