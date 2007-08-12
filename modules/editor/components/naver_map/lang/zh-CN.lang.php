<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  网页编辑器(editor) 模块 > naver地图 (naver_map) 组件语言包
     **/

    $lang->map_width = "宽度";
    $lang->map_height = "高度";

    // 词句
    $lang->about_address = "例) 王府井 餐厅, 月坛公园";
    $lang->about_address_use = "在搜索窗口搜索要找的地址后，按『添加』按钮即可把相关地图插入到文章当中。";

    // 错误信息
    $lang->msg_not_exists_addr = "没有找到搜索的对象";
    $lang->msg_fail_to_socket_open = "链接搜索邮编服务器失败。";
    $lang->msg_no_result = "没有搜索结果";

    $lang->msg_no_apikey = "要想使用naver地图，将需要一个open api key。\n 请选择管理员 >  网页编辑器 > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">naver地图设置</a>后输入open api key。";
?>
