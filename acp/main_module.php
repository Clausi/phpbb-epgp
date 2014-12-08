<?php

namespace clausi\epgp\acp;

class main_module
{
	public $u_action;

	function main($id, $mode)
	{
		global $phpbb_container, $request, $user;

		$user->add_lang_ext('clausi/epgp', 'epgp_acp');
		$admin_controller = $phpbb_container->get('clausi.epgp.admin.controller');
		$action = $request->variable('action', '');
		$admin_controller->set_page_url($this->u_action);
		
		switch($mode) 
		{
			case 'settings':
				$this->tpl_name = 'epgp_settings';
				$this->page_title = $user->lang('ACP_EPGP_SETTINGS');
				$admin_controller->display_options();
			break;
			
			case 'upload':
				$this->tpl_name = 'epgp_upload';
				$this->page_title = $user->lang('ACP_EPGP_UPLOAD');
				$admin_controller->display_upload();
			break;
			
			case 'snapshots':
				$this->tpl_name = 'epgp_snapshots';
				$this->page_title = $user->lang('ACP_EPGP_SNAPSHOTS');
				switch($action) 
				{
					case 'delete':
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->deleteSnapshot($request->variable('snap_id', 0));
					break;
					default:
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->display_snapshots();
				}
				
			break;
		}
	}
}
