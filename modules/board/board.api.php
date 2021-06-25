<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardAPI
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module View Action에 대한 API 처리
 **/

class boardAPI extends board {

/* do not use dispBoardContent .
	function dispBoardContent(&$oModule) {
	}
*/

	/**
	 * @brief notice list
	 **/
	function dispBoardNoticeList(&$oModule) {
		 $oModule->add('notice_list',$this->arrangeContentList(Context::get('notice_list')));
	}


	/**
	 * @brief content list
	 **/
	function dispBoardContentList(&$oModule) {
		$api_type = Context::get('api_type');
		$document_list = $this->arrangeContentList(Context::get('document_list'));

		if($api_type =='summary')
		{
			$content_cut_size = Context::get('content_cut_size');
			$content_cut_size = $content_cut_size?$content_cut_size:50;
			foreach($document_list as $k=>$v)
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($v, false);
				$document_list[$k]->content = $oDocument->getSummary($content_cut_size);
				unset($oDocument);
			}
		}

		$oModule->add('document_list',$document_list);
		$oModule->add('page_navigation',Context::get('page_navigation'));
	}


	/**
	 * @brief category list
	 **/
	function dispBoardCategoryList(&$oModule) {
		$oModule->add('category_list',Context::get('category_list'));
	}

	/**
	 * @brief board content view
	 **/
	function dispBoardContentView(&$oModule) {
		$oDocument = Context::get('oDocument');
		$extra_vars = $oDocument->getExtraVars();
		if($oDocument->isGranted())
		{
			$oDocument->add('extra_vars',$this->arrangeExtraVars($extra_vars));
		}
		$oModule->add('oDocument',$this->arrangeContent($oDocument));
	}


	/**
	 * @brief contents file list
	 **/
	function dispBoardContentFileList(&$oModule) {
		$oDocument = Context::get('oDocument');
		if($oDocument->isAccessible())
		{
			$oModule->add('file_list', $this->arrangeFile(Context::get('file_list')));
		}
		else
		{
			$oModule->add('file_list', array());
		}
	}


	/**
	 * @brief tag list
	 **/
	function dispBoardTagList(&$oModule) {
		$oModule->add('tag_list',Context::get('tag_list'));
	}

	/**
	 * @brief comments list
	 **/
	function dispBoardContentCommentList(&$oModule) {
		$oModule->add('comment_list',$this->arrangeComment(Context::get('comment_list')));
	}

	function arrangeContentList($content_list) {
		$output = array();
		if(count($content_list)) {
			foreach($content_list as $key => $val) $output[] = $this->arrangeContent($val);
		}
		return $output;
	}


	function arrangeContent($content) {
		$oBoardView = getView('board');
		$output = new stdClass;
		if($content){
			$output = $content->gets('document_srl','category_srl','member_srl','nick_name','is_notice','lang_code','title','title_bold','title_color','content','tags','readed_count','voted_count','blamed_count','comment_count','uploaded_count','regdate','last_update','extra_vars','status','comment_status','notify_message');

			if(!$oBoardView->grant->view)
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

			if ($t_width && $t_height && $t_type && $content->thumbnailExists($t_width, $t_height, $t_type)) {
				$output->thumbnail_src = $content->getThumbnail($t_width, $t_height, $t_type);
			}
		}
		return $output;
	}

	function arrangeComment($comment_list) {
		$output = array();
		if(count($comment_list) > 0 ) {
			foreach($comment_list as $key => $val){
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
		}
		return $output;
	}


	function arrangeFile($file_list) {
		$output = array();
		if(count($file_list) > 0) {
			foreach($file_list as $key => $val){
				$item = new stdClass;
				$item->download_count = $val->download_count;
				$item->source_filename = $val->source_filename;
				$item->file_size = $val->file_size;
				$item->regdate = $val->regdate;
				$output[] = $item;
			}
		}
		return $output;
	}

	function arrangeExtraVars($list) {
		$output = array();
		if(count($list)) {
			foreach($list as $key => $val){
				$item = new stdClass;
				$item->name = $val->name;
				$item->type = $val->type;
				$item->desc = $val->desc;
				$item->value = $val->value;
				$output[] = $item;
			}
		}
		return $output;
	}
}
