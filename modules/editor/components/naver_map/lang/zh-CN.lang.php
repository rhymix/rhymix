<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  所见即所得编辑器(editor) 模块 > naver地图 (naver_map) 组件语言包
     **/

    $lang->map_width = "宽度大小";
    $lang->map_height = "高度大小";

    // 词句
    $lang->about_address = "例) 분당 정자동, 역삼";
    $lang->about_address_use = "在搜索窗口输入要搜索的地址后选择显示的结果按『添加』按钮会在文章里显示地图。";

    // 错误信息
    $lang->msg_not_exists_addr = "找不到搜索的对象";
    $lang->msg_fail_to_socket_open = "链接邮编搜索对象服务器失败";
    $lang->msg_no_result = "搜索没有找到结果";

    $lang->msg_no_apikey = "为了使用naver地图需要 open api key。\n open api key在 管理员 >  所见即所得编辑器 > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">naver地图设定</a>选择后输入";
?>
