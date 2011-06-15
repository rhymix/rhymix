<?php
    /**
     * @file   modules/install/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa(risapapa@gmail.com)、ミニミ
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->introduce_title = 'XEのインストール';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
    $lang->install_condition_title = "インストールするための必須条件を確認して下さい。";
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);
    $lang->install_checklist_title = array(
            'php_version' => 'PHPバージョン',
            'permission' => 'パーミッション',
            'xml' => 'XMLライブラリ',
            'iconv' => 'ICONVライブラリ',
            'gd' => 'GDライブラリ',
            'session' => 'Session.auto_startの設定',
        );

	$lang->install_license_desc = array(
			'lgpl' => 'GNU 약소 일반 공중 사용 허가서(LGPL v2)에 동의해야 합니다.'
		);
    $lang->install_checklist_desc = array(
            'php_version' => '【必須】PHPバージョンが 5.2.2の場合は、PHPのセキュリティバグのため、インストール出来ません。',
            'permission' => '【必須】XEのインストールパスまたは「./files」ディレクトリのパーミッションを「707」に設定して下さい。',
            'xml' => '【必須】XML通信のためにXMLライブラリが必要です',
            'session' => '【必須】XEでは、セッションを使用しているため、「php.ini」の設定を「session.auto_start=0」にして下さい。',
            'iconv' => 'UTF-8と多言語サポート及び文字コード変換のため、「iconv」をインストールする必要があります。',
            'gd' => 'イメージ変換機能を使用するためには、「GDライブラリ」をインストールする必要があります。',
        );

    $lang->install_checklist_xml = 'XMLライブラリのインストール';
    $lang->install_without_xml = 'XMLライブラリがインストールされていません。';
    $lang->install_checklist_gd = 'GDライブラリのインストール';
    $lang->install_without_gd  = 'イメージ変換用のGDライブラリがインストールされていません。';
    $lang->install_checklist_gd = 'GDライブラリのインストール';
    $lang->install_without_iconv = '文字列処理のための「iconv」ライブラリがインストールされていません。';
    $lang->install_session_auto_start = 'PHPの設定で「session.auto_start==1」 にするとセッション処理に問題が発生することがあります。';
    $lang->install_permission_denied = 'インストールする対象ディレクトリのパーミッションが「707」になっていません。';

    $lang->cmd_agree_license = 'ライセンスに同意します。';
    $lang->cmd_install_fix_checklist = 'インストール必須条件を設定しました。';
    $lang->cmd_install_next = 'インストールを続けます。';
    $lang->cmd_ignore = 'FTP設定を省略する';

    $lang->db_desc = array(
        'mysql' => 'MySQL DBでPHPの「mysql*()」関数を利用してデータの入出力を行います。<br />DBは「myisam」タイプで作成されるため、トランザクション処理は出来ません。',
        'mysqli' => 'MySQL DBでPHPの「mysqli*()」関数を利用してデータの入出力を行います。<br />DBは「myisam」タイプで作成されるため、トランザクション処理は出来ません。',
        'mysql_innodb' => 'MySQL DBで「innodb」タイプでデータの入出力を行います。<br />「innodb」ではトランザクションの処理が行えます。',
        'sqlite2' => 'ファイルタイプデータベースである「sqlite2」をサポートします。<br />インストール時、セキュリティのため、DBファイルはウェブがらアクセス出来ない場所に作成して下さい。<br />（安定化までのテストは行われていません）',
        'sqlite3_pdo' => 'PHPのPDOを経由うして「sqlite3」をサポートします。<br />インストール時、セキュリティのため、DBファイルはウェブからアクセス出来ない場所に作成して下さい。',
        'cubrid' => 'CUBRID DBを利用します。 <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => 'MSSQL DBを利用します。',
        'postgresql' => 'PostgreSql DBを利用します。',
        'firebird' => 'Firebird DBを利用します。<br />DB生成方法 (create database "/path/dbname.fdb" page_size=8192 default character set UTF8;)',
    );

    $lang->form_title = 'データベース &amp; 管理者情報入力';
    $lang->db_title = 'データベース情報入力';
    $lang->db_type = 'データベースの種類';
    $lang->select_db_type = '使用するデータベース種類を選択して下さい。';
    $lang->db_hostname = 'ホスト名';
    $lang->db_port = 'ポート番号';
    $lang->db_userid = 'ユーザＩＤ';
    $lang->db_password = 'パスワード';
    $lang->db_database = 'データベース名';
    $lang->db_database_file = 'データベースファイル';
    $lang->db_table_prefix = 'テーブルプレフィックス';

    $lang->admin_title = '管理者情報';

    $lang->env_title = '環境設定';
    $lang->use_optimizer = 'オプティマイザー使用';
    $lang->about_optimizer = 'オプティマイザーを使用すると多数の「CSS/JS」ファイルを、統合・圧縮して転送するのでレスポンスが早くなります。<br />但し、CSSまたはJSファイルによっては問題が生じる場合があります。この場合は、チェックを外すと正常に動作します。';
    $lang->use_rewrite = 'リライト・モジュールを使用';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = 'Webサーバで「リライト・モジュール（mod_rewrite）」をサポートしている場合は、「http://アドレス/?document_srl=123」のようなアドレスを動的だけど「http://アドレス/123」のように静的なページに見せることが出来ます。';
	$lang->about_sso = 'ユーザが一度のログインで基本サイトと仮想サイトに同時にログインされる機能です。仮想サイトの機能を使用してない場合、設定する必要がありません。';
    $lang->time_zone = 'タイムゾーン';
    $lang->about_time_zone = 'サーバの設定時間とサービスしているローカル時間との差がある場合、タイムゾーンを指定して表示時間を合わせることが出来ます。';
    $lang->qmail_compatibility = 'Qmail 互換';
    $lang->about_qmail_compatibility = 'Qmail等、CRLFを改行コードとして認識出来ないMTA（Message Transfer Agent）で、メールの送信が出来るようにします。';
    $lang->about_database_file = 'Sqliteはファイルにデータを保存します。そのため、データベースファイルにはウェブからアクセス出来ない場所にしなければなりません。<br/><span style="color:red">データファイルのパーミッションは「707」に設定して下さい。</span>';
    $lang->success_installed = '正常にインストールされました。';
    $lang->msg_cannot_proc = 'インストール出来る環境が整っていないため、リクエストを実行出来ませんでした。';
    $lang->msg_already_installed = '既にインストールされています。';
    $lang->msg_dbconnect_failed = "データベースアクセスにエラーが発生しました。\nデータベースの情報をもう一度確認して下さい。";
    $lang->msg_table_is_exists = "既にデータベースにデーブルが作成されています。\nconfigファイルを再作成しました。";
    $lang->msg_install_completed = "インストールが完了しました。\nありがとうございます。";
    $lang->msg_install_failed = 'インストールファイルを作成する際にエラーが発生しました。';
    $lang->ftp_get_list = "Get List";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.ja.html';
?>
