<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editor
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the editor module
 */
class Editor extends ModuleObject
{
	/**
	 * @brief Default font config
	 */
	public static $default_font_config = array(
		'default_font_family' => 'inherit',
		'default_font_size' => '13px',
		'default_line_height' => '160%',
		'default_paragraph_spacing' => '0',
		'default_word_break' => 'normal',
	);

	/**
	 * @brief Default editor config
	 */
	public static $default_editor_config = array(
		'editor_skin' => 'ckeditor',
		'editor_colorset' => 'moono-lisa',
		'editor_height' => 300,
		'editor_toolbar' => 'default',
		'editor_toolbar_hide' => 'N',
		'mobile_editor_skin' => 'simpleeditor',
		'mobile_editor_colorset' => 'light',
		'mobile_editor_height' => 200,
		'mobile_editor_toolbar' => 'simple',
		'mobile_editor_toolbar_hide' => 'Y',
		'comment_editor_skin' => 'ckeditor',
		'comment_editor_colorset' => 'moono-lisa',
		'comment_editor_height' => 100,
		'comment_editor_toolbar' => 'simple',
		'comment_editor_toolbar_hide' => 'N',
		'mobile_comment_editor_skin' => 'simpleeditor',
		'mobile_comment_editor_colorset' => 'light',
		'mobile_comment_editor_height' => 100,
		'mobile_comment_editor_toolbar' => 'simple',
		'mobile_comment_editor_toolbar_hide' => 'Y',
		'content_font' => '',
		'content_font_size' => '13px',
		'content_line_height' => '160%',
		'content_paragraph_spacing' => '0px',
		'content_word_break' => 'normal',
		'enable_autosave' => 'Y',
		'auto_dark_mode' => 'Y',
		'allow_html' => 'Y',
		'editor_focus' => 'N',
		'autoinsert_types' => array('image' => true, 'audio' => true, 'video' => true),
		'autoinsert_position' => 'paragraph',
		'additional_css' => array(),
		'additional_mobile_css' => array(),
		'additional_plugins' => array(),
		'remove_plugins' => array('liststyle', 'tabletools', 'tableselection', 'contextmenu', 'exportpdf'),
		'timestamp' => 0,
	);

	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// Add the default editor component
		$oEditorController = getAdminController('editor');
		$oEditorController->insertComponent('emoticon', false);
		$oEditorController->insertComponent('image_link', false);
		$oEditorController->insertComponent('image_gallery', false);
		$oEditorController->insertComponent('poll_maker', true);

		// Create a directory to use in the editor module
		FileHandler::makeDir('./files/cache/editor');
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();

		// XEVE-17-030
		if(!$oDB->isColumnExists('editor_autosave', 'certify_key')) return true;
		if(!$oDB->isIndexExists('editor_autosave', 'idx_certify_key')) return true;

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleController = getController('module');

		// XEVE-17-030
		if(!$oDB->isColumnExists('editor_autosave', 'certify_key'))
		{
			$oDB->addColumn('editor_autosave', 'certify_key', 'varchar', 32);
		}
		if(!$oDB->isIndexExists('editor_autosave', 'idx_certify_key'))
		{
			$oDB->addIndex('editor_autosave', 'idx_certify_key', 'certify_key');
		}
	}
}
/* End of file editor.class.php */
/* Location: ./modules/editor/editor.class.php */
