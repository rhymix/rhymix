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
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerViewMilestone');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerViewSource');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerViewIssue');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerNewIssue');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerDeleteIssue');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerDeleteTrackback');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerDownload');

            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminContent');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminProjectSetting');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminReleaseSetting');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminAdditionSetup');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminGrantInfo');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminSkinInfo');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminInsertProject');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminDeleteIssuetracker');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminProjectInfo');

            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyMilestone');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyPriority');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyType');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyComponent');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyPackage');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminModifyRelease');
            $oModuleController->insertActionForward('issuetracker', 'view', 'dispIssuetrackerAdminAttachRelease');

            $oModuleController->insertActionForward('issuetracker', 'controller', 'procIssuetrackerAdminAttachRelease');

            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            $oModuleController->insertTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after');


            $oDB = &DB::getInstance();
            $oDB->addIndex("issue_changesets","idx_unique_revision", array("module_srl","revision"), true);
        }

        function checkUpdate()
        {
            $oModuleModel = &getModel('module');
            // 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'issuetracker', 'controller', 'triggerMemberMenu', 'after')) return true;
            return false;
        }
    }
?>
