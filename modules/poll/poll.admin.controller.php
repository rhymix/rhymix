<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pollAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin controller class of the poll module
 */
class pollAdminController extends poll
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Save the configurations
	 */
	function procPollAdminInsertConfig()
	{
		$config = new stdClass;
		$config->skin = Context::get('skin');
		$config->colorset = Context::get('colorset');

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('poll', $config);

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the polls selected in the administrator's page
	 */
	function procPollAdminDeleteChecked()
	{
		// Display an error no post is selected
		$cart = Context::get('cart');

		if(is_array($cart)) $poll_srl_list = $cart;
		else $poll_srl_list= explode('|@|', $cart);

		$poll_count = count($poll_srl_list);
		if(!$poll_count) return $this->stop('msg_cart_is_null');
		// Delete the post
		for($i=0;$i<$poll_count;$i++)
		{
			$poll_index_srl = trim($poll_srl_list[$i]);
			if(!$poll_index_srl) continue;

			$output = $this->deletePollTitle($poll_index_srl, true);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage( sprintf(Context::getLang('msg_checked_poll_is_deleted'), $poll_count) );

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminList');
		$this->setRedirectUrl($returnUrl);
	}

	function procPollAdminAddCart()
	{
		$poll_index_srl = (int)Context::get('poll_index_srl');

		$oPollAdminModel = getAdminModel('poll');
		//$columnList = array('comment_srl');
		$args = new stdClass;
		$args->pollIndexSrlList = array($poll_index_srl);
		$args->list_count = 100;

		$output = $oPollAdminModel->getPollList($args);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$value)
			{
				if($_SESSION['poll_management'][$value->poll_index_srl]) unset($_SESSION['poll_management'][$value->poll_index_srl]);
				else $_SESSION['poll_management'][$value->poll_index_srl] = true;
			}
		}
	}

	/**
	 * @brief Delete the poll (when several questions are registered in one poll, delete this question)
	 */
	function deletePollTitle($poll_index_srl) 
	{
		$args = new stdClass;
		$dargs = new stdClass;

		$args->poll_index_srl = $poll_index_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = executeQueryArray('poll.getPollByDeletePollTitle', $args);
		if($output->toBool() && $output->data && $output->data[0]->count == 1)
		{
			$dargs->poll_srl = $output->data[0]->poll_srl;
		}

		$output = $oDB->executeQuery('poll.deletePollTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('poll.deletePollItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		if($dargs->poll_srl)
		{
			$output = executeQuery('poll.deletePoll', $dargs);
			if(!$output)
			{
				$oDB->rollback();
				return $output;
			}

			$output = executeQuery('poll.deletePollLog', $dargs);
			if(!$output)
			{
				$oDB->rollback();
				return $output;
			}
		}
		$oDB->commit();

		return new Object();
	}

	/**
	 * @brief Delete the poll (delete the entire poll)
	 */
	function deletePoll($poll_srl)
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = $oDB->executeQuery('poll.deletePoll', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('poll.deletePollTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('poll.deletePollItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		return new Object();
	}
}
/* End of file poll.admin.controller.php */
/* Location: ./modules/poll/poll.admin.controller.php */
