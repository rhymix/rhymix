<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pollModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The model class for the poll modules
 */
class pollModel extends poll
{
	/**
	 * @brief Initialization
	 */
	public function init()
	{
	}

	/**
	 * @brief returns poll infomation
	 */
	public function _getPollinfo($poll_srl)
	{
		$args = new stdClass;
		$args->poll_srl = intval($poll_srl);
		$logged_info = Context::get('logged_info');

		if(!$args->poll_srl || $args->poll_srl === 0) return $this->setError("poll_no_poll_srl");

		// Get the information related to the survey
		$columnList = array('poll_count', 'stop_date','poll_type','member_srl');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		$poll_member_srl = $output->data->member_srl;
		if(!$output->data) return $this->setError("poll_no_poll_or_deleted_poll");

		$poll = new stdClass;

		// if a person can vote is_polled=0, else 1
		$poll->is_polled = 0;
		if($output->data->stop_date < date("Ymd")) $poll->is_polled = 1;
		elseif($this->isPolled($poll_srl)) $poll->is_polled = 1;

		$poll->poll_count = (int)$output->data->poll_count;
		$poll->poll_type = (int)$output->data->poll_type;
		$poll->stop_date = zdate($output->data->stop_date, "Y-m-d");
		$columnList = array('poll_index_srl', 'title', 'checkcount', 'poll_count');
		$output = executeQueryArray('poll.getPollTitle', $args, $columnList);
		if(!$output->data) return;

		$poll->poll = array();
		foreach($output->data as $key => $val)
		{
			$poll->poll[$val->poll_index_srl] = new stdClass;
			$poll->poll[$val->poll_index_srl]->poll_index_srl = $val->poll_index_srl;
			$poll->poll[$val->poll_index_srl]->title = $val->title;
			$poll->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
			$poll->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
		}

		$output = executeQueryArray('poll.getPollItem', $args);
		foreach($output->data as $key => $val)
		{
			unset($val->upload_target_srl);
			unset($val->poll_srl);
			$val->my_item = false;
			if(($val->add_user_srl==$logged_info->member_srl || $poll_member_srl == $logged_info->member_srl) && $val->add_user_srl!=0) $val->my_item = true;
			$poll->poll[$val->poll_index_srl]->item[] = $val;

		}

		$output = new stdClass;

		$poll->poll_srl = $poll_srl;
		$output->caniadditem = $this->isAbletoAddItem($poll->poll_type) && !!$logged_info->member_srl;

		$output->poll = $poll;

		return $output;
	}

	/**
	 * @brief returns poll infomation
	 */
	public function getPollinfo()
	{
		$output = $this->_getPollinfo(Context::get('poll_srl'));

		$this->add('poll', $output->poll);
		$this->add('caniadditem', $output->caniadditem);
	}

	/**
	 * @brief returns poll item infomation
	 */
	public function getPollitemInfo()
	{
		$args = new stdClass;
		$poll_srl = Context::get('poll_srl');
		$poll_item = Context::get('poll_item');

		if(!$poll_srl || $poll_srl=='') return $this->setError("poll_no_poll_srl");

		$args->poll_srl = $poll_srl;
		$args->poll_item_srl = $poll_item;

		// Get the information related to the survey
		$columnList = array('poll_type');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		if(!$output->data) return $this->setError("poll_no_poll_or_deleted_poll");
		$type = $output->data->poll_type;

		$poll = new stdClass();

		if($this->checkMemberInfo($type))
		{
			$pollvar = new stdClass;
			$pollvar->poll_srl = $poll_srl;
			$pollvar->poll_item = $poll_item;
			$pollvar->poll_item_srl = $poll_item;
			$pollvar->page = !!Context::get('page') ? Context::get('page') : 1;
			$pollvar->list_count = !!Context::get('list_count') ? Context::get('list_count') : 5;

			$output = executeQueryArray('poll.getMemberbyPollitem', $pollvar);
			$output_item = executeQuery('poll.getPollItem', $args);
			$poll->title = $output_item->data->title;

			$oMemberModel = getModel('member');
			$poll->member = array();

			$count = 0;

			foreach($output->data as $key=>$value)
			{
				$count++;
				$vars = $oMemberModel->getMemberInfoByMemberSrl($value->member_srl);

				if(!$value->member_srl)
				{
					if(Context::get('logged_info')->is_admin === "Y")
					{
						$ip = md5($value->ip_address);
						$poll->member[$ip] = new stdClass();
						$poll->member[$ip]->member_srl = 0;
						$poll->member[$ip]->nick_name = lang("anonymous") . ' IP: ' . $value->ip_address;
						$poll->member[$ip]->profile_image = "";
					}
					else
					{
						$ip = md5($value->ip_address);
						$poll->member[$ip] = new stdClass();
						$poll->member[$ip]->member_srl = 0;
						$poll->member[$ip]->nick_name = lang("anonymous");
						$poll->member[$ip]->profile_image = "";
					}
				}
				else
				{
					$poll->member[$vars->member_srl] = new stdClass();
					$poll->member[$vars->member_srl]->member_srl = $vars->member_srl;
					$poll->member[$vars->member_srl]->nick_name = $vars->nick_name;
					if($vars->profile_image->file!='') $poll->member[$vars->member_srl]->profile_image = $vars->profile_image->file;
					else $poll->member[$vars->member_srl]->profile_image = "";
				}
			}

			$poll->count = $count;
		}

		$this->add('item', $poll);
		$this->add("dummy_profile","data:image/gif;base64,R0lGODdhFgAWAPQAAMHBwcDAwPr6+t/f3/v7+9HR0fj4+MrKyvz8/PLy8uvr68/Pz+Li4ubm5vT09ODg4M3Nzdra2uzs7MjIyPDw8N3d3dTU1Pb29sLCwv39/cPDw8bGxv7+/sXFxf///8TExCwAAAAAFgAWAAAF2WDXbWRpnuT3iZ3qvjDMxqpGiyqGrbMGfL6dK+ViiTAHRiLROABaH6KxhVlcPByPx1D4RXFG4MbhyXDO28Nug4NhIlhOxpwdBDTQGKARP6MVGBo2KlN7HghZcR4KKzxTHQEVZXRYHgxPjywaBwZ9WAQLg5kqABYGfhwEERhsPI4jghMSfh4UBwGNbS6CAwJyc2YCA4GuLxgMWqhoHg2BMytvlVrTihUBbBs1BwLSyageAhDFGg9xc1nKWQy4UBoUfenAHgnG2+nKcQQQNh0aBYjLzCXLgKXAjhAAOw==");
		$this->add("page",$output->page_navigation);
	}

	/**
	 * @brief returns poll status
	 * @see this function uses isPolled function below
	 */
	public function getPollstatus()
	{
		$poll_srl = Context::get('poll_srl');
		if(!$poll_srl || $poll_srl=='') return $this->setError("poll_no_poll_srl");

		if($this->isPolled($poll_srl)) $is_polled = 1;
		else $is_polled = 0;

		$this->add('is_polled', $is_polled);
	}

	/**
	 * @brief The function examines if the user has already been polled
	 */
	public function isPolled($poll_srl)
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$output = executeQuery('poll.getPollLog', $args);
		if($output->data->count) return true;
		return false;
	}

	/**
	 * @brief Return the HTML data of the survey
	 * Return the result after checking if the poll has responses
	 * @deprecated this function uses poll skin, which will be removed
	 */
	public function getPollHtml($poll_srl, $style = '', $skin = 'default')
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;
		// Get the information related to the survey
		$columnList = array('poll_count', 'stop_date');
		$output = executeQuery('poll.getPoll', $args, $columnList);
		if(!$output->data) return '';

		$poll = new stdClass;
		$poll->style = $style;
		$poll->poll_count = (int)$output->data->poll_count;
		$poll->stop_date = $output->data->stop_date;

		$columnList = array('poll_index_srl', 'title', 'checkcount', 'poll_count');
		$output = executeQuery('poll.getPollTitle', $args, $columnList);
		if(!$output->data) return;
		if(!is_array($output->data)) $output->data = array($output->data);

		$poll->poll = array();
		foreach($output->data as $key => $val)
		{
			$poll->poll[$val->poll_index_srl] = new stdClass;
			$poll->poll[$val->poll_index_srl]->title = $val->title;
			$poll->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
			$poll->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
		}

		$output = executeQuery('poll.getPollItem', $args);
		foreach($output->data as $key => $val)
		{
			$poll->poll[$val->poll_index_srl]->item[] = $val;
		}

		$poll->poll_srl = $poll_srl;
		// Only ongoing poll results
		if($poll->stop_date >= date("Ymd"))
		{
			if($this->isPolled($poll_srl)) $tpl_file = "result";
			else $tpl_file = "form";
		}
		else
		{
			$tpl_file = "result";
		}

		Context::set('poll',$poll);
		Context::set('skin',$skin);
		// The skin for the default configurations, and the colorset configurations
		$tpl_path = sprintf("%sskins/%s/", $this->module_path, $skin);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Return the result's HTML
	 * @deprecated this function uses poll skin, which will be removed
	 */
	public function getPollResultHtml($poll_srl, $skin = 'default')
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;
		// Get the information related to the survey
		$output = executeQuery('poll.getPoll', $args);
		if(!$output->data) return '';

		$poll = new stdClass;
		$poll->style = $skin;
		$poll->poll_count = (int)$output->data->poll_count;
		$poll->stop_date = $output->data->stop_date;

		$columnList = array('poll_index_srl', 'title', 'checkcount', 'poll_count');
		$output = executeQuery('poll.getPollTitle', $args, $columnList);
		if(!$output->data) return;
		if(!is_array($output->data)) $output->data = array($output->data);

		$poll->poll = array();
		foreach($output->data as $key => $val)
		{
			$poll->poll[$val->poll_index_srl] = new stdClass;
			$poll->poll[$val->poll_index_srl]->title = $val->title;
			$poll->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
			$poll->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
		}

		$output = executeQuery('poll.getPollItem', $args);
		foreach($output->data as $key => $val)
		{
			$poll->poll[$val->poll_index_srl]->item[] = $val;
		}

		$poll->poll_srl = $poll_srl;

		$tpl_file = "result";

		Context::set('poll',$poll);
		// The skin for the default configurations, and the colorset configurations
		$tpl_path = sprintf("%sskins/%s/", $this->module_path, $skin);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
	/** [TO REVIEW]
	 * @brief Selected poll - return the colorset of the skin
	 * @deprecated this function uses poll skin, which will be removed
	 */
	public function getPollGetColorsetList()
	{
		$skin = Context::get('skin');

		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

		for($i=0;$i<count($skin_info->colorset);$i++)
		{
			$colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
			$colorset_list[] = $colorset;
		}

		if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
		$this->add('colorset_list', $colorsets);
	}
}
/* End of file poll.model.php */
/* Location: ./modules/poll/poll.model.php */
