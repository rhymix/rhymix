<?php
    /**
     * @class  tagModel
     * @author zero (zero@nzeo.com)
     * @brief  tag 모듈의 model class
     **/

    class tagModel extends tag {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 태그 목록을 가져옴
         * 지정된 모듈의 태그를 개수가 많은 순으로 추출
         **/
        function getTagList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->list_count = $obj->list_count;
            $args->count = $obj->sort_index;

            $output = executeQueryArray('tag.getTagList', $args);
            if(!$output->toBool()) return $output;

            return $output;
        }


        /**
         * @brief tag로 document_srl를 가져오기
         **/
		function getDocumentSrlByTag($obj){
			if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

			$args->tag = $obj->tag;
			$output = executeQueryArray('tag.getDocumentSrlByTag', $args);
            if(!$output->toBool()) return $output;

            return $output;
		}

        /**
         * @brief document 에서 사용된 tag 가져오기
         **/
		function getDocumentsTagList($obj){
			if(is_array($obj->document_srl)) $args->document_srl = implode(',', $obj->document_srl);
            else $args->document_srl = $obj->document_srl;

            $output = executeQueryArray('tag.getDocumentsTagList', $args);
            if(!$output->toBool()) return $output;

            return $output;
		}

		/**
		 * @brief 특정tag과 함께 사용된 tag목록
		 **/
		function getTagWithUsedList($obj){
			if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
			else $args->module_srl = $obj->module_srl;

			$args->tag = $obj->tag;
			$output = $this->getDocumentSrlByTag($args);
			$document_srl = array();

			if($output->data){
				foreach($output->data as $k => $v) $document_srl[] = $v->document_srl;
			}
			unset($args);
			$args->document_srl = $document_srl;
			$output = $this->getDocumentsTagList($args);
			return $output;
		}
    }
?>
