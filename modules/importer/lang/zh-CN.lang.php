<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Importer(importer) 模块语言包
     **/

    // 按钮上使用的语言
    $lang->cmd_sync_member = '同步';
    $lang->cmd_continue = '继续进行';
    $lang->preprocessing = '데이터 이전을 위한 사전 준비중입니다.';

    // 项目
    $lang->importer = '数据导入';
    $lang->source_type = '导入对象';
    $lang->type_member = '会员信息';
    $lang->type_message = '短信息(MemoBox)';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = '版面信息';
    $lang->type_syncmember = '同步会员信息';
    $lang->target_module = '模块对象';
    $lang->xml_file = 'XML文件';

    $lang->import_step_title = array(
        1 => 'Step 1. 选择导入对象',
        12 => 'Step 1-2. 选择模块对象',
        13 => 'Step 1-3. 选择对象分类',
        2 => 'Step 2. 上传XML文件',
        3 => 'Step 2. 同步会员信息和文章信息',
    );

    $lang->import_step_desc = array(
        1 => '请选择要导入的XML文件种类。',
        12 => '请选择要导入的对象模块。',
        13 => '请选择要导入的对象分类。',
        2 => "请输入要导入的XML文件的位置。\n位于同一个服务器时，可输入相对或绝对路径，位于不同服务器时，可输入http://地址。",
        3 => '数据导入后可能会导致会员信息和文章内容信息的误差。这时以user_id进行同步即可解决问题。',
    );

    // 信息/提示
    $lang->msg_sync_member = '按同步按钮即可开始对会员信息和文章信息进行同步。';
    $lang->msg_no_xml_file = '找不到XML文件，请重新确认路径。';
    $lang->msg_invalid_xml_file = '错误形式的XML文件！';
    $lang->msg_importing = '%d个的数据中正在输入 %d个。 （长时间没有响应时请按“继续进行”按钮）';
    $lang->msg_import_finished = '已完成输入%d/%d个数据。根据情况的不同可能有没有被导入的数据。';
    $lang->msg_sync_completed = '已完成会员和文章，评论的同步。';

    // 说明
    $lang->about_type_member = '数据导入对象为会员信息时请选择此项。';
    $lang->about_type_message = '数据导入对象为短信息(MemoBox)时请选择此项。';
    $lang->about_type_ttxml = '数据导入对象为TTXML(textcube系列)时请选择此项。';
    $lang->about_ttxml_user_id = '请输入导入TTXML数据时指定为主题发布者的ID(必须是已注册会员)。';
    $lang->about_type_module = '数据导入对象为版面主题时请选择此项。';
    $lang->about_type_syncmember = '导入会员信息和文章信息后需要同步会员信息时请选择此项。';
    $lang->about_importer = "不仅可以导入Zeroboard 4，Zb5beta的数据,也可以把其他程序数据导入到Zeroboard XE当中。\n导入数据时请利用 <a href=\"http://svn.zeroboard.com/zeroboard_xe/migration_tools/\" onclick=\"winopen(this.href);return false;\">XML Exporter</a>生成XML文件后再上传。";

    $lang->about_target_path = "为了下载附件请输入Zeroboard 4的安装位置。\n位置在同一个服务器时，请输入如 /home/id/public_html/bbs的路径,在不同服务器时，请输入如 http://域名/bbs的url地址。";
?>
