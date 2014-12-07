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
	/** string Custom form action */
	protected $auth;
	
	protected $epgp;
	
	protected $guild;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, ContainerInterface $container, \clausi\epgp\controller\main_controller $epgp)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->container = $container;
		$this->epgp = $epgp;
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
			
			$log = htmlspecialchars_decode($this->request->variable('epgp_log', '', true));
			if($log == '') trigger_error($this->user->lang('ACP_EPGP_UPLOAD_ERROR') . adm_back_link($this->u_action));

			$log = json_decode($log);

			if( ! $log || $log == NULL ) 
			{
				switch (json_last_error()) {
					case JSON_ERROR_NONE:
						$error = ' - No errors';
					break;
					case JSON_ERROR_DEPTH:
						$error = ' - Maximum stack depth exceeded';
					break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = ' - Underflow or the modes mismatch';
					break;
					case JSON_ERROR_CTRL_CHAR:
						$error = ' - Unexpected control character found';
					break;
					case JSON_ERROR_SYNTAX:
						$error = ' - Syntax error, malformed JSON';
					break;
					case JSON_ERROR_UTF8:
						$error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
					default:
						$error = ' - Unknown error';
					break;
				}
				trigger_error($this->user->lang('ACP_EPGP_UPLOAD_JSONERROR') . $error . adm_back_link($this->u_action));
			}
			
			$this->guild = $this->epgp->getGuild($log->guild, $log->realm, $log->region);

			if($this->guild === false) $this->createGuild($log->guild, $log->realm, $log->region, $log->min_ep, $log->base_gp, $log->extras_p, $log->decay_p);
			else $this->updateGuild($log->guild, $log->realm, $log->region, $log->min_ep, $log->base_gp, $log->extras_p, $log->decay_p);
			
			$epgp_note = $this->request->variable('epgp_note', '', true);
			$this->setSnapshotNote($epgp_note);

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

	
	private function createGuild($name, $realm, $region, $min_ep, $base_gp, $extras_p, $decay_p)
	{
		$sql_ary = array(
			'name' => strtolower($name),
			'realm' => strtolower($realm),
			'region' => strtolower($region),
			'min_ep' => $min_ep,
			'base_gp' => $base_gp,
			'extras_p' => $extras_p,
			'decay_p' => $decay_p,
			'created' => time(),
			'modified' => time()
		);
		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.epgp_guilds') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		
		$this->guild = $this->epgp->getGuild($name, $realm, $region);
	}
	
	
	private function updateGuild($name, $realm, $region, $min_ep, $base_gp, $extras_p, $decay_p)
	{
		$sql_ary = array(
			'min_ep' => $min_ep,
			'base_gp' => $base_gp,
			'extras_p' => $extras_p,
			'decay_p' => $decay_p,
			'modified' => time()
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.epgp_guilds') . ' SET 
			' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE guild_id = ' . $this->guild['guild_id'];
		$this->db->sql_query($sql);
		
		$this->guild = $this->epgp->getGuild($name, $realm, $region);
	}
	
	
	private function setSnapshotNote($note)
	{
		
	}

	
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
	
	private function var_display($var)
	{
		$error = "<pre>";
		print_r($var);
		$error = "</pre>";
	}
	
}
