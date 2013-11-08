<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberAPI
 * @author NAVER (developers@xpressengine.com)
 * API Processing of View Action in the member module
 */
class memberAPI extends member
{
	/**
	 * Content List
	 *
	 * @param Object $oModule
	 *
	 * @return void
	 */
	function dispSavedDocumentList(&$oModule)
	{
		$document_list = $this->arrangeContentList(Context::get('document_list'));
		$oModule->add('document_list',$document_list);
		$oModule->add('page_navigation',Context::get('page_navigation'));
	}

	/**
	 * Arrange Contents
	 *
	 * @param array $content_list
	 *
	 * @return array
	 */
	function arrangeContentList($content_list)
	{
		$output = array();
		if(count($content_list))
		{
			foreach($content_list as $key => $val) $output[] = $this->arrangeContent($val);
		}
		return $output;
	}

	/**
	 * Arrange Contents
	 *
	 * @param array $content_list
	 *
	 * @return array
	 */
	function arrangeContent($content)
	{
		$output = null;
		if($content)
		{
			$output= $content->gets('document_srl','category_srl','nick_name','user_id','user_name','title','content','tags','voted_count','blamed_count','comment_count','regdate','last_update','extra_vars','status');
		}
		return $output;
	}
}
/* End of file member.api.php */
/* Location: ./modules/member/member.api.php */
