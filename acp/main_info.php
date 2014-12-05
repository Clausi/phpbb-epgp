<?php

namespace clausi\epgp\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\clausi\epgp\acp\main_module',
			'title'		=> 'ACP_EPGP_TITLE',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings' => array(
					'title' => 'ACP_EPGP_SETTINGS', 
					'auth' => 'ext_clausi/epgp && acl_a_epgp', 
					'cat' => array('ACP_EPGP_TITLE')
				),
				'upload' => array(
					'title' => 'ACP_EPGP_UPLOAD', 
					'auth' => 'ext_clausi/epgp && acl_a_epgp', 
					'cat' => array('ACP_EPGP_UPLOAD')
				),
			),
		);
	}
}
