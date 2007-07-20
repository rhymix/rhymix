<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  editor module > language pack of multimedia_link(Naver Map) component
     **/

    $lang->map_width = "Width";
    $lang->map_height = "Height";

    // Expressions
    $lang->about_address = "Ex) Jeongjadong Boondang, Yeoksam";
    $lang->about_address_use = "First search the address and then press [Add] button with displayed results. Then, the map would be added to the article.";

    // Error Messages
    $lang->msg_not_exists_addr = "Address doesn't exists";
    $lang->msg_fail_to_socket_open = "Failed to connect zip code searching server";
    $lang->msg_no_result = "Nothing Found";

    $lang->msg_no_apikey = "Naver Map api key is needed to use Naver Map.\ninput api key after selecting Administrator >  WISYWIG Editor > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">Setting for Naver Map Linkage Component</a>";
    
?>
