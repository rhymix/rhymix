<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Importer(importer) 模块的基本语言包
     **/

    // 按钮上使用的语言
    $lang->cmd_sync_member = '同步化';
    $lang->cmd_continue = '继续进行';

    // 项目
    $lang->importer = 'zeroboard数据转移';
    $lang->source_type = '转移 对象';
    $lang->type_member = '会员 信息';
    $lang->type_module = '论坛 信息';
    $lang->type_syncmember = '会员信息同步化';
    $lang->target_module = '目标 模块';
    $lang->xml_file = 'XML 文件';

    $lang->import_step_title = array(
        1 => 'Step 1. 选择 转移 对象',
        12 => 'Step 1-2. 选择 目标 模块',
        13 => 'Step 1-3. 选择 目标 分类',
        2 => 'Step 2. 上传 XML 文件',
        3 => 'Step 2. 会员信息和文章信息同步化',
    );

    $lang->import_step_desc = array(
        1 => '请选择要转移的XML文件种类。',
        12 => '请选择转移的对象模块。',
        13 => '请选择数据转移的对象分类。',
        2 => "请输入转移数据的XML文件的位置。\n同一个主机或绝对路径，上传至另一个服务器的位置请输入http://地址..",
        3 => '数据转移后会员信息和文章内容信息会有误差。这时利用同步化纠正。',
    );

    // 信息/提示
    $lang->msg_sync_member = '按同步化按钮开始同步化会员信息和文章信息。';
    $lang->msg_no_xml_file = '找不到XML文件，请再次确认路径。';
    $lang->msg_invalid_xml_file = '错误形式的 XML文件';
    $lang->msg_importing = '%d个的数据中正在输入 %d个。 （长时间没有反映时请按“继续进行”按钮）';
    $lang->msg_import_finished = '已完成输入%d个数据。情况的不同有可能没有输入的数据。';
    $lang->msg_sync_completed = '已完成会员和文章，评论的同步化';

    // 주절 주절..
    $lang->about_type_member = '数据转移对象是会员的情况请选择';
    $lang->about_type_module = '数据转移对象是board文章的情况请选择';
    $lang->about_type_syncmember = '会员信息和文章信息转移后同步会员信息时选择。';
    $lang->about_importer = "把zeroboard 4，zb5beta或其他的程序数据也可以转移到zeroboard XE.\n为了转移利用 <a href=\"#\" onclick=\"winopen('');return false;\">XML Exporter</a>生成XML文件后上传。";
?>
