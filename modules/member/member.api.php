<?php
    /**
     * @class  memberAPI
     * @author sol(sol@ngleader.com)
     * @brief  member 모듈의 View Action에 대한 API 처리
     **/

    class memberAPI extends member {


        /**
         * @brief 컨텐츠 목록
         **/
        function dispSavedDocumentList(&$oModule) {
            $document_list = $this->arrangeContentList(Context::get('document_list'));
            $oModule->add('document_list',$document_list);
            $oModule->add('page_navigation',Context::get('page_navigation'));
        }



        function arrangeContentList($content_list) {
            $output = array();
            if(count($content_list)) {
                foreach($content_list as $key => $val) $output[] = $this->arrangeContent($val);
            }
            return $output;
        }


        function arrangeContent($content) {
            $output = null;
            if($content){
                $output= $content->gets('document_srl','category_srl','is_secret','nick_name','user_id','user_name','title','content','tags','voted_count','blamed_count','comment_count','regdate','last_update','extra_vars');
            }
            return $output;
        }

    }
?>
