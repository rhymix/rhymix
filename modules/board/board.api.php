<?php
    /**
     * @class  boardAPI
     * @author sol(sol@ngleader.com)
     * @brief  board 모듈의 View Action에 대한 API 처리
     **/

    class boardAPI extends board {

/* dispBoardContent 는 사용하지 않는다..
        function dispBoardContent(&$oModule) {
        }
*/

        /**
         * @brief 공지사항 목록
         **/
        function dispBoardNoticeList(&$oModule) {
             $oModule->add('notice_list',$this->arrangeContentList(Context::get('notice_list')));
        }


        /**
         * @brief 컨텐츠 목록
         **/
        function dispBoardContentList(&$oModule) {
            $oModule->add('document_list',$this->arrangeContentList(Context::get('document_list')));
            $oModule->add('page_navigation',Context::get('page_navigation'));
        }


        /**
         * @brief 카테고리(분류) 목록
         **/
        function dispBoardCatogoryList(&$oModule) {
            $oModule->add('category_list',Context::get('category_list'));
        }

        /**
         * @brief 게시물 보기
         **/
        function dispBoardContentView(&$oModule) {
            $oModule->add('oDocument',$this->arrangeContent(Context::get('oDocument')));
        }


        /**
         * @brief 컨텐츠의 파일 목록
         **/
        function dispBoardContentFileList(&$oModule) {
            $oModule->add('file_list',$this->arrangeFile(Context::get('file_list')));
        }


        /**
         * @brief 태그 목록
         **/
        function dispBoardTagList(&$oModule) {
            $oModule->add('tag_list',Context::get('tag_list'));
        }

        /**
         * @brief 컨텐츠의 코멘트 목록
         **/
        function dispBoardContentCommentList(&$oModule) {
            $oModule->add('comment_list',$this->arrangeComment(Context::get('comment_list')));
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
                $output= $content->gets('document_srl','category_srl','is_secret','nick_name','user_id','user_name','title','content','tags','voted_count','blamed_count','comment_count','regdate','last_update','extra_vars1','extra_vars2','extra_vars3','extra_vars4','extra_vars5','extra_vars6','extra_vars7','extra_vars8','extra_vars9','extra_vars10','extra_vars11','extra_vars12','extra_vars13','extra_vars14','extra_vars15','extra_vars16','extra_vars17','extra_vars18','extra_vars19','extra_vars20');
            }
            return $output;
        }

        function arrangeComment($comment_list) {
            $output = array();
            if(count($comment_list)) {
                foreach($comment_list as $key => $val){
                    $item = null;
                    $item = $val->gets('comment_srl','parent_srl','depth','is_secret','content','voted_count','blamed_count','user_id','user_name','nick_name','email_address','homepage','regdate','last_update');
                    $output[] = $item;
                }
            }
            return $output;
        }


        function arrangeFile($file_list) {
            $output = array();
            if(count($file_list)) {
                foreach($file_list as $key => $val){
                    $item = null;
                    $item->sid = $val->sid;
                    $item->download_count = $val->download_count;
                    $item->source_filename = $val->source_filename;
                    $item->uploaded_filename = $val->uploaded_filename;
                    $item->file_size = $val->file_size;
                    $item->regdate = $val->regdate;
                    $item->download_url = $val->download_url;
                    $output[] = $item;
                }
            }
            return $output;
        }
    }
?>
