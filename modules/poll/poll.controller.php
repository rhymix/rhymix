<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pollController
 * @author NAVER (developers@xpressengine.com)
 * @brief Controller class for poll module
 */
class pollController extends poll
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief after a qeustion is created in the popup window, register the question during the save time
	 */
	function procPollInsert()
	{
		$stop_date = intval(Context::get('stop_date'));
		// mobile input date format can be different
		if($stop_date != Context::get('stop_date'))
		{
			$stop_date = date('Ymd', strtotime(Context::get('stop_date')));
		}
		if($stop_date < date('Ymd'))
		{
			$stop_date = date('YmdHis', $_SERVER['REQUEST_TIME']+60*60*24*30);
		}

		$logged_info = Context::get('logged_info');
		$vars = Context::getRequestVars();

		$args = new stdClass;
		$tmp_args = array();

		unset($vars->_filter);
		unset($vars->error_return_url);
		unset($vars->stop_date);

		foreach($vars as $key => $val)
		{
			if(stripos($key, 'tidx'))
			{
				continue;
			}

			$tmp_arr = explode('_', $key);

			$poll_index = $tmp_arr[1];
			if(!$poll_index)
			{
				continue;
			}

			if(!trim($val))
			{
				continue;
			}

			if($tmp_args[$poll_index] == NULL)
			{
				$tmp_args[$poll_index] = new stdClass;
			}

			if(!is_array($tmp_args[$poll_index]->item))
			{
				$tmp_args[$poll_index]->item = array();
			}

			if($logged_info->is_admin != 'Y')
			{
				$val = htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
			}

			switch($tmp_arr[0])
			{
				case 'title':
					$tmp_args[$poll_index]->title = $val;
					break;
				case 'checkcount':
					$tmp_args[$poll_index]->checkcount = $val;
					break;
				case 'item':
					$tmp_args[$poll_index]->item[] = $val;
					break;
			}
		}

		foreach($tmp_args as $key => $val)
		{
			if(!$val->checkcount)
			{
				$val->checkcount = 1;
			}

			if($val->title && count($val->item))
			{
				$args->poll[] = $val;
			}
		}

		if(!count($args->poll)) throw new Rhymix\Framework\Exception('cmd_null_item');

		$args->stop_date = $stop_date;

		// Configure the variables
		$poll_srl = getNextSequence();
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$oDB = &DB::getInstance();
		$oDB->begin();

		// Register the poll
		$poll_args = new stdClass;
		$poll_args->poll_srl = $poll_srl;
		$poll_args->member_srl = $member_srl;
		$poll_args->list_order = $poll_srl*-1;
		$poll_args->stop_date = $args->stop_date;
		$poll_args->poll_count = 0;
		$poll_args->poll_type = $vars->show_vote + $vars->add_item;

		$output = executeQuery('poll.insertPoll', $poll_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Individual poll registration
		foreach($args->poll as $key => $val)
		{
			$title_args = new stdClass;
			$title_args->poll_srl = $poll_srl;
			$title_args->poll_index_srl = getNextSequence();
			$title_args->title = $val->title;
			$title_args->checkcount = $val->checkcount;
			$title_args->poll_count = 0;
			$title_args->list_order = $title_args->poll_index_srl * -1;
			$title_args->member_srl = $member_srl;
			$title_args->upload_target_srl = $vars->upload_target_srl;
			$output = executeQuery('poll.insertPollTitle', $title_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// Add the individual survey items
			foreach($val->item as $k => $v)
			{
				$item_args = new stdClass;
				$item_args->poll_srl = $poll_srl;
				$item_args->poll_index_srl = $title_args->poll_index_srl;
				$item_args->title = $v;
				$item_args->poll_count = 0;
				$item_args->upload_target_srl = $vars->upload_target_srl;
				$output = executeQuery('poll.insertPollItem', $item_args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();

		$this->add('poll_srl', $poll_srl);
		$this->setMessage('success_registed');
	}

	function procPollInsertItem()
	{
		$poll_srl = (int) Context::get('srl');
		$poll_index_srl = (int) Context::get('index_srl');
		$poll_item_title = Context::get('title');

		if($poll_item_title=='') throw new Rhymix\Framework\Exception('msg_item_title_cannot_empty');

		$logged_info = Context::get('logged_info');
		if(!$logged_info) throw new Rhymix\Framework\Exception('msg_cannot_add_item');

		if(!$poll_srl || !$poll_index_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$args = new stdClass();
		$args->poll_srl = $poll_srl;

		// Get the information related to the survey
		$columnList = array('poll_type');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		if(!$output->data) throw new Rhymix\Framework\Exception('poll_no_poll_or_deleted_poll');
		$type = $output->data->poll_type;

		if(!$this->isAbletoAddItem($type)) throw new Rhymix\Framework\Exception('msg_cannot_add_item');

		if($logged_info->is_admin != 'Y')
		{
			$poll_item_title = htmlspecialchars($poll_item_title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		}

		$oDB = &DB::getInstance();
		$oDB->begin();

		$item_args = new stdClass;
		$item_args->poll_srl = $poll_srl;
		$item_args->poll_index_srl = $poll_index_srl;
		$item_args->title = $poll_item_title;
		$item_args->poll_count = 0;
		$item_args->upload_target_srl = 0;
		$item_args->add_user_srl = $logged_info->member_srl;
		$output = executeQuery('poll.insertPollItem', $item_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		return $output;
	}

	function procPollDeleteItem()
	{
		$poll_srl = (int) Context::get('srl');
		$poll_index_srl = (int) Context::get('index_srl');
		$poll_item_srl = Context::get('item_srl');

		$logged_info = Context::get('logged_info');
		if(!$logged_info)  throw new Rhymix\Framework\Exception('msg_cannot_delete_item');

		if(!$poll_srl || !$poll_index_srl || !$poll_item_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$args = new stdClass();
		$args->poll_srl = $poll_srl;
		$args->poll_index_srl = $poll_index_srl;
		$args->poll_item_srl = $poll_item_srl;

		// Get the information related to the survey
		$columnList = array('add_user_srl','poll_count');
		$output = executeQuery('poll.getPollItem', $args, $columnList);
		$add_user_srl = $output->data->add_user_srl;
		$poll_count = $output->data->poll_count;

		// Get the information related to the survey
		$columnList = array('member_srl');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		if(!$output->data) throw new Rhymix\Framework\Exception('poll_no_poll_or_deleted_poll');
		$poll_member_srl = $output->data->member_srl;

		if($add_user_srl!=$logged_info->member_srl && $poll_member_srl!=$logged_info->member_srl) throw new Rhymix\Framework\Exception('msg_cannot_delete_item');
		if($poll_count>0) throw new Rhymix\Framework\Exception('msg_cannot_delete_item_poll_exist');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$item_args = new stdClass;
		$item_args->poll_srl = $poll_srl;
		$item_args->poll_index_srl = $poll_index_srl;
		$item_args->poll_item_srl = $poll_item_srl;
		$output = executeQuery('poll.deletePollItem', $item_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		return $output;
	}

	/**
	 * @brief Accept the poll
	 */
	function procPoll()
	{
		$poll_srl = Context::get('poll_srl');

		$args = new stdClass();
		$args->poll_srl = $poll_srl;
		// Get the information related to the survey
		$columnList = array('poll_count', 'stop_date','poll_type');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		if(!$output->data) throw new Rhymix\Framework\Exception('poll_no_poll_or_deleted_poll');

		if($output->data->stop_date < date("Ymd")) throw new Rhymix\Framework\Exception('msg_cannot_vote');

		$columnList = array('checkcount');
		$output = executeQuery('poll.getPollTitle', $args, $columnList);
		if(!$output->data) return;

		$poll_srl_indexes = Context::get('poll_srl_indexes');
		$tmp_item_srls = explode(',',$poll_srl_indexes);
		//if(count($tmp_item_srls)-1>(int)$output->data->checkcount) throw new Rhymix\Framework\Exception('msg_exceed_max_select');
		for($i=0;$i<count($tmp_item_srls);$i++)
		{
			$srl = (int)trim($tmp_item_srls[$i]);
			if(!$srl) continue;
			$item_srls[] = $srl;
		}

		// If there is no response item, display an error
		if(!count($item_srls)) throw new Rhymix\Framework\Exception('msg_check_poll_item');
		// Make sure is the poll has already been taken
		$oPollModel = getModel('poll');
		if($oPollModel->isPolled($poll_srl)) throw new Rhymix\Framework\Exception('msg_already_poll');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$args = new stdClass;
		$args->poll_srl = $poll_srl;
		// Update all poll responses related to the post
		$output = executeQuery('poll.updatePoll', $args);
		$output = executeQuery('poll.updatePollTitle', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Record each polls selected items
		$args->poll_item_srl = implode(',',$item_srls);
		$output = executeQuery('poll.updatePollItems', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Log the respondent's information
		$log_args = new stdClass;
		$log_args->poll_srl = $poll_srl;
		$log_args->poll_item = $args->poll_item_srl;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$log_args->member_srl = $member_srl;
		$log_args->ipaddress = \RX_CLIENT_IP;
		$output = executeQuery('poll.insertPollLog', $log_args);

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		//$skin = Context::get('skin');
		//if(!$skin || !is_dir(RX_BASEDIR . 'modules/poll/skins/'.$skin)) $skin = 'default';
		// Get tpl
		//$tpl = $oPollModel->getPollHtml($poll_srl, '', $skin);

		$this->add('poll_srl', $poll_srl);
		$this->add('poll_item_srl',$item_srls);
		//$this->add('tpl',$tpl);
		$this->setMessage('success_poll');

		//$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminConfig');
		//$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Preview the results
	 */
	function procPollViewResult()
	{
		$poll_srl = Context::get('poll_srl');

		$skin = Context::get('skin');
		if(!$skin || !is_dir(RX_BASEDIR . 'modules/poll/skins/'.$skin)) $skin = 'default';

		$oPollModel = getModel('poll');
		$tpl = $oPollModel->getPollResultHtml($poll_srl, $skin);

		$this->add('poll_srl', $poll_srl);
		$this->add('tpl',$tpl);
	}

	/**
	 * @brief poll list
	 */
	function procPollGetList()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;
		$pollSrls = Context::get('poll_srls');
		if($pollSrls) $pollSrlList = explode(',', $pollSrls);

		global $lang;
		if(count($pollSrlList) > 0)
		{
			$oPollAdminModel = getAdminModel('poll');
			$args = new stdClass;
			$args->pollIndexSrlList = $pollSrlList;
			$output = $oPollAdminModel->getPollListWithMember($args);
			$pollList = $output->data;

			if(is_array($pollList))
			{
				foreach($pollList AS $key=>$value)
				{
					if($value->checkcount == 1) $value->checkName = $lang->single_check;
					else $value->checkName = $lang->multi_check;
				}
			}
		}
		else
		{
			$pollList = array();
			$this->setMessage($lang->no_documents);
		}

		$this->add('poll_list', $pollList);
	}

	/**
	 * @brief A poll synchronization trigger when a new post is registered
	 */
	function triggerInsertDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
	}

	/**
	 * @brief A poll synchronization trigger when a new comment is registered
	 */
	function triggerInsertCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
	}

	/**
	 * @brief A poll synchronization trigger when a post is updated
	 */
	function triggerUpdateDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
	}

	/**
	 * @brief A poll synchronization trigger when a comment is updated
	 */
	function triggerUpdateCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
	}

	/**
	 * @brief A poll deletion trigger when a post is removed
	 */
	function triggerDeleteDocumentPoll(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;
		// Get the poll
		$args = new stdClass();
		$args->upload_target_srl = $document_srl;
		$output = executeQuery('poll.getPollByTargetSrl', $args);
		if(!$output->data) return;

		$poll_srl = $output->data->poll_srl;
		if(!$poll_srl) return;

		$args->poll_srl = $poll_srl;

		$output = executeQuery('poll.deletePoll', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollLog', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief A poll deletion trigger when a comment is removed
	 */
	function triggerDeleteCommentPoll(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		if(!$comment_srl) return;
		// Get the poll
		$args = new stdClass();
		$args->upload_target_srl = $comment_srl;
		$output = executeQuery('poll.getPollByTargetSrl', $args);
		if(!$output->data) return;

		$poll_srl = $output->data->poll_srl;
		if(!$poll_srl) return;

		$args->poll_srl = $poll_srl;

		$output = executeQuery('poll.deletePoll', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('poll.deletePollLog', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief As post content's poll is obtained, synchronize the poll using the document number
	 */
	function syncPoll($upload_target_srl, $content)
	{
		$match_cnt = preg_match_all('!<img([^\>]*)poll_srl=(["\']?)([0-9]*)(["\']?)([^\>]*?)\>!is',$content, $matches);
		for($i=0;$i<$match_cnt;$i++)
		{
			$poll_srl = $matches[3][$i];

			$args = new stdClass;
			$args->poll_srl = $poll_srl;
			$output = executeQuery('poll.getPoll', $args);
			$poll = $output->data;

			if($poll->upload_target_srl) continue;

			$args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('poll.updatePollTarget', $args);
			$output = executeQuery('poll.updatePollTitleTarget', $args);
			$output = executeQuery('poll.updatePollItemTarget', $args);
		}
	}
}
/* End of file poll.controller.php */
/* Location: ./modules/poll/poll.controller.php */
