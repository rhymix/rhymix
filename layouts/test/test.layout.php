<?php

  /**
   * @file   : layouts/test/test.addon.php
   * @author : zero <zero@nzeo.com>
   * @desc   : test 레이아웃
   **/

  class test {
    function proc(&$oModule, $oModuleInfo) {
      $oModule->setHtml('레이아웃');
    }
  }
