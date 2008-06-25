<?php
    /**
     * @class  tccommentnotifier controller
     * @author haneul (haneul0318@gmail.com) 
     * @brief  tccommentnotifier 모듈의 controller class
     **/


    class tccommentnotifyController extends tccommentnotify {
        /**
         * @brief initialization
         **/

        function init() {
        }

        function insertSite($title, $name, $url)
        {
            $id = getNextSequence();
            $args->id = $id;
            $args->title = $title; 
            $args->name = $name; 
            $args->url = $url;
            $output = executeQuery("tccommentnotify.insertSite", $args);
            if(!$output->toBool())
            {
                return -1;
            }
            else
            {
                return $id;
            }
        }

        function insertParent($obj, $siteid, $module_srl)
        {
            $parentid = getNextSequence();
            $args->notified_srl = $parentid;
            $args->module_srl = $module_srl;
            $args->name = $obj->r1_name;
            $args->homepage = $obj->r1_homepage;
            $args->written = date('YmdHis', $obj->r1_regdate);
            $args->comment = $obj->r1_body;
            $args->entry = $obj->s_no;
            $args->siteid = $siteid;
            $args->url = $obj->r1_url;
            $args->remoteid = $obj->r1_no;
            $args->entrytitle = $obj->s_post_title;
            $args->entryurl = $obj->s_url;
            $args->list_order = $parentid * -1;
            $output = executeQuery("tccommentnotify.insertCommentNotified", $args);
            if(!$output->toBool())
            {
                return -1;
            }
            return $parentid;
        }

        function procDoNotify()
        {
            $lockFilePath = $this->cachedir.$this->lockfile;
            if(file_exists($lockFilePath))
            {
                return;
            }

            $fp = null;
            if(version_compare(PHP_VERSION, "4.3.2", '<'))
            {
                $fp = fopen($lockFilePath, "a");
            }
            else
            {
                $fp = fopen($lockFilePath, "x");
                if(!$fp)
                {
                    return;
                }
            }

            fwrite($fp, "lock");
            fclose($fp);

            if( file_exists($this->cachedir.$this->cachefile) )
            {
                unlink($this->cachedir.$this->cachefile);
            }

            $oModel = &getModel('tccommentnotify');
            $output = $oModel->GetCommentsFromNotifyQueue();
            if(!$output->toBool())
            {
                debugPrint("Error");
                debugPrint($output);
            }
            if($output->data)
            {
                foreach($output->data as $data)
                {
                    $this->deleteFromQueue($data->comment_srl);
                    $this->sendCommentNotify($data->comment_srl);
                }
            }
            unlink($lockFilePath);
        }

        function deleteFromQueue($comment_srl)
        {
            $args->comment_srl = $comment_srl;
            executeQuery("tccommentnotify.deleteFromQueue", $args);
        }

        function triggerInsertComment($obj)
        {
            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($obj->comment_srl);
            if($oComment->get('parent_srl'))
            {
                $output = $this->insertCommentNotifyQueue($obj->comment_srl);
                if($output->toBool())
                {
                    if(!file_exists($this->cachedir.$this->cachefile))
                    {
                        if(!file_exists($this->cachedir))
                        {
                            mkdir($this->cachedir);
                        }
                        $fp = fopen($this->cachedir.$this->cachefile, "w");
                        fwrite($fp, "aa");
                        fclose($fp);
                    }
                }
            }
            return new Object(); 
        }

        function insertCommentNotifyQueue($comment_srl)
        {
            $args->comment_srl = $comment_srl;
            return executeQuery("tccommentnotify.insertQueue", $args);
        }

        function sendCommentNotify($comment_srl)
        {
            set_include_path("./libs/PEAR");
            require_once('PEAR.php');
            require_once('HTTP/Request.php');

            $oCommentModel = &getModel('comment');
            $oChild = $oCommentModel->getComment($comment_srl);
            if(!$oChild->isExists())
            {
                return;
            }

            $parent_srl = $oChild->get('parent_srl');
            if(!$parent_srl)
            {
                return;
            }
            $oParent = $oCommentModel->getComment($parent_srl);
            if(!$oParent->isExists())
            {
                return;
            }
            $parentHomepage = $oParent->getHomepageUrl();
            $oMemberModel = &getModel('member');
            if(!$parentHomepage)
            {
                $parent_member = $oParent->getMemberSrl();
                if(!$parent_member)
                    return; 
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($parent_member);
                $parentHomepage = $member_info->homepage;
                if(!$parentHomepage)
                    return;
            }

            $childHomepage = $oChild->getHomepageUrl();
            if(!$childHomepage)
            {
                $child_member = $oChild->getMemberSrl();
                if($child_member)
                {
                    $child_info = $oMemberModel->getMemberInfoByMemberSrl($child_member);
                    $childHomepage = $member_info->homepage;
                }
            }

            $document_srl = $oChild->get('document_srl');
            
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
            if($this->SendNotifyRequest($parentHomepage, &$module_info, &$oDocument, &$oParent, $parentHomepage, &$oChild, $childHomepage) != 200)
            {
                $indexedPage = rtrim($parentHomepage, '/').'/index.php';
                $this->SendNotifyRequest($indexedPage, &$module_info, &$oDocument, &$oParent, $parentHomepage, &$oChild, $childHomepage);
            }
        }

        function SendNotifyRequest($target, $module_info, $oDocument, $oParent, $parentHomepage, $oChild, $childHomepage)
        {
            $oReq = new HTTP_Request();
            $oReq->setURL($target);
            $oReq->setMethod("POST");
            $oReq->addHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
            $oReq->addPostData('mode', 'fb');
            $oReq->addPostData('url', getUrl('mid', $module_info->mid,'act','','module',''));
            $oReq->addPostData('s_home_title', $module_info->browser_title);
            $oReq->addPostData('s_post_title', $oDocument->getTitleText());
            $oReq->addPostData('s_name', $oDocument->getNickName());
            $oReq->addPostData('s_url', $oDocument->getPermanentUrl());
            $oReq->addPostData('s_no', $oDocument->document_srl);
            $oReq->addPostData('r1_name', $oParent->getNickName());
            $oReq->addPostData('r1_no', $oParent->comment_srl);
            $oReq->addPostData('r1_pno', $oDocument->document_srl);
            $oReq->addPostData('r1_rno', '0');
            $oReq->addPostData('r1_homepage', $parentHomepage);
            $oReq->addPostData('r1_regdate', ztime($oParent->get('regdate')));
            $oReq->addPostData('r1_url', sprintf("%s#comment_%s", $oDocument->getPermanentUrl(), $oParent->comment_srl));
            $oReq->addPostData('r2_name', $oChild->getNickName());
            $oReq->addPostData('r2_no', $oChild->comment_srl);
            $oReq->addPostData('r2_pno', $oDocument->document_srl);
            $oReq->addPostData('r2_rno', $oParent->comment_srl);
            $oReq->addPostData('r2_homepage', $childHomepage);
            $oReq->addPostData('r2_regdate', ztime($oChild->get('regdate')));
            $oReq->addPostData('r2_url', sprintf("%s#comment_%s", $oDocument->getPermanentUrl(), $oChild->comment_srl));
            $oReq->addPostData('r1_body', strip_tags($oParent->get('content')));
            $oReq->addPostData('r2_body', strip_tags($oChild->get('content')));

            $oReq->sendRequest(false);
            $code = $oReq->getResponseCode();
            return $code;
        }

        function insertCommentNotify($obj, $siteid, $parentid, $module_srl)
        {
            $myid = getNextSequence();
            $args->notified_srl = $myid;
            $args->module_srl = $module_srl; 
            $args->parent_srl = $parentid;
            $args->name = $obj->r2_name;
            $args->homepage = $obj->r2_homepage;
            $args->written = date('YmdHis', $obj->r2_regdate);
            $args->comment = $obj->r2_body;
            $args->url = $obj->r2_url;
            $args->remoteid = $obj->r2_no;
            $args->list_order = $myid * -1;
            $output = executeQuery("tccommentnotify.insertCommentNotifiedChild", $args);
            $bRet = $output->toBool();
            return $bRet;
        }

        function updateParent($parentid)
        {
            $args->notified_srl = $parentid;
            $args->list_order = -1 * getNextSequence();
            $output = executeQuery("tccommentnotify.updateParent", $args);
        }

        function procNotifyReceived() {

            $obj = Context::getRequestVars();
            $oModel = &getModel('tccommentnotify');

            $oDB = &DB::getInstance();
            $oDB -> begin();
            $siteid = $oModel->GetSite( $obj->url );
            $module_info = Context::get('current_module_info');
            $module_srl = $module_info->module_srl;

            if( $siteid == -2 )
            {
                $oDB->rollback();
                return;
            }
            else if( $siteid == -1 )
            { 
                $siteid = $this->insertSite($obj->s_home_title, $obj->s_name, $obj->url);
                if($siteid == -1)
                {
                    $oDB->rollback();
                    return;
                }
            }

            $parentid = $oModel->GetParentID( $obj->s_no, $siteid, $module_srl, $obj->r1_no );
            if( $parentid == -2 )
            {
                $oDB->rollback();
                return;
            }
            else if ( $parentid == -1 )
            {
                $parentid = $this->insertParent( &$obj, $siteid, $module_srl );
                if($parentid == -1)
                {
                    $oDB->rollback();
                    return;
                }
            }
            else
            {
               $this->updateParent($parentid); 
            }

            if(!$this->insertCommentNotify(&$obj, $siteid, $parentid, $module_srl))
            {
                $oDB->rollback();
                return;
            }

            $oDB->commit();
        }
    }
?>
