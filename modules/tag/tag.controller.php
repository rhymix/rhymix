<?php
    /**
     * @class  tagController
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
         * @brief ,(콤마)로 연결된 태그를 정리하는 trigger
         **/
        function triggerArrangeTag(&$obj) {
            if(!$obj->tags) return new Object();

            // tags변수 정리
            $tag_list = explode(',', $obj->tags);
            $tag_count = count($tag_list);
            $tag_list = array_unique($tag_list);
            if(!count($tag_list)) return new Object();

            foreach($tag_list as $tag) {
                if(!trim($tag)) continue;
                $arranged_tag_list[] = trim($tag); 
            }
            if(!count($arranged_tag_list)) $obj->tags = null;
            else $obj->tags = implode(',',$arranged_tag_list);
            return new Object();
        }

        /**
         * @brief 태그 입력 trigger
         * 태그 입력은 해당 글의 모든 태그를 삭제 후 재 입력하는 방식을 이용
         **/
        function triggerInsertTag(&$obj) {
            $module_srl = $obj->module_srl;
            $document_srl = $obj->document_srl;
            $tags = $obj->tags;
            if(!$document_srl) return new Object();

            // 해당 글의 tags를 모두 삭제
            $output = $this->triggerDeleteTag($obj);
            if(!$output->toBool()) return $output;

            // 다시 태그를 입력
            $args->module_srl = $module_srl;
            $args->document_srl = $document_srl;

            $tag_list = explode(',',$tags);
            $tag_count = count($tag_list);
            for($i=0;$i<$tag_count;$i++) {
                unset($args->tag);
                $args->tag = trim($tag_list[$i]);
                if(!$args->tag) continue;
                $output = executeQuery('tag.insertTag', $args);
                if(!$output->toBool()) return $output;
            }

            return new Object();
        }

        /**
         * @brief 특정 문서의 태그 삭제 trigger
         * document_srl에 속한 tag 모두 삭제
         **/
        function triggerDeleteTag(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            $args->document_srl = $document_srl;
            return executeQuery('tag.deleteTag', $args);
        }

        /**
         * @brief module 삭제시 해당 태그 모두 삭제하는 trigger
         **/
        function triggerDeleteModuleTags(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            $oTagController = &getAdminController('tag');
            return $oTagController->deleteModuleTags($module_srl);
        }

    }
?>
