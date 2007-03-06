<?php
    /**
     * @class  rss
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     **/

    class rss extends ModuleObject {

        var $default_rss_type = "rss20";
        var $rss_types = array(
                "rss20" => "rss 2.0",
                "rss10" => "rss 1.0",
            );

    }
?>
