<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardWAP
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module WAP class
 **/

class boardWAP extends board
{
	/**
	 * @brief wap procedure method
	 **/
	function procWAP(&$oMobile)
	{
		// check grant
		if(!$this->grant->list || $this->module_info->consultation == 'Y')
		{
			return $oMobile->setContent(lang('msg_not_permitted'));
		}

		// generate document model object
		$oDocumentModel = getModel('document');

		// if the doument is existed
		$document_srl = Context::get('document_srl');
		if($document_srl)
		{
			$oDocument = $oDocumentModel->getDocument($document_srl);
			if($oDocument->isExists())
			{
				// check the grant
				if(!$this->grant->view)
				{
					return $oMobile->setContent(lang('msg_not_permitted'));
				}

				// setup the browser title
				Context::setBrowserTitle($oDocument->getTitleText());

				// if the act is display comment list
				if($this->act=='dispBoardContentCommentList')
				{

					$oCommentModel = getModel('comment');
					$output = $oCommentModel->getCommentList($oDocument->document_srl, 0, false, $oDocument->getCommentCount());

					$content = '';
					if(count($output->data))
					{
						foreach($output->data as $key => $val)
						{
							$oComment = new commentItem();
							$oComment->setAttribute($val);

							if(!$oComment->isAccessible()) continue;

							$content .= "<b>".$oComment->getNickName()."</b> (".$oComment->getRegdate("Y-m-d").")<br>\r\n".$oComment->getContent(false,false)."<br>\r\n";
						}
					}

					// setup mobile contents
					$oMobile->setContent( $content );

					// setup upper URL
					$oMobile->setUpperUrl( getUrl('act',''), lang('cmd_go_upper') );

				// display the document if the act is not display the comment list
				} else {

					// setup contents (strip all html tags)
					$content = strip_tags(str_replace('<p>','<br>&nbsp;&nbsp;&nbsp;',$oDocument->getContent(false,false,false)),'<br><b><i><u><em><small><strong><big>');


					// setup content information(include the comments link)
					$content = lang('replies').' : <a href="'.getUrl('act','dispBoardContentCommentList').'">'.$oDocument->getCommentCount().'</a><br>'."\r\n".$content;
					$content = '<b>'.$oDocument->getNickName().'</b> ('.$oDocument->getRegdate("Y-m-d").")<br>\r\n".$content;

					// setup mobile contents
					$oMobile->setContent( $content );

					// setup upper URL
					$oMobile->setUpperUrl( getUrl('document_srl',''), lang('cmd_list') );

				}

				return;
			}
		}

		// board index
		$args = new stdClass;
		$args->module_srl = $this->module_srl;
		$args->page = Context::get('page');;
		$args->list_count = 9;
		$args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
		$args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';
		$output = $oDocumentModel->getDocumentList($args, $this->except_notice);
		$document_list = $output->data;
		$page_navigation = $output->page_navigation;

		$childs = array();
		if($document_list && count($document_list))
		{
			foreach($document_list as $key => $val)
			{
				$href = getUrl('mid',$_GET['mid'],'document_srl',$val->document_srl);
				$obj = null;
				$obj['href'] = $val->getPermanentUrl();

				$title = htmlspecialchars($val->getTitleText());
				if($val->getCommentCount()) $title .= ' ['.$val->getCommentCount().']';
				$obj['link'] = $obj['text'] = '['.$val->getNickName().'] '.$title;
				$childs[] = $obj;
			}
			$oMobile->setChilds($childs);
		}

		$totalPage = $page_navigation->last_page;
		$page = (int)Context::get('page');
		if(!$page) $page = 1;

		// next/prevUrl specification
		if($page > 1)
		{
			$oMobile->setPrevUrl(getUrl('mid',$_GET['mid'],'page',$page-1), sprintf('%s (%d/%d)', lang('cmd_prev'), $page-1, $totalPage));
		}

		if($page < $totalPage)
		{
			$oMobile->setNextUrl(getUrl('mid',$_GET['mid'],'page',$page+1), sprintf('%s (%d/%d)', lang('cmd_next'), $page+1, $totalPage));
		}

		$oMobile->mobilePage = $page;
		$oMobile->totalPage = $totalPage;
	}
}
