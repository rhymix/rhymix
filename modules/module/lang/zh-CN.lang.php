<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  简体中文语言包
     **/

    $lang->module_list = "模块目录";
    $lang->module_index = "模块目录";
    $lang->module_category = "模块分类";
    $lang->module_info = "模块信息";
    $lang->add_shortcut = "添加到左侧快捷菜单";
    $lang->module_action = "动作";
    $lang->module_maker = "模块作者";
    $lang->module_history = "变更事项 ";
    $lang->category_title = "分类名称";
    $lang->header_text = '上端内容';
    $lang->footer_text = '下端内容';
    $lang->use_category = '分类使用';
    $lang->category_title = '分类名';
    $lang->checked_count = '被选择的文章数';
    $lang->skin_default_info = '皮肤基本信息';
    $lang->skin_maker = '皮肤作者';
    $lang->skin_maker_homepage = '作者主页';
    $lang->open_rss = 'RSS 公开';
    $lang->open_rss_types = array(
        'Y' => '完全公开',
        'H' => '部分公开',
        'N' => '不公开',
    );

    $lang->cmd_add_shortcut = "添加到左侧快捷菜单";
    $lang->cmd_install = "安装";
    $lang->cmd_update = "升级";
    $lang->cmd_manage_category = '分类管理';
    $lang->cmd_manage_grant = '权限管理';
    $lang->cmd_manage_skin = '皮肤管理';
    $lang->cmd_manage_document = '文章管理';

    $lang->msg_new_module = "模块生成";
    $lang->msg_update_module = "模块修改";
    $lang->msg_module_name_exists = "已存在的模块名称。请输入其他名称。";
    $lang->msg_category_is_null = '没有登录的分类';
    $lang->msg_grant_is_null = '没有登录的权限对象';
    $lang->msg_no_checked_document = '没有被选择的主题';
    $lang->msg_move_failed = '移动失败';
    $lang->msg_cannot_delete_for_child = '不能删除有下级菜单的分类';

    $lang->about_browser_title = "在浏览器窗口显示的标题值。 在RSS/Trackback也可以使用。";
    $lang->about_mid = "模块名称是像 http://地址/?mid=模块名称 直接呼出的值。 (英文+数字+_ 组成)";
    $lang->about_default = "选择将网站里没有mid值链接的情况显示默认";
    $lang->about_module_category = "可以生成分类后管理。 模块分类的管理在 <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">模块管理 > 模块分类 </a>";
    $lang->about_description= '管理用使用的说明';
    $lang->about_default = '选择将网站里没有mid值链接的情况显示默认';
    $lang->about_header_text = '模块顶部显示的内容（可以使用HTML）';
    $lang->about_footer_text = '模块底部显示的内容（可以使用HTML）';
    $lang->about_skin = '可以选择模块皮肤';
    $lang->about_use_category = '选择可以使用分类功能';
    $lang->about_list_count = '可以指定每页显示的主题数。（基本1个）';
    $lang->about_page_count = '可以指定目录下端移动页面的链接数。 (基本10个)';
    $lang->about_admin_id = '可以指定该模块的最高管理权限。<br />登录多数 I D用,(逗号)区分。 (不能访问管理页面)';
    $lang->about_grant = '全部解除特定权限的对象会没有登录的会员也有相关权限';
    $lang->about_open_rss = '可以选择对该模块的RSS的公开。不相关查看内容的权限按RSS的选项指定';
    $lang->about_module = "zeroboard XE是除了基本library以外全部是以模块构成。\n模块管理的模块是帮助显示全部已安装的模块以及管理。\n经常使用的模块通过『添加到左侧快捷菜单』可以方便管理。";
?>
