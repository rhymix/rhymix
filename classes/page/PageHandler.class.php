<?php
  /**
   * @file   : classes/page/PageHandler.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 페이지 처리
   **/

  class PageHandler {

    var $total_count = 0;
    var $total_page = 0;
    var $cur_page = 0;
    var $page_count = 10;
    var $first_page = 1;
    var $last_page = 1;
    var $point = 0;

    function PageHandler($total_count, $total_page, $cur_page, $page_count = 10) {
      $this->total_count = $total_count;
      $this->total_page = $total_page;
      $this->cur_page = $cur_page;
      $this->page_count = $page_count;
      $this->point = 0;

      $first_page = $cur_page-(int)($page_count/2);
      if($first_page<1) $first_page = 1;
      $last_page = $first_page+$page_count-1;
      if($last_page>$total_page) $last_page = $total_page;

      $this->first_page = $first_page;
      $this->last_page = $last_page;
    }

    function getNextPage() {
      $page = $this->first_page+$this->point++;
      if($page > $this->last_page) $page = 0;
      return $page;
    }

  }

?>
