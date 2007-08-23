<?php
    /**
     * @file   modules/krzip/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  English language pack (Only basic contents are listed)
     **/

    // normal words
    $lang->krzip = "Korean Zip Code";
    $lang->krzip_server_hostname = "Server name for zip code checking";
    $lang->krzip_server_port = "Server port for zip code checking";
    $lang->krzip_server_query = "Server path for zip code checking";

    // descriptions
    $lang->about_krzip_server_hostname = "Please input the server's domain for checking zip codes and receiving the result list";
    $lang->about_krzip_server_port = "Please input the server's port number for checking the zip code";
    $lang->about_krzip_server_query = "Please input the query url that will be requested for checking the zip code";

    // error messages
    $lang->msg_not_exists_addr = "Target for searching doesn't exist";
    $lang->msg_fail_to_socket_open = "Unabled to connect to zip code checking server";
?>
