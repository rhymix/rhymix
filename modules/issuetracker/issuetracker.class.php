<?PHP
    /**
     * @class  issuetracker
     * @author haneul (haneul0318@gmail.com)
     * @brief  base class for the issue tracker
     **/

    require_once(_XE_PATH_.'modules/issuetracker/issuetracker.item.php');

    class issuetracker extends ModuleObject
    {
        // 검색 대상 지정
        var $search_option = array('title','content','title_content','user_name','nick_name','user_id','tag');

        // 이슈 목록 노출 대상
        var $display_option = array('no','title','milestone','priority','type','component','status','occured_version','package','regdate','assignee', 'writer');
        var $default_enable = array('no','title','status','release','regdate','assignee','writer');

        function moduleInstall()
        {
            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            $oModuleController = &getController('module');
            $oModuleController->insertTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after');

            $oDB = &DB::getInstance();
            $oDB->addIndex("issue_changesets","idx_unique_revision", array("module_srl","revision"), true);

            // 히스토리(=댓글) 첨부파일 활성화 트리거
            $oModuleController->insertTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
            $oModuleController->insertTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentAttachFiles', 'after');

            // movemodule trigger
            $oModuleController->insertTrigger('document.moveDocumentModule', 'issuetracker', 'controller', 'triggerMoveDocumentModule', 'after');
        }

        function checkUpdate()
        {
            $oModuleModel = &getModel('module');
            $oDB = &DB::getInstance();

            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after')) return true;

            // 히스토리(=댓글) 첨부파일 활성화 트리거
            if(!$oModuleModel->getTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
            if(!$oModuleModel->getTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
            if(!$oDB->isColumnExists('issues_history', 'uploaded_count')) return true;

            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'issuetracker', 'controller', 'triggerMoveDocumentModule', 'after')) return true;

            return false;
        }

        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $oDB = &DB::getInstance();

            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after')) {
                $oModuleController->insertTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after');
            }
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after')) {
                $oModuleController->insertTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after');
            }

            // 히스토리(=댓글) 첨부파일 활성화 트리거
            if(!$oModuleModel->getTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) {
                $oModuleController->insertTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
            }
            if(!$oModuleModel->getTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) {
                $oModuleController->insertTrigger('issuetracker.insertHistory', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
            }
            if(!$oDB->isColumnExists('issues_history', 'uploaded_count')) {
                $oDB->addColumn('issues_history', 'uploaded_count', 'number', 11, 0);
            }

            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'issuetracker', 'controller', 'triggerMoveDocumentModule', 'after')) {
                $oModuleController->insertTrigger('document.moveDocumentModule', 'issuetracker', 'controller', 'triggerMoveDocumentModule', 'after');
            }

            return new Object(0, 'success_updated');
        }
    }
?>
