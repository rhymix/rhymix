<?php
    /**
     * @class  commentAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the comment module
     **/

    class commentAdminController extends comment {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Delete the selected comment from the administrator page
         **/
        function procCommentAdminDeleteChecked() {
			$isTrash = Context::get('is_trash');

            // Error display if none is selected
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            if(!is_array($cart)) $comment_srl_list= explode('|@|', $cart);
			else $comment_srl_list = $cart;
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

			$oCommentController = &getController('comment');
			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();

			// comment into trash
			if($isTrash == 'true') $this->_moveCommentToTrash($comment_srl_list, $oCommentController, $oDB);

			$deleted_count = 0;
			// Delete the comment posting
			for($i=0;$i<$comment_count;$i++) {
				$comment_srl = trim($comment_srl_list[$i]);
				if(!$comment_srl) continue;

				$output = $oCommentController->deleteComment($comment_srl, true, $isTrash);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}

				$deleted_count ++;
			}
			$oDB->commit();

            $this->setMessage( sprintf(Context::getLang('msg_checked_comment_is_deleted'), $deleted_count) );

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommentAdminList');
				header('location:'.$returnUrl);
				return;
			}
        }

		function _moveCommentToTrash($commentSrlList, &$oCommentController, &$oDB)
		{
			require_once(_XE_PATH_.'modules/trash/model/TrashVO.php');

			if(is_array($commentSrlList))
			{
				$logged_info = Context::get('logged_info');
				$oCommentModel = &getModel('comment');
				$commentItemList = $oCommentModel->getComments($commentSrlList);
				$oTrashAdminController = &getAdminController('trash');

				foreach($commentItemList AS  $key=>$oComment)
				{
					$oTrashVO = new TrashVO();
					$oTrashVO->setTrashSrl(getNextSequence());
					$oTrashVO->setTitle(trim(strip_tags($oComment->variables['content'])));
					$oTrashVO->setOriginModule('comment');
					$oTrashVO->setSerializedObject(serialize($oComment->variables));

					$output = $oTrashAdminController->insertTrash($oTrashVO);
					if (!$output->toBool()) {
						$oDB->rollback();
						return $output;
					}
				}
			}
		}

        /**
         * @brief cancel the blacklist of abused comments reported by other users
         **/
        function procCommentAdminCancelDeclare() {
            $comment_srl = trim(Context::get('comment_srl'));

            if($comment_srl) {
                $args->comment_srl = $comment_srl;
                $output = executeQuery('comment.deleteDeclaredComments', $args);
                if(!$output->toBool()) return $output;
            }
        }

		function procCommentAdminAddCart()
		{
			$comment_srl = Context::get('comment_srl');
			$commentSrlList = explode(',', $comment_srl);

			if(is_array($commentSrlList))
			{
				foreach($commentSrlList AS $key=>$value)
				{
					$_SESSION['comment_management'][$value] = true;
				}
			}
		}

        /**
         * @brief delete all comments of the specific module
         **/
        function deleteModuleComments($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('comment.deleteModuleComments', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('comment.deleteModuleCommentsList', $args);
            return $output;
        }

        /**
         * @brief restore comment from trash module, called by trash module
		 * this method is passived
         **/
		function restoreTrash($originObject)
		{
			if(is_array($originObject)) $originObject = (object)$originObject;

			$obj->document_srl = $originObject->document_srl;
			$obj->comment_srl = $originObject->comment_srl;
			$obj->parent_srl = $originObject->parent_srl;
			$obj->content = $originObject->content;
			$obj->password = $originObject->password;
			$obj->nick_name = $originObject->nick_name;
			$obj->member_srl = $originObject->member_srl;
			$obj->email_address = $originObject->email_address;
			$obj->homepage = $originObject->homepage;
			$obj->is_secret = $originObject->is_secret;
			$obj->notify_message = $originObject->notify_message;
			$obj->module_srl = $originObject->module_srl;

			$oCommentController = &getController('comment');
			$output = $oCommentController->insertComment($obj);

			return $output;
		}

        /**
         * @brief empty comment in trash, called by trash module
		 * this method is passived
         **/
		function emptyTrash($originObject)
		{
			$originObject = unserialize($originObject);
			if(is_array($originObject)) $originObject = (object) $originObject;

			$oComment = new commentItem();
			$oComment->setAttribute($originObject);

			//already comment deleted, therefore only comment log delete
			$oCommentController = &getController('comment');
			$output = $oCommentController->deleteCommentLog($oComment->get('comment_srl'));
			return $output;
		}
    }
?>
