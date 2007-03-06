<?php
    /**
     * @class  document 
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 high 클래스
     **/

    class document extends ModuleObject {

        // 공지사항용 값
        var $notice_list_order = -2100000000;

        // 관리자페이지에서 사용할 검색 옵션
        var $search_option = array('title','content','title_content','user_name','user_id'); ///< 검색 옵션

    }
?>
