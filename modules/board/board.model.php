<?php
    /**
     * @class  boardModel
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 Model class
     **/

    class boardModel extends module {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 설정 값을 가져옴
         **/
        function getListConfig($module_srl) {
            $oModuleModel = &getModel('module');
            $oDocumentModel = &getModel('document');

            // 저장된 목록 설정값을 구하고 없으면 기본 값으로 설정
            $list_config = $oModuleModel->getModulePartConfig('board', $module_srl);
            if(!$list_config || !count($list_config)) $list_config = array( 'no', 'title', 'nick_name','regdate','readed_count');

            // 사용자 선언 확장변수 구해와서 배열 변환후 return
            $inserted_extra_vars = $oDocumentModel->getExtraKeys($module_srl);

            foreach($list_config as $key) {
                if(preg_match('/^([0-9]+)$/',$key)) $output['extra_vars'.$key] = $inserted_extra_vars[$key];
                else $output[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
            }
            return $output;
        }

        /** 
         * @brief 기본 목록 설정값을 return
         **/
        function getDefaultListConfig($module_srl) {
            // 가상번호, 제목, 등록일, 수정일, 닉네임, 아이디, 이름, 조회수, 추천수 추가
            $virtual_vars = array( 'no', 'title', 'regdate', 'last_update', 'nick_name', 'user_id', 'user_name', 'readed_count', 'voted_count' );
            foreach($virtual_vars as $key) {
                $extra_vars[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
            }

            // 사용자 선언 확장변수 정리
            $oDocumentModel = &getModel('document');
            $inserted_extra_vars = $oDocumentModel->getExtraKeys($module_srl);

            if(count($inserted_extra_vars)) foreach($inserted_extra_vars as $obj) $extra_vars['extra_vars'.$obj->idx] = $obj;

            return $extra_vars;

        }


    }
?>
