<?php
    /**
     * @class  documentAdminModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  document 모듈의 admin model class
     **/

    class documentAdminModel extends document {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 특정 카테고리의 정보를 이용하여 템플릿을 구한후 return
         * 관리자 페이지에서 특정 메뉴의 정보를 추가하기 위해 서버에서 tpl을 컴파일 한후 컴파일 된 html을 직접 return
         **/
        function getDocumentAdminCategoryTplInfo() {
            // 해당 메뉴의 정보를 가져오기 위한 변수 설정
            $module_srl = Context::get('module_srl');

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            $category_srl = Context::get('category_srl');
            $parent_srl = Context::get('parent_srl');

            // 회원 그룹의 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($module_info->site_srl);
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


            $category_info->title = htmlspecialchars($category_info->title);
            Context::set('category_info', $category_info);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile('./modules/document/tpl', 'category_info');
            $tpl = str_replace("\n",'',$tpl);

            // 사용자 정의 언어 변경
            $oModuleController = &getController('module');
            $oModuleController->replaceDefinedLangCode($tpl);



            // return 할 변수 설정
            $this->add('tpl', $tpl);
        }

        /**
          * @brief 휴지통에 존재하는 문서 목록을 가져옴
          **/
        function getDocumentTrashList($obj) {
            // 정렬 대상과 순서 체크
            if (!in_array($obj->sort_index, array('list_order','delete_date','title'))) $obj->sort_index = 'list_order';
            if (!in_array($obj->order_type, array('desc','asc'))) $obj->order_type = 'asc';

            // module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
            if ($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크
            if (is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            // 변수 체크
            $args->sort_index = $obj->sort_index;
            $args->order_type = $obj->order_type;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->member_srl = $obj->member_srl;

            // query_id 지정
            $query_id = 'document.getTrashList';

            // query 실행
            $output = executeQueryArray($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if (!$output->toBool() || !count($output->data)) return $output;

            $idx = 0;
            $data = $output->data;
            unset($output->data);

            $keys = array_keys($data);
            $virtual_number = $keys[0];

            foreach($data as $key => $attribute) {
                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute, false);
                if ($is_admin) $oDocument->setGrant();

                $output->data[$virtual_number] = $oDocument;
                $virtual_number--;
            }

            return $output;
        }

        /**
          * @brief trash_srl값을 가지는 휴지통 문서를 가져옴
          **/
        function getDocumentTrash($trash_srl) {
            $args->trash_srl = $trash_srl;
            $output = executeQuery('document.getTrash', $args);

            $node = $output->data;
            if (!$node) return;

            return $node;
        }

    }
?>
