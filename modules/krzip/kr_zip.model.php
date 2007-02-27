<?php
    /**
     * @class  kr_zip
     * @author zero (zero@nzeo.com)
     * @brief  tag 모듈의 controller class
     **/

    class tagController extends tag {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 태그 입력
         * 태그 입력은 해당 글의 모든 태그를 삭제 후 재 입력하는 방식을 이용
         **/
        function insertTag($module_srl, $document_srl, $tags) {

            // 해당 글의 tags를 모두 삭제
            $this->deleteTag($document_srl);
            if(!$tags) return;

            // tags변수 정리
            $tmp_tag_list = explode(',', $tags);
            $tag_count = count($tmp_tag_list);
            for($i=0;$i<$tag_count;$i++) {
                $tag = trim($tmp_tag_list[$i]); 
                if(!$tag) continue;
                $tag_list[] = $tag;
            }
            if(!count($tag_list)) return;

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다시 태그를 입력
            $args->module_srl = $module_srl;
            $args->document_srl = $document_srl;
            $tag_count = count($tag_list);
            for($i=0;$i<$tag_count;$i++) {
                $args->tag = $tag_list[$i];
                $oDB->executeQuery('tag.insertTag', $args);
            }

            return implode(',',$tag_list);
        }

        /**
         * @brief 특정 문서의 태그 삭제
         **/
        function deleteTag($document_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            return $oDB->executeQuery('tag.deleteTag', $args);
        }

        /**
         * @brief 특정 모듈의 태그 삭제
         **/
        function deleteModuleTags($module_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            return $oDB->executeQuery('tag.deleteModuleTags', $args);
        }
    }
?>
