<?php
    /**
     * @file   modules/layout/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com> 翻译：guny
     * @brief  布局(layout) 模块简体中文语言包
     **/

    $lang->cmd_layout_management = '布局设置';
    $lang->cmd_layout_edit = '布局编辑';

    $lang->layout_name = '布局名';
    $lang->layout_maker = "布局作者";
    $lang->layout_license = '版权';
    $lang->layout_history = "更新记录 ";
    $lang->layout_info = "布局信息";
    $lang->layout_list = '布局列表';
    $lang->menu_count = '菜单数';
    $lang->downloaded_list = '下载目录';
    $lang->layout_preview_content = '显示内容的部分。';
    $lang->not_apply_menu = '布局共享';

    $lang->cmd_move_to_installed_list = "查看生成目录";

    $lang->about_downloaded_layouts = "已下载的布局目录";
    $lang->about_title = '请输入连接模块时容易区分的标题。';
    $lang->about_not_apply_menu = '勾选表示连接到此布局的菜单项全部采用此布局。';

    $lang->about_layout = "布局模块使网站制作变得更简单。<br />通过布局设置及菜单的链接，可以轻松制作以多种模块组成的完整网站。<br />- 无法删除和修改的布局可能是博客或其他模块自带的模板，因此应到相关模块进行设置。";
    $lang->about_layout_code = 
        "修改的布局代码保存后即可生效。
	保存之前请必须先预览后再保存。
        XE布局语法请参考<strong><a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE模板</a></strong>。";

    $lang->layout_export = '导出';
    $lang->layout_btn_export = '下载布局设置';
    $lang->about_layout_export = '可以导出当前已修改好的布局。';
    $lang->layout_import = '导入';
    $lang->about_layout_import = '导入之前请利用<strong>导出功能</strong>备份好当前的布局及设置，因为导入会删除当前的布局及设置。';

    $lang->layout_manager = array(
        0  => '布局管理器',
        1  => '保存',
        2  => '取消',
        3  => '宽度',
        4  => '版式',
        5  => '对齐',
        6  => '固定宽度',
        7  => '自适应宽度',
        8  => '固定+自适应(内容)',
        9  => '1栏',
        10 => '2栏 (左侧内容区)',
        11 => '2栏 (右侧内容区)',
        12 => '3栏 (左侧内容区)',
        13 => '3栏 (剧中内容区)',
        14 => '3栏 (右侧内容区)',
        15 => '左对齐',
        16 => '剧中对齐',
        17 => '右对齐',
        18 => '全局',
        19 => '布局',
        20 => '添加控件',
        21 => '添加内容',
        22 => '属性',
        23 => '控件样式',
        24 => '修改',
        25 => '删除',
        26 => '对齐',
        27 => '占段落',
        28 => '左对齐',
        29 => '右对齐',
        30 => '宽度',
        31 => '高度',
        32 => '外边距',
        33 => '内填充',
        34 => '上',
        35 => '左',
        36 => '右',
        37 => '下',
        38 => '边框', 
        39 => '无',
        40 => '背景',
        41 => '颜色',
        42 => '图片',
        43 => '选择',
        44 => '背景重复',
        45 => '平铺',
        46 => '不重复',
        47 => '水平重复',
        48 => '垂直重复',
        49 => '应用',
        50 => '取消',
        51 => '初始化',
        52 => '字体',
        53 => '字体',
        54 => '文字颜色',
    );

    $lang->layout_image_repository = '布局文件库';
    $lang->about_layout_image_repository = '可以上传要在所选布局中使用的图片/FLASH文件(导出时包含此库文件)。';
    $lang->msg_layout_image_target = '只允许上传gif, png, jpg, swf, flv文件。';
    $lang->layout_migration = '导入/导出';
    $lang->about_layout_migration = '可以把已修改好的布局导出为tar文件或把已有的tar文件直接导入。'."\n".'(目前此功能只能在faceOff布局中使用)';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff布局管理工具',
        'description' => '利用FaceOff布局管理工具，可以在线随意布置您的布局。<br/>请仔细查看下面的布局示意图和功能简介后，尽情发挥吧！<br/>FaceOff的布局示意图如下：',
        'layout' => '根据布局示意图，对其进行宽度/版式/对齐方式的调整。<br/>控件插入区为Extension(e1, e2)区和Neck, Knee区。<br/>另外的Body, Layout, Header, Body, Footer区可以使用Style来进行渲染，Content区是内容显示区。',
        'setting' => '布局管理工具顶部左侧菜单说明：<br/><ul><li>保存 : 保存已修改的设置</li><li>取消 : 不保存返回</li><li>初始化 : 重置为空白布局</li><li>宽度 : 可指定固定/自适应/固定+自适应(内容)布局宽度样式</li><li>版式 : 可布置2个Extension区和Content区的样式</li><li>对齐 : 布局对齐方式</li></ul>',
        'hotkey' => '选取各个区域可以利用鼠标，还可以利用快捷键：<br/><ul><li>tab键 : 当前没有被选取的控件，选取顺序为： Header, Body, Footer；已有被选取的控件，将选取下一个控件。</li><li>Shift + tab键 : 与tab键作用相反。</li><li>Esc键 : 当前没有被选取的区域，选取顺序为： Neck, Extension(e1,e2),Knee；已有被选取的控件，将选取包含此控件的区域。</li><li>方向键 : 已有被选取的控件时，可以利用方向键移到别的区域。</li></ul>',
        'attribute' => '除控件之外的各个区域都可以对其指定背景色/背景图片及字体颜色(包括a标签)。',

    );
	$lang->mobile_layout_list = "移动版布局目录";
	$lang->mobile_downloaded_list = "移动版布局下载目录";
	$lang->apply_mobile_view = "移动版布局共享";
	$lang->about_apply_mobile_view = "勾选表示连接到此布局的所有菜单项全部采用此移动版布局。";
?>
