<?php
    /**
     * @file   modules/file/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  Attachment module's basic language pack
     **/

    $lang->file = 'Ek';
    $lang->file_name = 'Dosya Adı';
    $lang->file_size = 'Dosya Boyutu';
    $lang->download_count = 'İndirme Sayısı';
    $lang->status = 'Durum';
    $lang->is_valid = 'Geçerli';
    $lang->is_stand_by = 'Askıya al';
    $lang->file_list = 'Eklentiler Listesi';
    $lang->allow_outlink = 'Dış Bağlantı';
    $lang->allow_outlink_site = 'İzin Verilmiş Siteler';
    $lang->allow_outlink_format = 'İzin Verilmiş Biçimler';
    $lang->allowed_filesize = 'En büyük dosya boyutu';
    $lang->allowed_attach_size = 'En büyük ek boyutu';
    $lang->allowed_filetypes = 'İzin Verilmiş Uzantılar';
    $lang->enable_download_group = 'İzin Verilen Grupları İndir';

    $lang->about_allow_outlink = 'Dış bağlantıları kapatabilirsiniz. (*.wmv, *.mp3 gibi ortam dosyaları hariç)';
    $lang->about_allow_outlink_format = 'Bu biçimlere her zaman izin verilecektir. Lütfen çoklu giriş için virgül(,) kullanınız.<br />örn.)hwp,doc,zip,pdf';
    $lang->about_allow_outlink_site = 'Bu sitelere her zaman izin verilecektir. Lütfen çoklu giriş için yeni satır kullanınız.<br />örn.)http://xpressengine.com/';
	$lang->about_allowed_filesize = 'Her dosya için dosya boyut limiti atayabilirsiniz. (Yöneticiler için limitsizdir)';
    $lang->about_allowed_attach_size = 'Her belge için dosya boyutu limiti atayabilirsiniz. (Yöneticiler için limitsizdir)';
    $lang->about_allowed_filetypes = 'Sadece izin verilen uzantılar iliştirilebilir. Bir uzantıya izin vermek için, "*.[uzantı]" komutunu kullanınız. Birden fazla eklentiye izin vermek için, her uzantının arasına ";" koyunuz.<br />örn.) *.* veya *.jpg;*.gif;<br />(Yöneticilerin tercihleri sınırlandırılmaz)';

    $lang->cmd_delete_checked_file = 'Seçilen Parça(lar) Silinsin';
    $lang->cmd_move_to_document = 'Belgeye Taşı';
    $lang->cmd_download = 'İndir';

    $lang->msg_not_permitted_download = 'İndirmek için izniniz yok';
    $lang->msg_cart_is_null = 'Lütfen silenecek dosya(ları) seçiniz';
    $lang->msg_checked_file_is_deleted = '%d ek(ler)i silindi';
    $lang->msg_exceeds_limit_size = 'Ek dosya boyutu, izin verilen boyuttan büyük.';
    $lang->msg_file_not_found = 'İstenilen dosya bulunamadı.';


    $lang->file_search_target_list = array(
        'filename' => 'Dosya Adı',
        'filesize_more' => 'Dosya Boyutu (byte, üstü)',
        'filesize_mega_more' => 'Dosya Boyutu (mbyte, üstü)',
		'filesize_less' => 'Dosya Boyutu (byte, düşük)',
		'filesize_mega_less' => 'Dosya Boyutu (Mb, düşük)',
        'download_count' => 'İndirmeler (daha fazla)',
        'regdate' => 'Kayıt Zamanı',
        'user_id' => 'Kullanıcı Kimliği',
        'user_name' => 'Kullanıcı İsmi',
        'nick_name' => 'Rumuz',
        'ipaddress' => 'IP Adresi',
    );
	$lang->msg_not_allowed_outlink = 'Bu siteden dosya indirimine izin verilmemektedir.'; 
    $lang->msg_not_permitted_create = 'Dosya veya dizin oluşturma hatası.';
	$lang->msg_file_upload_error = 'Karşıya yükleme esnasında bir hata oluştu.';

?>
