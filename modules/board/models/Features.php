<?php

namespace Rhymix\Modules\Board\Models;

use ModuleModel;

/**
 * The board features model.
 *
 * This is a new data structure that summarizes currently enabled features
 * of a board in a format that is easier to use in skins.
 * It also uses words that are more accurate than old XE variable names,
 * such as 'vote down' instead of 'blame' and 'report' instead of 'declare'.
 */
class Features
{
	/**
	 * Public properties.
	 */
	public $document;
	public $comment;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->document = new \stdClass;
		$this->comment = new \stdClass;
	}

	/**
	 * Get board features from module_srl.
	 *
	 * @param int $module_srl
	 * @param ?int $document_module_srl
	 * @return self
	 */
	public static function fromModuleSrl(int $module_srl, ?int $document_module_srl = null): self
	{
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl) ?: new \stdClass;
		return self::fromModuleInfo($module_info, $document_module_srl);
	}

	/**
	 * Get board features from an already created module info object.
	 *
	 * @param object $module_info
	 * @param ?int $document_module_srl
	 * @return self
	 */
	public static function fromModuleInfo(object $module_info, ?int $document_module_srl = null): self
	{
		if (!$document_module_srl)
		{
			$document_module_srl = $module_info->module_srl;
		}
		$document_config = ModuleModel::getModulePartConfig('document', $document_module_srl);
		$comment_config = ModuleModel::getModulePartConfig('comment', $document_module_srl);
		$features = new self;

		// Document features
		$features->document->vote_up = ($document_config->use_vote_up ?? 'Y') !== 'N';
		$features->document->vote_down = ($document_config->use_vote_down ?? 'Y') !== 'N';
		$features->document->vote_log = ($document_config->use_vote_up ?? 'Y') === 'S' || ($document_config->use_vote_down ?? 'Y') === 'S';
		if (isset($document_config->allow_vote_cancel))
		{
			$features->document->cancel_vote = $document_config->allow_vote_cancel === 'Y';
		}
		else
		{
			$features->document->cancel_vote = ($module_info->cancel_vote ?? 'N') === 'Y';
		}
		if (isset($document_config->allow_vote_non_member))
		{
			$features->document->non_member_vote = $document_config->allow_vote_non_member === 'Y';
		}
		else
		{
			$features->document->non_member_vote = ($module_info->non_login_vote ?? 'N') === 'Y';
		}
		$features->document->report = true;
		if (isset($document_config->allow_declare_cancel))
		{
			$features->document->cancel_report = $document_config->allow_declare_cancel === 'Y';
		}
		else
		{
			$features->document->cancel_report = ($module_info->cancel_vote ?? 'N') === 'Y';
		}
		$features->document->history = ($document_config->use_history ?? 'N') === 'Y';

		// Comment features
		$features->comment->vote_up = ($comment_config->use_vote_up ?? 'Y') !== 'N';
		$features->comment->vote_down = ($comment_config->use_vote_down ?? 'Y') !== 'N';
		$features->comment->vote_log = ($comment_config->use_vote_up ?? 'Y') === 'S' || ($comment_config->use_vote_down ?? 'Y') === 'S';
		if (isset($comment_config->allow_vote_cancel))
		{
			$features->comment->cancel_vote = $comment_config->allow_vote_cancel === 'Y';
		}
		else
		{
			$features->comment->cancel_vote = ($module_info->cancel_vote ?? 'N') === 'Y';
		}
		if (isset($comment_config->allow_vote_non_member))
		{
			$features->comment->non_member_vote = $comment_config->allow_vote_non_member === 'Y';
		}
		else
		{
			$features->comment->non_member_vote = ($module_info->non_login_vote ?? 'N') === 'Y';
		}
		$features->comment->report = true;
		if (isset($comment_config->allow_declare_cancel))
		{
			$features->comment->cancel_report = $comment_config->allow_declare_cancel === 'Y';
		}
		else
		{
			$features->comment->cancel_report = ($module_info->cancel_vote ?? 'N') === 'Y';
		}
		$features->comment->max_thread_depth = $comment_config->max_thread_depth ?? 0;
		$features->comment->default_page = $comment_config->default_page ?? 'last';

		return $features;
	}
}
