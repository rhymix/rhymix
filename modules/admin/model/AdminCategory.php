<?php
class AdminCategory
{
	var $module;
	var $category;
	var $categoryList;
	var $subCategoryList;
	var $categoryListForSearchWithModule;

	function AdminCategory($module = 'admin')
	{
		$this->module = $module;
		$this->category = 'dashboard';
		$this->_makeCategoryList();
		$this->_makeSubCategoryList();
		$this->_makeCategoryListForSearchWithModule();
	}

	function getCategoryList()
	{
		return $this->categoryList;
	}
	function getSubCategoryList()
	{
		return $this->subCategoryList;
	}
	function getCategory()
	{
		foreach($this->categoryList AS $key=>$value)
		{
			if(in_array($this->module, $this->categoryListForSearchWithModule[$key]))
			{
				$this->category = $key;
				break;
			}
		}
		return $this->category;
	}

	function _makeCategoryList()
	{
		$this->categoryList = array(
			'dashboard'=>getUrl('', 'module', 'admin'),
			'site'=>getUrl(''),
			'user'=>getUrl(''),
			'content'=>getUrl('', 'module', 'admin', 'act', 'dispDocumentAdminList'),
			'theme'=>getUrl(''),
			'extensions'=>getUrl(''),
			'configuration'=>getUrl('')
		);
	}

	function _makeSubCategoryList()
	{
		$this->subCategoryList['dashboard'] = array();
		$this->subCategoryList['site'] = array();
		$this->subCategoryList['user'] = array(
			'userList'=>getUrl(''),
			'setting'=>getUrl(''),
			'point'=>getUrl('')
		);
		$this->subCategoryList['content'] = array(
			'document'=>getUrl('', 'module', 'admin', 'act', 'dispDocumentAdminList'),
			'comment'=>getUrl('', 'module', 'admin', 'act', 'dispCommentAdminList'),
			'trackback'=>getUrl(''),
			'file'=>getUrl(''),
			'poll'=>getUrl(''),
			'dataMigration'=>getUrl('')
		);
		$this->subCategoryList['theme'] = array();
		$this->subCategoryList['extensions'] = array(
			'easyInstaller'=>getUrl(''),
			'installedLayout'=>getUrl(''),
			'installedModule'=>getUrl(''),
			'installedWidget'=>getUrl(''),
			'installedAddon'=>getUrl(''),
			'WYSIWYGEditor'=>getUrl(''),
			'spamFilter'=>getUrl('')
		);
		$this->subCategoryList['configuration'] = array(
			'general'=>getUrl(''),
			'fileUpload'=>getUrl('')
		);
	}

	function _makeCategoryListForSearchWithModule()
	{
		$this->categoryListForSearchWithModule = array(
			'dashboard'=>array('admin'),
			'site'=>array(),
			'user'=>array('member'),
			'content'=>array('document', 'comment', 'trackback', 'file', 'poll', 'importer'),
			'theme'=>array(),
			'extensions'=>array(),
			'configuration'=>array()
		);
	}
}
?>
