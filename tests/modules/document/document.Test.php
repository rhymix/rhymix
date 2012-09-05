<?php
error_reporting(0);

class documentModelTest extends PHPUnit_Framework_TestCase
{
	public static $oDB;
	private $oDocumentController = null;
	private $oDocumentModel = null;

	protected function setUp()
	{
		define('__ZBXE__', TRUE);
		require_once '../../../config/config.inc.php';
		require_once _XE_PATH_.'files/config/db.config.php';
		require_once _XE_PATH_.'classes/context/Context.class.php';
		require_once _XE_PATH_.'classes/db/DB.class.php';
		require_once _XE_PATH_.'classes/db/DBMysql.class.php';
		require_once _XE_PATH_.'modules/document/document.class.php';
		require_once _XE_PATH_.'modules/document/document.controller.php';
		require_once _XE_PATH_.'modules/document/document.model.php';

		$db_info->master_db['db_database'] = $db_info->master_db['db_database'].'_test';
		if(is_array($db_info->slave_db))
		{
			foreach($db_info->slave_db AS $key=>$slave_db)
			{
				$db_info->slave_db[$key]['db_database'] = $slave_db['db_database'].'_test';
			}
		}

		$logged_info = new stdclass;
		$logged_info->member_srl = 1;
		$logged_info->user_id = 'neosky';
		$logged_info->email_address = 'neosky@naver.com';
		$logged_info->password = '4297f44b13955235245b2497399d7a93';
		$logged_info->email_id = 'neosky';
		$logged_info->email_host = 'naver.com';
		$logged_info->user_name = 'neosky';
		$logged_info->nick_name = 'neosky';
		$logged_info->find_account_question = 1;
		$logged_info->find_account_answer = 'ovclas@naver.com';
		$logged_info->allow_mailing = 'Y';
		$logged_info->allow_message = 'Y';
		$logged_info->denied = 'N';
		$logged_info->regdate = '20110520142031';
		$logged_info->last_login = '20120905135102';
		$logged_info->change_password_date = '20110520142031';
		$logged_info->is_admin = 'N';
		$logged_info->list_order = -1;

		$oContext = &Context::getInstance();
		//$oContext->init();
		$oContext->setDBInfo($db_info);
		$oContext->set('is_logged', true);
		$oContext->set('logged_info', $logged_info);

		self::$oDB = new DBMysql;

		$this->oDocumentController = &getController('document');
		$this->oDocumentModel = &getModel('document');

	}

	public function testInsertDocument()
	{
		$inputObj = new stdclass;
		$inputObj->_filter = 'insert';
		$inputObj->error_return_url = '/1.5.0_admin/index.php?mid=freeboard&act=dispBoardWrite';
		$inputObj->act = 'procBoardInsertDocument';
		$inputObj->mid = 'freeboard';
		$inputObj->content = '<p>content</p>';
		$inputObj->category_srl = 237465;
		$inputObj->title = 'title';
		$inputObj->extra_vars2 = 'extra_vars';
		$inputObj->_saved_doc_message = "자동 저장된 글이 있습니다. 복구하시겠습니까?\n글을 다 쓰신 후 저장하시면 자동 저장 본은 사라집니다.";
		$inputObj->comment_status = 'ALLOW';
		$inputObj->allow_trackback = 'Y';
		$inputObj->status = 'PUBLIC';
		$inputObj->module = 'board';
		$inputObj->module_srl = 57;
		$inputObj->is_notice = 'N';
		$inputObj->commentStatus = 'ALLOW';

		// document insert
		$output = $this->oDocumentController->insertDocument($inputObj);
		$insertedDocumentSrl = $output->get('document_srl');

		// get Document
		$oDocument = $this->oDocumentModel->getDocument($insertedDocumentSrl);

		$this->assertEquals($inputObj->title, $oDocument->get('title'));
		$this->assertEquals($inputObj->content, $oDocument->get('content'));
		$this->assertEquals($inputObj->is_notice, $oDocument->get('is_notice'));
		$this->assertEquals($inputObj->status, $oDocument->get('status'));
		$this->assertEquals($inputObj->comment_status, $oDocument->get('comment_status'));
		$this->assertEquals($insertedDocumentSrl, $oDocument->get('document_srl'));

		// update Document
		$inputObj->title = 'title2';
		$output = $this->oDocumentController->updateDocument($oDocument, $inputObj);
		unset($GLOBALS['XE_DOCUMENT_LIST'][$insertedDocumentSrl]);
		$oUpdatedDocument = $this->oDocumentModel->getDocument($insertedDocumentSrl);

		$this->assertEquals($inputObj->title, $oUpdatedDocument->get('title'));
		$this->assertNotEquals($oDocument->get('title'), $oUpdatedDocument->get('title'));

		//$output = $oDocumentController->updateVotedCount($insertedDocumentSrl);
		//debugPrint($output);

		// delete document
		$output = $this->oDocumentController->deleteDocument($insertedDocumentSrl);
		$this->assertEquals('success', $output->message);

		unset($oDocument, $GLOBALS['XE_DOCUMENT_LIST'][$insertedDocumentSrl]);
		$oDocument = $this->oDocumentModel->getDocument($insertedDocumentSrl);
		$this->assertEmpty($oDocument->document_srl);
	}

	protected function tearDown()
	{
	}
}
?>
