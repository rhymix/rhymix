<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for XE
     **/

    $lang->introduce_title = 'Установка XE';
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
    $lang->install_condition_title = "Пожалуйста, проверьте требования к установке.";
    $lang->install_checklist_title = array(
			'php_version' => 'Версия PHP',
            'permission' => 'Права доступа',
            'xml' => 'XML библиотека',
            'iconv' => 'ICONV библиотека',
            'gd' => 'GD библиотека',
            'session' => 'Session.auto_start настройка',
            'db' => 'DB',
        );

    $lang->install_checklist_desc = array(
			'php_version' => '[Требуется] Если версия PHP равна 5.2.2, то XE не будет установлена из-за бага',
            'permission' => '[Требуется] Путь установки XE или директория ./files должна иметь права доступа 707',
            'xml' => '[Требуется] XML Библиотека нужна для XML коммуникации',
            'session' => '[Требуется] Файл настроек PHP (php.ini) \'Session.auto_start\' должен быть равен нулю, чтобы XE могла использовать сессии',
            'iconv' => 'Iconv должна быть установлена для конвертирования между UTF-8 и иными языковыми кодировками',
            'gd' => 'GD Библиотека должна быть установлена для использования функции конвертироваия изображений',
        );

    $lang->install_checklist_xml = 'Установить XML библиотеку';
    $lang->install_without_xml = 'XML библиотека не установлена';
    $lang->install_checklist_gd = 'Установить GD библиотеку';
    $lang->install_without_gd  = 'GD библиотека не установлена';
    $lang->install_checklist_gd = 'Установить GD библиотеку';
    $lang->install_without_iconv = 'Iconv библиотека не установлена';
    $lang->install_session_auto_start = 'Возможно возникнут проблемы из-за настройки PHP session.auto_start, установленной в 1';
    $lang->install_permission_denied = 'Права доступа пути не установлены в 707';

    $lang->cmd_agree_license = 'Я согласен с данной лицензией';
    $lang->cmd_install_fix_checklist = 'Я удоволетворил требуемые условия';
    $lang->cmd_install_next = 'Продолжить установку';
    $lang->cmd_ignore = 'Ignore';

    $lang->db_desc = array(
        'mysql' => 'Используем mysql*() функцию, чтобы использовать базу данных mysql.<br />Транзакция отключена из-за того, что файл базы данных создан посредством myisam.',
        'mysqli' => 'Используем mysqli*() функцию, чтобы использовать базу данных mysql.<br />Транзакция отключена из-за того, что файл базы данных создан посредством myisam.',
        'mysql_innodb' => 'Используем innodb  чтобы использовать базу данных mysql.<br />Транзакция включена для innodb',
        'sqlite2' => 'Поддерживает sqlite2, которая сохраняет данные в файл.<br />Устанавливая, следует размещать файл базы данных в недоступном с веб месте.<br />(Никогда не тестировалось на стабильность)',
        'sqlite3_pdo' => 'Поддерживает sqlite3 посредством PHP\'s PDO.<br />Устанавливая, следует размещать файл базы данных в недоступном с веб месте.',
        'cubrid' => 'Используем CUBRID DB. <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => 'Используем MSSQL DB.',
        'postgresql' => 'Используем PostgreSql DB.',
        'firebird' => 'Используем firebird DB.',
    );

    $lang->form_title = 'Пожалуйста, введите дазу данных &amp; Административная Информация';
    $lang->db_title = 'Пожалуйста, введите информацию базы данных';
    $lang->db_type = 'Тип базы данных';
    $lang->select_db_type = 'Пожалуйста, выберите базу данных, которую Вы хотите использовать.';
    $lang->db_hostname = 'Хост базы данных';
    $lang->db_port = 'Порт базы данных';
    $lang->db_userid = 'ID базы данных';
    $lang->db_password = 'Пароль базы данных';
    $lang->db_database = 'Имя базы данных';
    $lang->db_database_file = 'Файл базы данных';
    $lang->db_table_prefix = 'Префикс таблиц';

    $lang->admin_title = 'Административная информация';

    $lang->env_title = 'Конфигурация';
    $lang->use_optimizer = 'Включить оптимизатор';
    $lang->about_optimizer = 'Если оптимизатор включен, пользователи могут быстро использовать этот сайт, поскольку несколько CSS / JS файлов собраны вместе и сжаты до передачи. <br /> Тем не менее, эта оптимизация может быть проблематичной согласно CSS или JS. Если Вы выключите ее, движок будет работать правильно, хотя и медленее.';
    $lang->use_rewrite = 'Использовать<br /> модуль перезаписи<br />(rewrite mod)';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = "Если сервер предлагает rewrite mod, длинные URL такие как  http://blah/?document_srl=123 могут быть сокращены до http://blah/123";
	$lang->about_sso = '사용자가 한 번만 로그인하면 기본 사이트와 가상 사이트에 동시에 로그인이 되는 기능입니다. 가상 사이트를 사용할 때만 필요합니다.';
    $lang->time_zone = 'Часовой пояс';
    $lang->about_time_zone = "Если серверное время и Ваше локальное время не совпадают, Вы можете установить такое же время, как Ваше локальное, используя часовой пояс";
    $lang->qmail_compatibility = 'Qmail 호환';
    $lang->about_qmail_compatibility = 'Qmail등 CRLF를 줄 구분자로 인식하지 못하는 MTA에서 메일이 발송되도록 합니다.';
    $lang->about_database_file = 'Sqlite сохраняет данные в файл. Размещение базы данных должно быть недоступно с веб<br/><span style="color:red">Файл базы данных должен иметь права доступа 707.</span>';
    $lang->success_installed = 'Установка завершена';
    $lang->msg_cannot_proc = 'Невозможно исполнить запрос, поскольку окружение установки не указано';
    $lang->msg_already_installed = 'XE уже установлена';
    $lang->msg_dbconnect_failed = "Произошла ошибка подключения к базе данных.\nПожалуйста, проверьте иформацию базы данных еще раз";
    $lang->msg_table_is_exists = "Таблица существует в базе данных.\nФайл конфигурации создан заново";
    $lang->msg_install_completed = "Установка завершена.\nСпасибо Вам за выбор XE";
    $lang->msg_install_failed = "Произошла ошибка при создании файла конфигурации.";
    $lang->ftp_get_list = 'Get List';
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
