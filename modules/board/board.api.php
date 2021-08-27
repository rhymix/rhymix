<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardAPI
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module View Action에 대한 API 처리
 **/

class boardAPI extends board
{
	/**
	 * @brief notice list
	 **/
	public function dispBoardNoticeList($oModule)
	{
		$oModule->add('notice_list', $this->_arrangeContentList(Context::get('notice_list'), $oModule->grant));
	}

	/**
	 * @brief content list
	 **/
	public function dispBoardContentList($oModule)
	{
		$api_type = Context::get('api_type');
		$document_list = $this->_arrangeContentList(Context::get('document_list'), $oModule->grant);

		if($api_type === 'summary')
		{
			$content_cut_size = Context::get('content_cut_size');
			$content_cut_size = $content_cut_size ?: 50;
			foreach($document_list as $k => $v)
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($v, false);
				$document_list[$k]->content = $oDocument->getSummary($content_cut_size);
				unset($oDocument);
			}
		}

		$oModule->add('document_list' ,$document_list);
		$oModule->add('page_navigation', Context::get('page_navigation'));
	}

	/**
	 * @brief category list
	 **/
	public function dispBoardCategoryList($oModule)
	{
		$oModule->add('category_list', Context::get('category_list'));
	}

	/**
	 * @brief board content view
	 **/
	public function dispBoardContentView($oModule)
	{
		$oDocument = Context::get('oDocument');
		if($oDocument->isGranted())
		{
			$extra_vars = $oDocument->getExtraVars() ?: [];
			$oDocument->add('extra_vars', $this->_arrangeExtraVars($extra_vars));
		}
		$oModule->add('oDocument', $this->_arrangeContent($oDocument, $oModule->grant));
	}

	/**
	 * @brief contents file list
	 **/
	public function dispBoardContentFileList($oModule)
	{
		$oDocument = Context::get('oDocument');
		if($oDocument->isAccessible())
		{
			$oModule->add('file_list', $this->_arrangeFiles(Context::get('file_list') ?: []));
		}
		else
		{
			$oModule->add('file_list', array());
		}
	}

	/**
	 * @brief tag list
	 **/
	public function dispBoardTagList($oModule)
	{
		$oModule->add('tag_list', Context::get('tag_list') ?: []);
	}

	/**
	 * @brief comments list
	 **/
	public function dispBoardContentCommentList($oModule)
	{
		$comment_list = Context::get('comment_list');
		if (!is_array($comment_list))
		{
			$comment_list = [];
		}
		$oModule->add('comment_list', $this->_arrangeComments($comment_list));
	}

	/**
	 * Apply _arrangeContent to a list of documents.
	 * 
	 * @param array $content_list
	 * @param object $grant
	 * @return array
	 */
	protected function _arrangeContentList($content_list, $grant): array
	{
		$output = array();
		foreach($content_list ?: [] as $val)
		{
			$output[] = $this->_arrangeContent($val, $grant);
		}
		return $output;
	}

	/**
	 * Clean up document info so that only some fields are exposed.
	 * 
	 * @param object $content
	 * @param object $grant
	 * @return stdClass
	 */
	protected function _arrangeContent($content, $grant): stdClass
	{
		$output = new stdClass;
		if($content)
		{
			$output = $content->gets('document_srl','category_srl','member_srl','nick_name','is_notice','lang_code','title','title_bold','title_color','content','tags','readed_count','voted_count','blamed_count','comment_count','uploaded_count','regdate','last_update','extra_vars','status','comment_status','notify_message');

			if(!$grant->view)
			{
				unset($output->content);
				unset($output->tags);
				unset($output->extra_vars);
			}
			if(!$content->isAccessible())
			{
				$output->content = Context::getLang('msg_is_secret');
				$output->member_srl = 0;
			}
			if($output->member_srl < 0)
			{
				$output->member_srl = 0;
			}

			$t_width  = Context::get('thumbnail_width');
			$t_height = Context::get('thumbnail_height');
			$t_type   = Context::get('thumbnail_type');

			if ($t_width && $t_height && $t_type && $content->thumbnailExists($t_width, $t_height, $t_type))
			{
				$output->thumbnail_src = $content->getThumbnail($t_width, $t_height, $t_type);
			}
		}
		return $output;
	}

	/**
	 * Clean up comment info so that only some fields are exposed.
	 * 
	 * @param array $comment_list
	 * @return array
	 */
	protected function _arrangeComments(array $comment_list): array
	{
		$output = array();
		foreach($comment_list ?: [] as $val)
		{
			$item = null;
			$item = $val->gets('comment_srl','parent_srl','document_srl','depth','member_srl','nick_name','content','is_secret','voted_count','blamed_count','uploaded_count','regdate','last_update','notify_message');

			if(!$val->isAccessible())
			{
				$item->content = Context::getLang('msg_is_secret');
				$item->member_srl = 0;
			}
			if($item->member_srl < 0)
			{
				$item->member_srl = 0;
			}
			$output[] = $item;
		}
		return $output;
	}

	/**
	 * Clean up file info so that only some fields are exposed.
	 * 
	 * @param array $file_list
	 * @return array
	 */
	protected function _arrangeFiles(array $file_list): array
	{
		$output = array();
		foreach ($file_list ?: [] as $val)
		{
			$item = new stdClass;
			$item->download_count = $val->download_count;
			$item->source_filename = $val->source_filename;
			$item->file_size = $val->file_size;
			$item->regdate = $val->regdate;
			$output[] = $item;
		}
		return $output;
	}

	/**
	 * Clean up extra vars so that only some fields are exposed.
	 * 
	 * @param array $extra_var_list
	 * @return array
	 */
	protected function _arrangeExtraVars(array $extra_var_list): array
	{
		$output = array();
		foreach ($extra_var_list ?: [] as $val)
		{
			$item = new stdClass;
			$item->name = $val->name;
			$item->type = $val->type;
			$item->desc = $val->desc;
			$item->value = $val->value;
			$output[] = $item;
		}
		return $output;
	}
}
