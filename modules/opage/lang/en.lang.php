<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  basic language pack of external page module
     **/

    $lang->opage = "External Page";
    $lang->opage_path = "Location of External Document";
    $lang->opage_caching_interval = "Set Caching Time";

    $lang->about_opage = "This module enables usage of external html or php files in Zeroboard XE.<br />It allows absolute or relative path, and if the url starts with 'http://' , it can display the external page of the server.";
    $lang->about_opage_path= "Please input the location of external document.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php' , the result will be received and then displayed.<br />This is current Zeroboard Xe's absolute path.<br />";
    $lang->about_opage_caching_interval = "The unit is minute, and it displays temporary saved data for assigned time.<br />It is recommended to cache for proper time if a lot of resources are needed when displaying other servers' data or information.<br />A value of 0 will not cache.";
?>
