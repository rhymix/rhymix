<?php
    /**
     * @file   modules/document/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  Document module's basic language pack
     **/

    $lang->document_list = 'Belge Listesi';
    $lang->thumbnail_type = 'Küçük Resim Türü';
    $lang->thumbnail_crop = 'Kırpılmış';
    $lang->thumbnail_ratio = 'Oran';
    $lang->cmd_delete_all_thumbnail = 'Tüm küçük resimleri sil';
    $lang->move_target_module = "Hedef modül ";
    $lang->title_bold = 'Kalın';
    $lang->title_color = 'Renk';
    $lang->new_document_count = 'Yeni belgeler';

    $lang->parent_category_title = 'Üst Kategori';
    $lang->category_title = 'Kategori';
    $lang->category_color = 'Kategori Yazı Rengi';
    $lang->expand = 'Genişlet';
    $lang->category_group_srls = 'Erişilebilir Grup';

    $lang->cmd_make_child = 'Alt Kategori Ekle';
    $lang->cmd_enable_move_category = "Kategori konumunu değiştir (Seçtikten sonra anamenüyü taşıyınız)";

    $lang->about_category_title = 'Lütfen kategori ismi giriniz';
    $lang->about_expand = 'Bu seçimle, her zaman genişletilmiş olacaktır';
    $lang->about_category_group_srls = 'Sadece seçilen grup geçerli kategoriyi kullanabilecektir';
    $lang->about_category_color = 'Kategorinin yazı rengini ayarlayabilirsiniz.';

    $lang->cmd_search_next = 'Sonrakini Ara';

    $lang->cmd_temp_save = 'Geçiçi olarak Kaydet';

	$lang->cmd_toggle_checked_document = 'Seçili ögeleri ters çevir';
    $lang->cmd_delete_checked_document = 'Seçilenleri Sil';
    $lang->cmd_document_do = 'Şunu yap';

    $lang->msg_cart_is_null = 'Lütfen silinecek makaleleri seçiniz';
    $lang->msg_category_not_moved = 'Taşınamıyor';
    $lang->msg_is_secret = 'Bu gizli bir makaledir';
    $lang->msg_checked_document_is_deleted = '%d makale silinmiştir';

    // Search targets in admin page
        $lang->search_target_list = array(
        'title' => 'Konu',
        'content' => 'İçerik',
        'user_id' => 'Kullanıcı Kimliği',
        'member_srl' => 'Üye Seri Numarası',
        'user_name' => 'Kullanıcı İsmi',
        'nick_name' => 'Rumuz',
        'email_address' => 'Email',
        'homepage' => 'Anasayfa',
        'is_notice' => 'Bildirim',
        'is_secret' => 'Gizli',
        'tags' => 'Etiket',
        'readed_count' => 'Gösterim Sayısı (over)',
        'voted_count' => 'Oylama Sayısı (over)',
        'comment_count ' => 'Yorum Sayısı (over)',
        'trackback_count ' => 'Geri İzleme Sayısı (over)',
        'uploaded_count ' => 'Eklerin Sayısı (over)',
        'regdate' => 'Tarih',
        'last_update' => 'Son Güncelleme Tarihi',
        'ipaddress' => 'IP Adresi',
    );

    $lang->alias = "Diğer Adıyla";
    $lang->history = "Geçmiş";
    $lang->about_use_history = "Geçmiş, belgeleri önceki değişikliklerine dönüştürmek içindir.";
    $lang->trace_only = "Sadece izleme";

    $lang->cmd_trash = 'Çöp Kutusu';
    $lang->cmd_restore = 'Geri Yükleme';
    $lang->cmd_restore_all = 'Hepsini Geri Yükle';

    $lang->in_trash = 'Çöp Kutusu';
    $lang->trash_nick_name = 'Silici';
    $lang->trash_date = 'Silinme Tarihi';
    $lang->trash_description = 'Açıklama';

	$lang->search_target_trash_list = array(
        'title' => 'Başlık',
        'content' => 'İçerik',
        'user_id' => 'Kullanıcı Kimliği',
        'member_srl' => 'Üye Diziseli',
        'user_name' => 'Kullanıcı İsmi',
        'nick_name' => 'Rumuz',
        'trash_member_srl' => 'Silici Diziseli',
        'trash_user_name' => 'Silici İsmi',
        'trash_nick_name' => 'Silici rumuzu',
        'trash_date' => 'Silinme Tarihi',
        'trash_ipaddress' => 'Silici IP adresi',
    );

    $lang->success_trashed = "Başarıyla silindi";
    $lang->msg_not_selected_document = 'Hiçbir makale seçilmedi.';
	$lang->show_voted_member = '사용자 노출';
?>
