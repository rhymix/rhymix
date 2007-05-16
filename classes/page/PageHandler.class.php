<?php
    /**
     * @class PageHandler
     * @author zero (zero@nzeo.com)
     * @brief 페이지 네비게이션 담당
     * @version 0.1
     *
     * 전체갯수, 전체페이지, 현재페이지, 페이지당 목록의 수를 넘겨주면 \n
     * 페이지 네비게이션에 필요한 variables와 method를 구현\n
     **/

    class PageHandler extends Handler {

        var $total_count = 0; ///< 전체 item의 갯수
        var $total_page = 0; ///< 전체 페이지 수
        var $cur_page = 0; ///< 현 페이지
        var $page_count = 10; ///< 한번에 보일 페이지의 수
        var $first_page = 1; ///< 첫 페이지
        var $last_page = 1; ///< 마지막 페이지
        var $point = 0; ///< getNextPage() 호출시 증가하는 값

        /**
         * @brief constructor
         **/
        function PageHandler($total_count, $total_page, $cur_page, $page_count = 10) {
            $this->total_count = $total_count;
            $this->total_page = $total_page;
            $this->cur_page = $cur_page;
            $this->page_count = $page_count;
            $this->point = 0;

            $first_page = $cur_page - (int)($page_count/2);
            if($first_page<1) $first_page = 1;
            $last_page = $total_page;
            if($last_page>$total_page) $last_page = $total_page;

            $this->first_page = $first_page;
            $this->last_page = $last_page;

            if($total_page < $this->page_count) $this->page_count = $total_page;
        }

        /**
         * @brief 다음 페이지 요청
         **/
        function getNextPage() {
            $page = $this->first_page+$this->point++;
            if($this->point > $this->page_count) $page = 0;
            return $page;
        }
    }
?>
