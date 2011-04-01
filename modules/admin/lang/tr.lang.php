<?php
    /**
     * @file   en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English Language Pack (Only basic words are included here)
     **/

    $lang->admin_info = 'Yönetici Bilgisi';
    $lang->admin_index = 'Indeks Yönetici Sayfası';
    $lang->control_panel = 'Dashboard';
    $lang->start_module = 'Varsayılan Modül';
    $lang->about_start_module = 'Sitenin varsayılan modülünü belirleyebilirsiniz.';

    $lang->module_category_title = array(
        'service' => 'Hizmetler',
        'member' => 'Yöneticiler',
        'content' => 'İçerikler',
        'statistics' => 'İstatistikler',
        'construction' => 'Yapı',
        'utility' => 'Yardımcı Uygulamalar',
        'interlock' => 'Gömülü',
        'accessory' => 'Donatılar',
        'migration' => 'Veri Geçişi',
        'system' => 'Sistem Ayarları',
    );

    $lang->newest_news = "Son Gelişmeler";
    
    $lang->env_setup = "Ayarlar";
    $lang->default_url = "Varsayılan URL";
    $lang->about_default_url = "Eğer sanal site özelliği kullanıyorsanız (örneğin, cafeXE), varsayılan URL girdisini yapınız (üst-sitenin adresi), SSO etkinleştirilecektir, böylece belgelere/modüllere sağlanan bağlantı uygun bir şekilde çalışacaktır. ";

    $lang->env_information = "Ortam Bilgisi";
    $lang->current_version = "Güncel Sürüm";
    $lang->current_path = "Yükleme Yolu";
    $lang->released_version = "Son Sürüm";
    $lang->about_download_link = "Zeroboard XE'nin yeni sürümü yayımlandı!\nLütfen son sürümü için indirme linkine tıklayınız.";
    
    $lang->item_module = "Modül Listesi";
    $lang->item_addon  = "Eklenti Listesi";
    $lang->item_widget = "Widget Listesi";
    $lang->item_layout = "Yerleşim Düzeni Listesi";

    $lang->module_name = "Modül İsmi";
    $lang->addon_name = "Eklenti İsmi";
    $lang->version = "Sürüm";
    $lang->author = "Geliştirici";
    $lang->table_count = "Tablo Numarası";
    $lang->installed_path = "Yükleme Yolu";

    $lang->cmd_shortcut_management = "Menü Düzenle";

    $lang->msg_is_not_administrator = 'Sadece Yöneticiler';
    $lang->msg_manage_module_cannot_delete = 'Modüllerin, eklentilerin, yerleşim düzenlerinin, widgetların kısayolları silinemez.';
    $lang->msg_default_act_is_null = 'Kısayol varsayılan yönetici eylemi ayarlanmadığından kayıt edilemiyor.';

    $lang->welcome_to_xe = 'XE Yönetici Sayfasına Hoşgeldiniz';
    $lang->about_admin_page = "Yönetici sayfası hâla geliştirilmektedir,\nClosebeta sürecinde birçok iyi öneriyi kabul ederek gerekli içerikleri ekleyeceğiz.";
    $lang->about_lang_env = "Seçilen dili varsayılan dil olarak uygulamak için, lütfen Kaydet tuşuna basınız.";

    $lang->xe_license = 'XE GPL ile uyumludur';
    $lang->about_shortcut = 'Sık kullanılan modüller listesine kaydedilmiş modüllerin kısayollarını silebilirsiniz.';

    $lang->yesterday = "Dün";
    $lang->today = "Bugün";

    $lang->cmd_lang_select = "Dil";
    $lang->about_cmd_lang_select = "Sadece seçili dillerde hizmet verecektir.";
    $lang->about_recompile_cache = "Gereksiz veya geçersiz önbellek dosyalarını silebilirsiniz.";
    $lang->use_ssl = "SSL Kullan";
    $lang->ssl_options = array(
        'none' => "Hiçbir zaman",
        'optional' => "İsteğe Bağlı",
        'always' => "Her zaman"
    );
    $lang->about_use_ssl = "'İsteği Bağlı' seçiminde; SSL, kayıt olma/bilgi değiştirme gibi eylemler için kullanılacaktır. 'Her zaman' seçiminde, siteniz sadece http yoluyla hizmet verecektir.";
    $lang->server_ports = "Sunucu Bağlantı Noktası (port)";
    $lang->about_server_ports = "Eğer web sunucunuz, HTTP bağlantı noktaları için 80 ya da HTTPS 443 portunu kullanmıyorsa, sunucu bağlantı noktalarını belirtmeniz gerekmektedir.";
    $lang->use_db_session = 'Oturum Veritabanı Kullanımı';
    $lang->about_db_session = 'Yetersiz web sunucusu kullanımı olan websiteleri için, bu özellik devredışı bırakıldığı zaman daha hızlı bir tepki beklenebilir.<br/>Ancak oturum veritabanı, mevcut kullanıcılar için veritabanını erişilemez hâle getirecektir ve ilgili işler kullanılamaz hale gelecektir.';
    $lang->sftp = "SFTP Kullan";
    $lang->ftp_get_list = "Listeyi Al";
    $lang->ftp_remove_info = 'FTP Bilgisini Sil.';
	$lang->msg_ftp_invalid_path = 'Belirtilen FTP Yolunu okuma işlemi başarız oldu.';
	$lang->msg_self_restart_cache_engine = 'Lütfen önbellek geri plan yordamını veya Memcached\' ı yeniden başlatınız.';
	$lang->mobile_view = 'Hareketli Görünümü';
	$lang->about_mobile_view = 'Hareketli görünümü, mobil cihazlarla giriş yapılırken, mobil cihazlara uygun en iyi yerleşim düzenini göstermek içindir.';
    $lang->autoinstall = 'KolayKurulum';

    $lang->last_week = 'Geçen Hafta';
    $lang->this_week = 'Bu Hafta';
?>
