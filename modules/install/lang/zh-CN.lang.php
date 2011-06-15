<?php
    /**
     * @file   modules/install/lang/zh-CN.lang.php
     * @author NHN (developers@xpressengine.com)  翻译：guny(space.china@gmail.com)
     * @brief  安装模块简体中文语言包
     **/

    $lang->introduce_title = '安装XE';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);

    $lang->install_condition_title = "检测运行环境";

    $lang->install_checklist_title = array(
			'php_version' => 'PHP版本',
            'permission' => '权限',
            'xml' => 'XML库',
            'iconv' => 'ICONV库',
            'gd' => 'GD库',
            'session' => 'Session.auto_start 设置',
            'db' => 'DB',
        );

	$lang->install_license_desc = array(
			'lgpl' => 'GNU 약소 일반 공중 사용 허가서(LGPL v2)에 동의해야 합니다.'
		);
    $lang->install_checklist_desc = array(
	    'php_version' => '[必须] 由于 PHP 5.2.2 版本BUG，无法安装 XE。',
            'permission' => '[必须] 的安装路径或 ./files目录属性必须是707',
            'xml' => '[必须]为了 XML通讯，将需要XML库',
            'session' => '[必须] 为了使用缓冲功能，必须在php.ini当中设置 session.auto_start=0',
            'iconv' => '为了UTF-8和其他语言环境之间的互相转换，必须安装iconv',
            'gd' => '为了使用图片转换功能，必须先得安装GD库',
        );

    $lang->install_checklist_xml = '安装XML库';
    $lang->install_without_xml = '还没有安装xml库！';
    $lang->install_checklist_gd = '安装GD库';
    $lang->install_without_gd  = '还没有安装负责转换图片功能的GD库！';
    $lang->install_checklist_gd = '安装GD库';
    $lang->install_without_iconv = '还没有安装负责处理字串的iconv库！';
    $lang->install_session_auto_start = 'PHP设置中设置成session.auto_start==1，可能处理session时发生错误。';
    $lang->install_permission_denied = '安装目录属性不是707！';

    $lang->cmd_agree_license = '同意';
    $lang->cmd_install_fix_checklist = '已设置了必要的安装条件。';
    $lang->cmd_install_next = '开始安装';
    $lang->cmd_ignore = '忽略';

    $lang->db_desc = array(
        'mysql' => '利用php的 mysql*()函数使用mysql DB。<br />DB数据是以myisam生成，因此不能实现transaction。',
        'mysqli' => '利用php的 mysqli*()函数使用mysql DB。<br />DB数据是以myisam生成，因此不能实现transaction。',
        'mysql_innodb' => '利用innodb使用mysql DB。<br />innodb可以使用transaction。',
        'sqlite2' => '支持用文件形式保存数据的sqlite2。<br />安装时DB文件应在web不能访问的地方生成。<br />(还没有通过安全的测试)',
        'sqlite3_pdo' => '用PHP的 PDO支持 sqlite3。<br />安装时DB文件应在web不能访问的地方生成。',
        'cubrid' => '使用CUBRID DB。 <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => '使用MSSQL DB。',
        'postgresql' => '使用PostgreSql DB。',
        'firebird' => '使用Firebird DB。',
    );

    $lang->form_title = '数据库及管理员基本信息';
    $lang->db_title = '输入数据库信息';
    $lang->db_type = '数据库类型';
    $lang->select_db_type = '选择数据库';
    $lang->db_hostname = '服务器名';
    $lang->db_port = '数据库端口';
    $lang->db_userid = 'DB用户名';
    $lang->db_password = 'DB密码';
    $lang->db_database = '数据库名';
    $lang->db_database_file = '数据库文件';
    $lang->db_table_prefix = '前缀';
    $lang->admin_title = '管理员信息';
    $lang->env_title = '环境设置';
    $lang->use_optimizer = '使用Optimizer';
    $lang->about_optimizer = '使用Optimizer可以对大部分的CSS/ JS文件进行整合/压缩传送使之加快网站访问速度。<br />只是有时会发生小小的问题。这时候请暂时不要使用Optimizer。';
    $lang->use_rewrite = '使用rewrite模块';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = '如服务器支持rewrite模块且选择此项，可以简化复杂的网址。<br />例如，http://域名/?document_srl=123简化为http://域名/123。';
	$lang->about_sso = '사용자가 한 번만 로그인하면 기본 사이트와 가상 사이트에 동시에 로그인이 되는 기능입니다. 가상 사이트를 사용할 때만 필요합니다.';
    $lang->time_zone = '时区';
    $lang->about_time_zone = '服务器时间和您所处的时间有差异时，可以设置时区来满足你所需要的时间显示。';
    $lang->qmail_compatibility = 'Qmail互换';
    $lang->about_qmail_compatibility = '支持不能识别CRLF为换行符的Qmail等MTA，也能发送电子邮件。';
    $lang->about_database_file = 'Sqlite是文件里保存数据。数据库的文件位置应该放在web不能访问的地方。<br/><span style="color:red">数据文件应放在具有707属性的位置。</span>';
    $lang->success_installed = '已完成安装。';
    $lang->msg_cannot_proc = '不具备安装所需环境，不能继续进行。';
    $lang->msg_already_installed = '已安装';
    $lang->msg_dbconnect_failed = "连接DB时发生错误。\n请重新确认DB信息。";
    $lang->msg_table_is_exists = "已生成数据表。\n重新生成了config文件。";
    $lang->msg_install_completed = "安装完成。\n非常感谢。";
    $lang->msg_install_failed = "生成安装文件时发生错误。";
    $lang->ftp_get_list = '载入FTP列表';
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
