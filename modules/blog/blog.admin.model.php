<?php
    /**
     * @class  blogAdminModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  blog 모듈의 admin model class
     **/

    class blogAdminModel extends blog {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 특정 카테고리의 정보를 이용하여 템플릿을 구한후 return
         * 관리자 페이지에서 특정 메뉴의 정보를 추가하기 위해 서버에서 tpl을 컴파일 한후 컴파일 된 html을 직접 return
         **/
        function getBlogAdminCategoryTplInfo() {
            // 해당 메뉴의 정보를 가져오기 위한 변수 설정
            $category_srl = Context::get('category_srl');
            $parent_srl = Context::get('parent_srl');

            // 회원 그룹의 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $oDocumentModel = &getModel('document');

            // parent_srl이 있고 category_srl 이 없으면 하부 메뉴 추가임
            if(!$category_srl && $parent_srl) {
                // 상위 메뉴의 정보를 가져옴
                $parent_info = $oDocumentModel->getCategory($parent_srl);

                // 추가하려는 메뉴의 기본 변수 설정 
                $category_info->category_srl = getNextSequence();
                $category_info->parent_srl = $parent_srl;
                $category_info->parent_category_title = $parent_info->title;

            // root에 메뉴 추가하거나 기존 메뉴의 수정일 경우
            } else {
                // category_srl 이 있으면 해당 메뉴의 정보를 가져온다
                if($category_srl) $category_info = $oDocumentModel->getCategory($category_srl);

                // 찾아진 값이 없다면 신규 메뉴 추가로 보고 category_srl값만 구해줌
                if(!$category_info->category_srl) {
                    $category_info->category_srl = getNextSequence();
                }
            }

            Context::set('category_info', $category_info);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'category_info');

            // return 할 변수 설정
            $this->add('tpl', $tpl);
        }

    }
?>
