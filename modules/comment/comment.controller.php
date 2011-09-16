<?php
    /**
     * @class  commentController
     * @author NHN (developers@xpressengine.com)
     * @brief controller class of the comment module 
     **/

    class commentController extends comment {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief action to handle recommendation votes on comments (Up)
         **/
        function procCommentVoteUp() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

			$oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);
			$module_srl = $oComment->get('module_srl');
			if(!$module_srl) return new Object(-1, 'msg_invalid_request');

			$oModuleModel = &getModel('module');
            $comment_config = $oModuleModel->getModulePartConfig('comment',$module_srl);
			if($comment_config->use_vote_up=='N') return new Object(-1, 'msg_invalid_request');

            $point = 1;
            return $this->updateVotedCount($comment_srl, $point);
        }

        /**
         * @brief action to handle recommendation votes on comments (Down)
         **/
        function procCommentVoteDown() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

			$oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);
			$module_srl = $oComment->get('module_srl');
			if(!$module_srl) return new Object(-1, 'msg_invalid_request');

			$oModuleModel = &getModel('module');
            $comment_config = $oModuleModel->getModulePartConfig('comment',$module_srl);
			if($comment_config->use_vote_down=='N') return new Object(-1, 'msg_invalid_request');

            $point = -1;
            return $this->updateVotedCount($comment_srl, $point);
        }

        /**
         * @brief action to be called when a comment posting is reported
         **/
        function procCommentDeclare() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            return $this->declaredComment($comment_srl);
        }

        /**
         * @brief trigger to delete its comments together with document deleted
         **/
        function triggerDeleteDocumentComments(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            return $this->deleteComments($document_srl, true);
        }

        /**
         * @brief trigger to delete corresponding comments when deleting a module
         **/
        function triggerDeleteModuleComments(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            $oCommentController = &getAdminController('comment');
            return $oCommentController->deleteModuleComments($module_srl);
        }

        /**
         * @brief Authorization of the comments
         * available only in the current connection of the session value
         **/
        function addGrant($comment_srl) {
            $_SESSION['own_comment'][$comment_srl] = true;
        }

        /**
         * @brief Enter comments
         **/
        function insertComment($obj, $manual_inserted = false) {
            $obj->__isupdate = false;
            // call a trigger (before)
            $output = ModuleHandler::triggerCall('comment.insertComment', 'before', $obj);
            if(!$output->toBool()) return $output;
            // check if a posting of the corresponding document_srl exists
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object(-1,'msg_invalid_document');
            // get a object of document model
            $oDocumentModel = &getModel('document');

            // even for manual_inserted if password exists, md5 it.
            if($obj->password) $obj->password = md5($obj->password);
            // get the original posting
            if(!$manual_inserted) {
                $oDocument = $oDocumentModel->getDocument($document_srl);

                if($document_srl != $oDocument->document_srl) return new Object(-1,'msg_invalid_document');
                if($oDocument->isLocked()) return new Object(-1,'msg_invalid_request');

                if($obj->homepage &&  !preg_match('/^[a-z]+:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
                // input the member's information if logged-in
                if(Context::get('is_logged')) {
                    $logged_info = Context::get('logged_info');
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_id = $logged_info->user_id;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }
            // error display if neither of log-in info and user name exist.
            if(!$logged_info->member_srl && !$obj->nick_name) return new Object(-1,'msg_invalid_request');

            if(!$obj->comment_srl) $obj->comment_srl = getNextSequence();
            // determine the order
            $obj->list_order = getNextSequence() * -1;
            // remove XE's own tags from the contents
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);
			if(Mobile::isFromMobilePhone())
			{
				$obj->content = nl2br(htmlspecialchars($obj->content));
			}
            if(!$obj->regdate) $obj->regdate = date("YmdHis");
            // remove iframe and script if not a top administrator on the session.
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            if(!$obj->notify_message) $obj->notify_message = 'N';
            if(!$obj->is_secret) $obj->is_secret = 'N';

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();
            // Enter a list of comments first
            $list_args->comment_srl = $obj->comment_srl;
            $list_args->document_srl = $obj->document_srl;
            $list_args->module_srl = $obj->module_srl;
            $list_args->regdate = $obj->regdate;
            // If parent comment doesn't exist, set data directly
            if(!$obj->parent_srl) {
                $list_args->head = $list_args->arrange = $obj->comment_srl;
                $list_args->depth = 0;
            // If parent comment exists, get information of the parent comment
            } else {
                // get information of the parent comment posting
                $parent_args->comment_srl = $obj->parent_srl;
                $parent_output = executeQuery('comment.getCommentListItem', $parent_args);
                // return if no parent comment exists
                if(!$parent_output->toBool() || !$parent_output->data) return;
                $parent = $parent_output->data;

                $list_args->head = $parent->head;
                $list_args->depth = $parent->depth+1;
                // if the depth of comments is less than 2, execute insert.
                if($list_args->depth<2) {
                    $list_args->arrange = $obj->comment_srl;
                // if the depth of comments is greater than 2, execute update.
                } else {
                    // get the top listed comment among those in lower depth and same head with parent's.
                    $p_args->head = $parent->head;
                    $p_args->arrange = $parent->arrange;
                    $p_args->depth = $parent->depth;
                    $output = executeQuery('comment.getCommentParentNextSibling', $p_args);

                    if($output->data->arrange) {
                        $list_args->arrange = $output->data->arrange;
                        $output = executeQuery('comment.updateCommentListArrange', $list_args);
                    } else {
                        $list_args->arrange = $obj->comment_srl;
                    }

                }
            }

            $output = executeQuery('comment.insertCommentList', $list_args);
            if(!$output->toBool()) return $output;
            // insert comment
            $output = executeQuery('comment.insertComment', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // creat the comment model object
            $oCommentModel = &getModel('comment');
            // get the number of all comments in the posting
            $comment_count = $oCommentModel->getCommentCount($document_srl);
            // create the controller object of the document
            $oDocumentController = &getController('document');
            // Update the number of comments in the post
            $output = $oDocumentController->updateCommentCount($document_srl, $comment_count, $obj->nick_name, true);
            // grant autority of the comment
            $this->addGrant($obj->comment_srl);
            // call a trigger(after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.insertComment', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            if(!$manual_inserted) {
                // send a message if notify_message option in enabled in the original article
                $oDocument->notify(Context::getLang('comment'), $obj->content);
                // send a message if notify_message option in enabled in the original comment
                if($obj->parent_srl) {
                    $oParent = $oCommentModel->getComment($obj->parent_srl);
					if ($oParent->get('member_srl') != $oDocument->get('member_srl')) {
						$oParent->notify(Context::getLang('comment'), $obj->content);
					}
                }
            }


            $output->add('comment_srl', $obj->comment_srl);
			//remove from cache
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) 
            {
            	$cache_key_newest = 'object_newest_comment_list:'.$obj->module_srl;
                $oCacheHandler->delete($cache_key_newest);
            	$cache_object = $oCacheHandler->get('comment_list_document_pages');
            	if(isset($cache_object) && is_array($cache_object)){
				    foreach ($cache_object as $object){
						$cache_key = $object;
						$oCacheHandler->delete($cache_key);
				    }
				}elseif(!is_array($cache_object)) {
				    $oCacheHandler->delete($cache_key);
				}
		                $oCacheHandler->delete('comment_list_document_pages');
            }
            return $output;
        }

        /**
         * @brief fix the comment
         **/
        function updateComment($obj, $is_admin = false) {
            $obj->__isupdate = true;
            // call a trigger (before)
            $output = ModuleHandler::triggerCall('comment.updateComment', 'before', $obj);
            if(!$output->toBool()) return $output;
            // create a comment model object
            $oCommentModel = &getModel('comment');
            // get the original data
            $source_obj = $oCommentModel->getComment($obj->comment_srl);
            if(!$source_obj->getMemberSrl()) {
                $obj->member_srl = $source_obj->get('member_srl');
                $obj->user_name = $source_obj->get('user_name');
                $obj->nick_name = $source_obj->get('nick_name');
                $obj->email_address = $source_obj->get('email_address');
                $obj->homepage = $source_obj->get('homepage');
            }
            // check if permission is granted
            if(!$is_admin && !$source_obj->isGranted()) return new Object(-1, 'msg_not_permitted');

            if($obj->password) $obj->password = md5($obj->password);
            if($obj->homepage &&  !preg_match('/^[a-z]+:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
            // set modifier's information if logged-in and posting author and modifier are matched.
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if($source_obj->member_srl == $logged_info->member_srl) {
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }
            // if nick_name of the logged-in author doesn't exist
            if($source_obj->get('member_srl')&& !$obj->nick_name) {
                $obj->member_srl = $source_obj->get('member_srl');
                $obj->user_name = $source_obj->get('user_name');
                $obj->nick_name = $source_obj->get('nick_name');
                $obj->email_address = $source_obj->get('email_address');
                $obj->homepage = $source_obj->get('homepage');
            }


			if(!$obj->content) $obj->content = $source_obj->get('content');
            // remove XE's wn tags from contents
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);
            // remove iframe and script if not a top administrator on the session
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();
            // Update
            $output = executeQuery('comment.updateComment', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // call a trigger (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.updateComment', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            $output->add('comment_srl', $obj->comment_srl);
			//remove from cache
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) 
            {
            	$cache_key_newest = 'object_newest_comment_list:'.$obj->module_srl;
                $oCacheHandler->delete($cache_key_newest);
            	$cache_object = $oCacheHandler->get('comment_list_document_pages');
            	if(isset($cache_object) && is_array($cache_object)){
		    foreach ($cache_object as $object){
			$cache_key = $object;
			$oCacheHandler->delete($cache_key);
		    }
		}elseif(!is_array($cache_object)) {
		    $oCacheHandler->delete($cache_key);
		}
                $oCacheHandler->delete('comment_list_document_pages');
            }
            return $output;
        }

        /**
         * @brief Delete comment
         **/
        function deleteComment($comment_srl, $is_admin = false, $isMoveToTrash = false) {
            // create the comment model object
            $oCommentModel = &getModel('comment');
            // check if comment already exists
            $comment = $oCommentModel->getComment($comment_srl);
            if($comment->comment_srl != $comment_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $comment->document_srl;
            // call a trigger (before)
            $output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
            if(!$output->toBool()) return $output;
            // check if child comment exists on the comment
            $child_count = $oCommentModel->getChildCommentCount($comment_srl);
            if($child_count>0) return new Object(-1, 'fail_to_delete_have_children');
            // check if permission is granted
            if(!$is_admin && !$comment->isGranted()) return new Object(-1, 'msg_not_permitted');

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();
            // Delete
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.deleteComment', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $output = executeQuery('comment.deleteCommentList', $args);
            // update the number of comments
            $comment_count = $oCommentModel->getCommentCount($document_srl);
            // create the controller object of the document
            $oDocumentController = &getController('document');
            // update comment count of the article posting 
            $output = $oDocumentController->updateCommentCount($document_srl, $comment_count, null, false);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // call a trigger (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

			if(!$isMoveToTrash)
			{
				$this->_deleteDeclaredComments($args);
				$this->_deleteVotedComments($args);
			}

            // commit
            $oDB->commit();

            $output->add('document_srl', $document_srl);
			//remove from cache
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) 
            {
            	$cache_object = $oCacheHandler->get('comment_list_document_pages');
            	if(isset($cache_object) && is_array($cache_object)){
		    foreach ($cache_object as $object){
			$cache_key = $object;
			$oCacheHandler->delete($cache_key);
		    }
		}elseif(!is_array($cache_object)) {
		    $oCacheHandler->delete($cache_key);
		}
                $oCacheHandler->delete('comment_list_document_pages');
            }
            return $output;
        }

        /**
         * @brief remove all comment relation log
         **/
		function deleteCommentLog()
		{
			$this->_deleteDeclaredComments($args);
			$this->_deleteVotedComments($args);
		}

        /**
         * @brief remove all comments of the article
         **/
        function deleteComments($document_srl) {
            // create the document model object
            $oDocumentModel = &getModel('document');
            $oCommentModel = &getModel('comment');
            // check if permission is granted
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists() || !$oDocument->isGranted()) return new Object(-1, 'msg_not_permitted');
            // get a list of comments and then execute a trigger(way to reduce the processing cost for delete all)
            $args->document_srl = $document_srl;
            $comments = executeQueryArray('comment.getAllComments',$args);
            if($comments->data) {
				$commentSrlList = array();
                foreach($comments->data as $key => $comment) {
					array_push($commentSrlList, $comment->comment_srl);
                    // call a trigger (before)
                    $output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
                    if(!$output->toBool()) continue;
                    // call a trigger (after)
                    $output = ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
                    if(!$output->toBool()) continue;
                }
            }
            // delete the comment
            $args->document_srl = $document_srl;
            $output = executeQuery('comment.deleteComments', $args);
            if(!$output->toBool()) return $output;
            // Delete a list of comments
            $output = executeQuery('comment.deleteCommentsList', $args);

			//delete declared, declared_log, voted_log
			if(is_array($commentSrlList) && count($commentSrlList)>0)
			{
				unset($args);
				$args->comment_srl = join(',', $commentSrlList);
				$this->_deleteDeclaredComments($args);
				$this->_deleteVotedComments($args);
			}
			//remove from cache
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) 
            {
            	$cache_object = $oCacheHandler->get('comment_list_document_pages');
            	if(isset($cache_object) && is_array($cache_object)){
		    foreach ($cache_object as $object){
			$cache_key = $object;
			$oCacheHandler->delete($cache_key);
		    }
		}elseif(!is_array($cache_object)) {
		    $oCacheHandler->delete($cache_key);
		}
                $oCacheHandler->delete('comment_list_document_pages');
            }

            return $output;
        }

		/**
		 * @brief delete declared comment, log
		 * @param $commentSrls : srls string (ex: 1, 2,56, 88)
		 * @return void
		 **/
		function _deleteDeclaredComments($commentSrls)
		{
			executeQuery('comment.deleteDeclaredComments', $commentSrls);
			executeQuery('comment.deleteCommentDeclaredLog', $commentSrls);
		}

		/**
		 * @brief delete voted comment log
		 * @param $commentSrls : srls string (ex: 1, 2,56, 88)
		 * @return void
		 **/
		function _deleteVotedComments($commentSrls)
		{
			executeQuery('comment.deleteCommentVotedLog', $commentSrls);
		}

        /**
         * @brief Increase vote-up counts of the comment
         **/
        function updateVotedCount($comment_srl, $point = 1) {
            if($point > 0) {
                $failed_voted = 'failed_voted';
                $success_message = 'success_voted';
            } else {
                $failed_voted = 'failed_blamed';
                $success_message = 'success_blamed';
            }

            // invalid vote if vote info exists in the session info. 
            if($_SESSION['voted_comment'][$comment_srl]) return new Object(-1, $failed_voted);

            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);
            // invalid vote if both ip addresses between author's and the current user are same.
            if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['voted_comment'][$comment_srl] = true;
                return new Object(-1, $failed_voted);
            }
            // if the comment author is a member 
            if($oComment->get('member_srl')) {
                // create the member model object
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();
                // session registered if the author information matches to the current logged-in user's. 
                if($member_srl && $member_srl == $oComment->get('member_srl')) {
                    $_SESSION['voted_comment'][$comment_srl] = true;
                    return new Object(-1, $failed_voted);
                }
            }
            // If logged-in, use the member_srl. otherwise use the ipaddress. 
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getCommentVotedLogInfo', $args);
            // session registered if log info contains recommendation vote log. 
            if($output->data->count) {
                $_SESSION['voted_comment'][$comment_srl] = true;
                return new Object(-1, $failed_voted);
            }

            // update the number of votes
            if($point < 0) {
                $args->blamed_count = $oComment->get('blamed_count') + $point;
                $output = executeQuery('comment.updateBlamedCount', $args);
            } else {
                $args->voted_count = $oComment->get('voted_count') + $point;
                $output = executeQuery('comment.updateVotedCount', $args);
            }
            // leave logs
            $args->point = $point;
            $output = executeQuery('comment.insertCommentVotedLog', $args);
            // leave into session information
            $_SESSION['voted_comment'][$comment_srl] = true;

            // Return the result
            return new Object(0, $success_message);
        }

        /**
         * @brief report a blamed comment
         **/
        function declaredComment($comment_srl) {
            // Fail if session information already has a reported document
            if($_SESSION['declared_comment'][$comment_srl]) return new Object(-1, 'failed_declared');
            // check if already reported
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getDeclaredComment', $args);
            if(!$output->toBool()) return $output;
            // get the original comment 
            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);
            // failed if both ip addresses between author's and the current user are same.
            if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['declared_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_declared');
            }
            // if the comment author is a member 
            if($oComment->get('member_srl')) {
                // create the member model object 
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();
                // session registered if the author information matches to the current logged-in user's.
                if($member_srl && $member_srl == $oComment->get('member_srl')) {
                    $_SESSION['declared_comment'][$comment_srl] = true;
                    return new Object(-1, 'failed_declared');
                }
            }
            // If logged-in, use the member_srl. otherwise use the ipaddress.
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getCommentDeclaredLogInfo', $args);
            // session registered if log info contains report log.
            if($output->data->count) {
                $_SESSION['declared_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_declared');
            }
            // execute insert 
            if($output->data->declared_count > 0) $output = executeQuery('comment.updateDeclaredComment', $args);
            else $output = executeQuery('comment.insertDeclaredComment', $args);
            if(!$output->toBool()) return $output;
            // leave the log
            $output = executeQuery('comment.insertCommentDeclaredLog', $args);
            // leave into the session information
            $_SESSION['declared_comment'][$comment_srl] = true;

            $this->setMessage('success_declared');
        }

        /**
         * @brief method to add a pop-up menu when clicking for displaying child comments
         **/
        function addCommentPopupMenu($url, $str, $icon = '', $target = 'self') {
            $comment_popup_menu_list = Context::get('comment_popup_menu_list');
            if(!is_array($comment_popup_menu_list)) $comment_popup_menu_list = array();

            $obj->url = $url;
            $obj->str = $str;
            $obj->icon = $icon;
            $obj->target = $target;
            $comment_popup_menu_list[] = $obj;

            Context::set('comment_popup_menu_list', $comment_popup_menu_list);
        }

        /**
         * @brief save the comment extension form for each module 
         **/
        function procCommentInsertModuleConfig() {
            $module_srl = Context::get('target_module_srl');
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $comment_config->comment_count = (int)Context::get('comment_count');
			if(!$comment_config->comment_count) $comment_config->comment_count = 50;

			$comment_config->use_vote_up = Context::get('use_vote_up');
			if(!$comment_config->use_vote_up) $comment_config->use_vote_up = 'Y';

            $comment_config->use_vote_down = Context::get('use_vote_down');
            if(!$comment_config->use_vote_down) $comment_config->use_vote_down = 'Y';

            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $this->setCommentModuleConfig($srl,$comment_config);
            }

            $this->setError(-1);
            $this->setMessage('success_updated');
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
				header('location:'.$returnUrl);
				return;
			}
        }

		function setCommentModuleConfig($srl, $comment_config){
            $oModuleController = &getController('module');
			$oModuleController->insertModulePartConfig('comment',$srl,$comment_config);
            return new Object();
		}

        /**
         * @brief get comment all list
         **/
		function procCommentGetList()
		{
			if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
			$commentSrls = Context::get('comment_srls');
			if($commentSrls) $commentSrlList = explode(',', $commentSrls);

			if(count($commentSrlList) > 0) {
				$oCommentModel = &getModel('comment');
				$commentList = $oCommentModel->getComments($commentSrlList);

				if(is_array($commentList))
				{
					foreach($commentList AS $key=>$value)
					{
						$value->content = strip_tags($value->content);
					}
				}
			}
			else
			{
				global $lang;
				$commentList = array();
				$this->setMessage($lang->no_documents);
			}

			$this->add('comment_list', $commentList);
		}
    }
?>
