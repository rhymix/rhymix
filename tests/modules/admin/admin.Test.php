<?php
error_reporting(0);

class adminAdminControllerTest extends PHPUnit_Framework_TestCase
{
	private $oAdminAdminController;

	protected function setUp()
	{
		define('__ZBXE__', TRUE);
		define('__XE__', TRUE);
		require_once '../../../config/config.inc.php';
		require_once _XE_PATH_.'classes/file/FileHandler.class.php';
		require_once _XE_PATH_.'classes/context/Context.class.php';
		require_once _XE_PATH_.'modules/admin/admin.class.php';
		require_once _XE_PATH_.'modules/admin/admin.admin.controller.php';

		$logged_info = new stdclass;
		$logged_info->member_srl = 4;
		$logged_info->user_id = 'admin';
		$logged_info->email_address = 'admin@admin.com';
		$logged_info->password = 'c4ca4238a0b923820dcc509a6f75849b';
		$logged_info->email_id = 'admin';
		$logged_info->email_host = 'admin.com';
		$logged_info->user_name = 'admin';
		$logged_info->nick_name = 'admin';
		$logged_info->find_account_question = 1;
		$logged_info->find_account_answer = 'admin@naver.com';
		$logged_info->allow_mailing = 'Y';
		$logged_info->allow_message = 'Y';
		$logged_info->denied = 'N';
		$logged_info->regdate = '20110520142031';
		$logged_info->last_login = '20120905135102';
		$logged_info->change_password_date = '20110520142031';
		$logged_info->is_admin = 'Y';
		$logged_info->list_order = -1;

		$oContext = &Context::getInstance();
		//$oContext->init();
		$oContext->set('is_logged', true);
		$oContext->set('logged_info', $logged_info);
		$oContext->set('is_admin', 'Y');

		$this->oAdminAdminController = &getAdminController('admin');
	}

	public function testInsertLayout()
	{
		$args->layout_srl = 62;
		$args->module = 'board';
		$args->module_skin = 'xe_board';
		$args->site_srl = 0;

		$args->skin_vars->colorset = 'white';
		$args->skin_vars->colorset = 'red';
		$args->skin_vars->default_style = 'gallery';
		$args->skin_vars->display_login_info = 'N';
		$args->skin_vars->display_setup_button = 'N';
		$args->skin_vars->header_title_format = 'h1';
		$args->skin_vars->document_title_format = 'h1';
		$args->skin_vars->display_number = 'Y';
		$args->skin_vars->display_author = 'Y';
		$args->skin_vars->display_regdate = 'Y';
		$args->skin_vars->display_readed_count = 'Y';
		$args->skin_vars->display_voted_count = 'Y';
		$args->skin_vars->display_blamed_count = 'Y';
		$args->skin_vars->display_ip_address = 'Y';
		$args->skin_vars->display_last_update = 'Y';
		$args->skin_vars->display_sign = 'Y';
		$args->skin_vars->duration_new = '24';
		$args->skin_vars->thumbnail_type = 'crop';
		$args->skin_vars->thumbnail_width = '100';
		$args->skin_vars->thumbnail_height = '100';

		$oContext = &Context::getInstance();
		//$oContext->init();
		$oContext->set('layout_srl', $args->layout_srl);
		$oContext->set('module', $args->module);
		$oContext->set('module_skin', $args->module_skin);
		$oContext->set('skin_vars', $args->skin_vars);
		$oContext->set('site_srl', $args->site_srl);
		
		$this->oAdminAdminController->updateDefaultDesignInfo($args);

		$file = _XE_PATH_.'files/site_design/design_0.php';

		$this->assertFileExists($file);

		@include($file);

		$this->assertEquals($designInfo->layout_srl, $args->layout_srl);
		$this->assertEquals($designInfo->module->{$args->module}->skin, $args->module_skin);
	}
}
?>
