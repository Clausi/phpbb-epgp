<?php

namespace clausi\epgp\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'load_language_on_setup',
			'core.page_header'	=> 'add_page_header_link',
			// ACP event
			'core.permissions'	=> 'add_permission',
		);
	}

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;
	
	protected $config;
	
	protected $auth;

	/**
	* Constructor
	*
	* @param \phpbb\controller\helper	$helper		Controller helper object
	* @param \phpbb\template			$template	Template object
	*/
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\config\config $config, \phpbb\auth\auth $auth)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->config = $config;
		$this->auth = $auth;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'clausi/epgp',
			'lang_set' => 'epgp_common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['a_epgp'] = array('lang' => 'ACL_A_EPGP', 'cat' => 'misc');
		$permissions['u_epgp'] = array('lang' => 'ACL_U_EPGP', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'U_EPGP' => ($this->auth->acl_get('u_epgp') || $this->auth->acl_get('a_epgp')),
			'U_EPGP_PAGE'	=> $this->helper->route('clausi_epgp_controller'),
			'S_CLAUSI_EPGP_ACTIVE' => $this->config['clausi_epgp_active'],
		));
	}
}
