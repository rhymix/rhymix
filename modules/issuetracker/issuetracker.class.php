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
        }

        function checkUpdate()
        {
            $oModuleModel = &getModel('module');
            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after')) return true;
            return false;
        }

        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after'))
                $oModuleController->insertTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after');
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after')) 
                $oModuleController->insertTrigger('document.deleteDocument', 'issuetracker', 'controller', 'triggerDeleteDocument', 'after');
            return new Object(0, 'success_updated');
        }
    }
?>
