<?php
    /**
     * @class  documentAdminModel
     * @author NHN (developers@xpressengine.com)
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
