<?php
    /**
     * @file   en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English language pack (Only basic contents are listed)
     **/

    $lang->introduce_title = 'XE Installation';
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
    $lang->install_condition_title = "Lütfen kurulum gereksinimlerini kontrol ediniz.";
    $lang->install_checklist_title = array(
			'php_version' => 'PHP Sürümü',
            'permission' => 'Yetki',
            'xml' => 'XML Kitaplığı',
            'iconv' => 'ICONV Kitaplığı',
            'gd' => 'GD Kitaplığı',
            'session' => 'Session.auto_start(otomatik.oturum_acma) ayarı',
            'db' => 'DB',
        );

    $lang->install_checklist_desc = array(
			'php_version' => '[Gerekli] Eğer PHP sürümü 5.2.2 ise, XE yazılım hatasından dolayı kurulmayacaktır',
            'permission' => '[Gerekli] XE kurulum yolu ya da ./files directory yolunun yetkisi 707 olmalıdır',
            'xml' => '[Gerekli] XML iletişimi için XML kitaplığı gereklidir.',
            'session' => '[Gerekli] PHP ayar dosyasındaki (php.ini) \'Session.auto_start\' XE\'nin oturumu kullanabilmesi için sıfıra eşit olmalıdır',
            'iconv' => 'Iconv, UTF-8 ve diğer dil ayarlarını değiştirebilmek için kurulmuş olmalıdır',
            'gd' => 'GD Kitaplığı, resim değiştirme özelliğini kullanabilmek için kurulmuş, olmalıdır',
        );

    $lang->install_checklist_xml = 'XML Kitaplığını Kur';
    $lang->install_without_xml = 'XML Kitaplığı kurulmamış';
    $lang->install_checklist_gd = 'GD Kitaplığı Kur';
    $lang->install_without_gd  = 'GD Library, resim dönüştürmek için, kurulmamış';
    $lang->install_checklist_gd = 'GD Kitaplığını Kur';
    $lang->install_without_iconv = 'Iconv Kitaplığı, karakterleri sıralamak için, kurulmamış';
    $lang->install_session_auto_start = 'Olası hatalar php ayarlarından dolayı oluşabilir. session.auto_start 1\'e eşit olmalıdır';
    $lang->install_permission_denied = 'Kurulum yolu yetkisi 707\'ye eşit değil';

    $lang->cmd_agree_license = 'Lisansı kabul ediyorum';
    $lang->cmd_install_fix_checklist = 'Gerekli koşulları tamamladım.';
    $lang->cmd_install_next = 'Kuruluma Devam Et';
    $lang->cmd_ignore = 'Önemseme';

    $lang->db_desc = array(
        'mysql' => 'PHP\'de mysql*() özellikleri için MySQL\'ü veritabanı olarak kullanınız.<br />İşlemler, veritabanı dosyası myisam \'da oluşturulduğu zaman işlenmeyecektir.',
        'mysqli' => 'PHP\'de mysqli*() özellikleri için MySQL\'ü veritabanı olarak kullanınız.<br />İşlemler, veritabanı dosyası myisam \'da oluşturulduğu zaman işlenmeyecektir.',
        'mysql_innodb' => 'innodb ile MySQL\'ü veritabanı olrak kullanınız.<br />İşlemler, innodb ile işlenecektir',
        'sqlite2' => '\'Verileri dosya olarak kaydeden sqlite2 \'yi veritabanı olarak kullanınız. <br />VT dosyası tarayıcıdan erişilebilir <b>olmamalıdır</b>.<br />(Sabitleme için hiç test edilmedi)',
        'sqlite3_pdo' => 'PHP\'nin PDO\'sunun desteğiyle sqlite3\'ü veritabanı olarak kullanınız.<br />VT dosyası tarayıcıdan erişilebilir <b>olmamalıdır</b>.',
        'cubrid' => 'CUBRID\'ü veritabanı olarak kullanın. Daha fazla bilgi için <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manuel</a>i inceleyiniz',
        'mssql' => 'MSSQL\'ü veritabanı olarak kullanın',
        'postgresql' => 'PostgreSql\'ü veritabanı olarak kullanın.',
        'firebird' => 'Firebird\'ü veritabanı olarak kullanın.<br /> (create database "/path/dbname.fdb" page_size=8192 default character set UTF-8;) ile veritabanı oluşturabilirsiniz',
    );

    $lang->form_title = 'Veritabanı &amp; Yönetici Bilgisi';
    $lang->db_title = 'Lütfen Veritabanı bilgisini giriniz';
    $lang->db_type = 'Veritabanı Tipi';
    $lang->select_db_type = 'Lütfen kullanmak istediğiniz Veritabanını seçiniz.';
    $lang->db_hostname = 'Veritabanı Sunucuadı';
    $lang->db_port = 'Veritabanı Portu';
    $lang->db_userid = 'Veritabanı ID';
    $lang->db_password = 'Veritabanı Şifresi';
    $lang->db_database = 'DB Database';
    $lang->db_database_file = 'DB Database File';
    $lang->db_table_prefix = 'Tablo Başlığı';

    $lang->admin_title = 'Yönetici Bilgisi';

    $lang->env_title = 'Yapılandırma';
    $lang->use_optimizer = 'Optimizasyonu Etkinleştir';
    $lang->about_optimizer = 'Eğer Optimizasyon etkinleştirildiyse, çoklu CSS / JS dosyaları gönderimden önce sıkıştırılıp bir araya konulduğundan, kullanıcılar siteye hızlı bir şekilde ulaşacaktır. <br /> Ancak;  bu optimizasyon, CSS ve JS\'ye göre sorunlu olabilir. Eğer bunu devre dışı bırakırsanız, düzgün bir şekilde çalışmasına karşın daha yavaş çalışacaktır.';
    $lang->use_rewrite = 'YenidenYazma Modu (mod_rewrite)';
    $lang->use_sso = 'Tekli Oturum Açma';
    $lang->about_rewrite = "Eğer websunucusu yenidenyazma(rewritemod) destekliyorsa, http://ornek/?dosya_no=123 gibi URLler http://ornek/123 olarak kısaltılabilir";
	$lang->about_sso = 'SSO kullanıcıları, geçreli ya da sanal siteye bir kere kayıt olmakla, ikisinden de yararlandıracaktır. Bu, size sadece sanal websiteler kullandığınız durumda lazım olacaktır.';
    $lang->time_zone = 'Zaman Dilimi';
    $lang->about_time_zone = "Eğer sunucu zaman dilimi ve bulunduğunuz yerin zaman dilimi uyumlu değilse; zaman dilimi özelliğini kullanarak zamanı bulunduğunuz yere göre ayarlayabilirsiniz ";
    $lang->qmail_compatibility = 'Qmail\'i Etkinleştir';
    $lang->about_qmail_compatibility = 'Bu size QMail gibi CRLF\'den ayırt edilemeyen MTA\'dan mail gönderme imkanı sağlayacaktır.';
    $lang->about_database_file = 'Sqlite veriyi dosyaya kaydeder. Veritabanı dosyası tarayıcıyla erişilebilir olmamalıdır.<br/><span style="color:red">Veri dosyası 707 yetki kapsamı içinde olmalıdır.</span>';
    $lang->success_installed = 'Kurulum tamamlandı';
    $lang->msg_cannot_proc = 'Kurulum ortamı devam etmek için uygun değil.';
    $lang->msg_already_installed = 'XE zaten kurulmuştur';
    $lang->msg_dbconnect_failed = "VT\'ye ulaşırken bir hata oluştu.\nLütfen VT bilgisini tekrar kontrol ediniz";
    $lang->msg_table_is_exists = "Tablo zaten VT\'da oluşturuldu.\nYapılandırma dosyası yeniden oluşturuldu";
    $lang->msg_install_completed = "Kurulum tamamlandı.\nXE\'yi seçtiğiniz için teşekkür ederiz";
    $lang->msg_install_failed = "Kurulum dosyası oluşturulurken bir hata oluştu.";
    $lang->ftp_get_list = "Liste Al";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
