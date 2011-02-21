<?php
    /**
     * @file   modules/editor/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  WYSIWYG Editor module's basic language pack
     **/

    $lang->editor = 'WYSIWYG Editor';
    $lang->component_name = 'Bileşen';
    $lang->component_version = 'Sürüm';
    $lang->component_author = 'Geliştirici';
    $lang->component_link = 'Link';
    $lang->component_date = 'Tarih';
    $lang->component_license = 'Lisans';
    $lang->component_history = 'Güncelleştirmeler';
    $lang->component_description = 'Açıklama';
    $lang->component_extra_vars = 'Değişken Seçenek';
    $lang->component_grant = 'Yetki Ayarı';
    $lang->content_style = 'İçerik Tarzı';
    $lang->content_font = 'İçerik Yazı Tipi';
    $lang->content_font_size = 'İçerik Yazı Boyutu';

    $lang->about_component = 'Bileşen hakkında';
    $lang->about_component_grant = 'Seçilen grup(lar), editörün genişletilmiş bileşenlerini kullanabilirler.<br />(Eğer tüm grupların bu yetkiye sahip olmasını istiyorsanız, boş bırakınız)';
    $lang->about_component_mid = 'Hedef editör bileşenlerini seçebilirsiniz.<br />(Hiçbir şey seçilmediğinde, tüm hedefler seçilecektir)';

    $lang->msg_component_is_not_founded = 'Editör bileşeni %s bulunamıyor';
    $lang->msg_component_is_inserted = 'Seçilen bileşen zaten eklenmiş durumda';
    $lang->msg_component_is_first_order = 'Seçilen bileşen ilk pozisyona yerleştirilmiştir';
    $lang->msg_component_is_last_order = 'Seçilen bileşen son pozisyonda yerleşmiştir';
    $lang->msg_load_saved_doc = "Kendiliğinden kaydolan bir makale mevcut. Bunu kurtarmak ister misiniz?\nOtomatik kaydolan taslak, geçerli makaleyi kaydedince, çıkartılacaktır";
    $lang->msg_auto_saved = 'Kendiliğinden Kaydedildi';

    $lang->cmd_disable = 'Devre Dışı';
    $lang->cmd_enable = 'Etkin';

    $lang->editor_skin = 'Editor Dış Görünümü';
    $lang->upload_file_grant = 'Karşıya Yükleme Yetkisi';
    $lang->enable_default_component_grant = 'Varsayılan Bileşenler için Yetki';
    $lang->enable_component_grant = 'Bileşenler için Yetki';
    $lang->enable_html_grant = 'HTML için Yetki';
    $lang->enable_autosave = 'Oto-Kayıt';
    $lang->height_resizable = 'Yükseklik Yeniden Boyutlandırılabilir';
    $lang->editor_height = 'Editör Yüksekliği';

    $lang->about_editor_skin = 'Editör dış görünümünü seçebilirsiniz.';
    $lang->about_content_style = 'Makale düzenleme veya içerik gösterme için tarz seçebilirsiniz';
    $lang->about_content_font = 'Makale düzenleme veya içerik gösterme için yazı tipi seçebilirsiniz.<br/>Varsayılan yazı tipi sizin kendi yazı tipinizdir<br/> Lütfen çoklu giriş için virgül(,) kullanınız.';
	$lang->about_content_font_size = 'Makale düzenleme veya içerik görüntüleme için yazı boyutu seçebilirsiniz.<br/>Lütfen px veya em gibi birimler kullanınız.';
    $lang->about_upload_file_grant = 'Seçilen (gruplar) dosyaları karşıya yükleme yetkisine sahip olacaklardır. (Eğer tüm grupların bu yetkiye sahip olmasını istiyorsanız lütfen boş bırakınız)';
    $lang->about_default_component_grant = 'Seçilen grup(lar) editörün varsayılan bileşenlerini kullanabileceklerdir. (Eğer tüm grupların bu yetkiye sahip olmasını istiyorsanız lütfen boş bırakınız)';
    $lang->about_editor_height = 'Editör yüksekliğini ayarlayabilirsiniz.';
    $lang->about_editor_height_resizable = 'Editörün yüksekliğinin yeniden boyutlandırıp-boyutlandırılamayacağı kararını verebilirsiniz.';
    $lang->about_enable_html_grant = 'Seçilen grup(lar) HTML kullanabileceklerdir';
    $lang->about_enable_autosave = 'Oto-Kayıt özelliğinin kullanılabilmesi kararını verebilirsiniz.';

    $lang->edit->fontname = 'Yazı Tipi';
    $lang->edit->fontsize = 'Boyut';
    $lang->edit->use_paragraph = 'Paragraf Özelliği';
    $lang->edit->fontlist = array(
    'Arial'=>'Arial',
    'Arial Black'=>'Arial Black',
    'Tahoma'=>'Tahoma',
    'Verdana'=>'Verdana',
    'Sans-serif'=>'Sans-serif',
    'Serif'=>'Serif',
    'Monospace'=>'Monospace',
    'Cursive'=>'Cursive',
    'Fantasy'=>'Fantasy',
    );

    $lang->edit->header = 'Tarz';
    $lang->edit->header_list = array(
    'h1' => 'Konu 1',
    'h2' => 'Konu 2',
    'h3' => 'Konu 3',
    'h4' => 'Konu 4',
    'h5' => 'Konu 5',
    'h6' => 'Konu 6',
    );

    $lang->edit->submit = 'Gönder';

    $lang->edit->fontcolor = 'Yazı Rengi';
	$lang->edit->fontcolor_apply = 'Yazı Rengini Uygula';
	$lang->edit->fontcolor_more = 'Daha Fazla Yazı Rengi';
    $lang->edit->fontbgcolor = 'Arkaplan Rengi';
	$lang->edit->fontbgcolor_apply = 'Arkaplan Rengini Uygula';
	$lang->edit->fontbgcolor_more = 'Daha Fazla Arkaplan Rengi';
    $lang->edit->bold = 'Kalın';
    $lang->edit->italic = 'Italik';
    $lang->edit->underline = 'Altıçizili';
    $lang->edit->strike = 'Göze Çarpan';
    $lang->edit->sup = 'Sup';
    $lang->edit->sub = 'Sub';
    $lang->edit->redo = 'Yinele';
    $lang->edit->undo = 'Geri Al';
    $lang->edit->align_left = 'Sola Hizalama';
    $lang->edit->align_center = 'Ortalı Hizalama';
    $lang->edit->align_right = 'Sağa Hizalama';
    $lang->edit->align_justify = 'Kenara Yaslı Hizalama';
    $lang->edit->add_indent = 'Girinti';
    $lang->edit->remove_indent = 'Çıkıntı';
    $lang->edit->list_number = 'Sıralanan Listesi';
    $lang->edit->list_bullet = 'Sırasız Listesi';
    $lang->edit->remove_format = 'Tarz Silicisi';

    $lang->edit->help_remove_format = 'Seçili alandaki etiketler silinecektir';
    $lang->edit->help_strike_through = 'Strike ';
    $lang->edit->help_align_full = 'Sol ve sağ hizalama';

    $lang->edit->help_fontcolor = 'Yazı rengi seç';
    $lang->edit->help_fontbgcolor = 'Yazı tipinin arkaplan rengini seç';
    $lang->edit->help_bold = 'Yazı tipini kalın yap';
    $lang->edit->help_italic = 'Yazı tipini italik yap';
    $lang->edit->help_underline = 'Altıçizili yazı tipi';
    $lang->edit->help_strike = 'Strike font';
    $lang->edit->help_sup = 'Superscript';
    $lang->edit->help_sub = 'Subscript';
    $lang->edit->help_redo = 'Yine';
    $lang->edit->help_undo = 'Geri al';
    $lang->edit->help_align_left = 'Sola Hizala';
    $lang->edit->help_align_center = 'Ortalı Hizala';
    $lang->edit->help_align_right = 'Sağa Hizala';
	$lang->edit->help_align_justify = 'Kenara Yaslı Hizalama';
    $lang->edit->help_add_indent = 'Girinti Ekle';
    $lang->edit->help_remove_indent = 'Girinti sil';
    $lang->edit->help_list_number = 'Sayı listesini uygula';
    $lang->edit->help_list_bullet = 'Bullet listesi uygula';
    $lang->edit->help_use_paragraph = 'Paragraf yapmak için Ctrl+Enter tuşlarına basınız. (Göndermek için Alt+S e basınız)';

    $lang->edit->url = 'URL';
    $lang->edit->blockquote = 'Blokalıntı';
    $lang->edit->table = 'Tablo';
    $lang->edit->image = 'Resim';
    $lang->edit->multimedia = 'Film';
    $lang->edit->emoticon = 'His Simgesi';

	$lang->edit->file = 'Dosyalar';
    $lang->edit->upload = 'Ekler';
    $lang->edit->upload_file = 'İliştir';
	$lang->edit->upload_list = 'Ekler Listesi';
    $lang->edit->link_file = 'İçeriğe Ekle';
    $lang->edit->delete_selected = 'Seçiliyi Sil';

    $lang->edit->icon_align_article = 'Paragraf Yap';
    $lang->edit->icon_align_left = 'Sola Hizala';
    $lang->edit->icon_align_middle = 'Ortaya Hizala';
    $lang->edit->icon_align_right = 'Sağa Hizala';

    $lang->about_dblclick_in_editor = 'Detaylı bileşen yapılandırmasını; arkaplana, metne, resme ya da alıntılara çift tıklayarak ayarlayabilirsiniz';


    $lang->edit->rich_editor = 'Zengin Metin Editörü';
    $lang->edit->html_editor = 'HTML Editörü';
    $lang->edit->extension ='Uzantı Bileşenleri';
    $lang->edit->help = 'Yardım';
    $lang->edit->help_command = 'Kısayol Yardım Tuşları';
    
    $lang->edit->lineheight = 'Satır Yüksekliği';
	$lang->edit->fontbgsampletext = 'ABC';
	
	$lang->edit->hyperlink = 'Köprü';
	$lang->edit->target_blank = 'Yeni Pencere';
	
	$lang->edit->quotestyle1 = 'Sol Düzçizgi';
	$lang->edit->quotestyle2 = 'Alıntı';
	$lang->edit->quotestyle3 = 'Düzçizgi';
	$lang->edit->quotestyle4 = 'Düzçizgi + Arkaplan';
	$lang->edit->quotestyle5 = 'Kalın Düzçizgi';
	$lang->edit->quotestyle6 = 'Noktalı';
	$lang->edit->quotestyle7 = 'Noktalı + Arkaplan';
	$lang->edit->quotestyle8 = 'İptal';


    $lang->edit->jumptoedit = 'Araç Çubuğu Düzenini Geç';
    $lang->edit->set_sel = 'Hücre sayımını ayarla';
    $lang->edit->row = 'Satır';
    $lang->edit->col = 'Sütun';
    $lang->edit->add_one_row = '1 Satır Ekle';
    $lang->edit->del_one_row = '1 Satır Sil';
    $lang->edit->add_one_col = '1 Sütun Ekle';
    $lang->edit->del_one_col = '1 Sütun Sil';

    $lang->edit->table_config = 'Tablo Yapılandırması';
    $lang->edit->border_width = 'Kenarlık Genişliği';
    $lang->edit->border_color = 'Kenarlık Rengi';
    $lang->edit->add = 'Ekle';
    $lang->edit->del = 'Sub';
    $lang->edit->search_color = 'Renk Ara';
    $lang->edit->table_backgroundcolor = 'Tablo Arkaplan Rengi';
    $lang->edit->special_character = 'Özek Karekterler';
    $lang->edit->insert_special_character = 'Özel Karakterler Ekle';
    $lang->edit->close_special_character = 'Özel Karakter Katmanını Kapat';
    $lang->edit->symbol = 'Semboller';
    $lang->edit->number_unit = 'Sayılar ve Birimler';
    $lang->edit->circle_bracket = 'Çemberler, Köşeli Ayraçlar';
    $lang->edit->korean = 'Korece';
    $lang->edit->greece = 'Yunanca';
    $lang->edit->Latin  = 'Latince';
    $lang->edit->japan  = 'Japonca';
    $lang->edit->selected_symbol  = 'Seçili Semboller';

    $lang->edit->search_replace  = 'Bul/Değiştir';
    $lang->edit->close_search_replace  = 'Bul\'u Kapat/Katmanı Değiştir';
    $lang->edit->replace_all  = 'Tümünü Değiştir';
    $lang->edit->search_words  = 'Bulunacak Sözcükler';
    $lang->edit->replace_words  = 'Değiştirilecek Sözcükler';
    $lang->edit->next_search_words  = 'Sonrakini Bul';
    $lang->edit->edit_height_control  = 'Düzen Formunun Boyutunu Ayarla';

    $lang->edit->merge_cells = 'Tablo Hücrelerini Birleştir';
    $lang->edit->split_row = 'Satır Ayır';
    $lang->edit->split_col = 'Sütun Ayır';
    
    $lang->edit->toggle_list   = 'Kıvır/Geriaç';
    $lang->edit->minimize_list = 'Simge Durumuna Küçült';
    
    $lang->edit->move = 'Taşı';
	$lang->edit->refresh = 'Yenile';
    $lang->edit->materials = 'Malzemeler';
    $lang->edit->temporary_savings = 'Geçici Kayıtlı Listesi';

	$lang->edit->paging_prev = 'Önceki';
	$lang->edit->paging_next = 'Sonraki';
	$lang->edit->paging_prev_help = 'Önceki sayfaya git.';
	$lang->edit->paging_next_help = 'Sonraki sayfaya git.';

	$lang->edit->toc = 'İçerik Tablosu';
	$lang->edit->close_help = 'Yardımı Kapat';
	
	$lang->edit->confirm_submit_without_saving = 'Kaydedilmemiş paragraflar var.\\nYine de devam etmek ister misiniz?';

	$lang->edit->image_align = 'Resim Hizalaması';
	$lang->edit->attached_files = 'Ekler';

	$lang->edit->fontcolor_input = 'Özel Metin Rengi';
	$lang->edit->fontbgcolor_input = 'Özel Arkaplan Rengi';
	$lang->edit->pangram = 'Kahverengi hızlı tilki, tembel köpeğin üzerinden atlıyor';

	$lang->edit->table_caption_position = 'Tablo Yazısı &amp; Konumu';
	$lang->edit->table_caption = 'Tablo Yazısı';
	$lang->edit->table_header = 'Tablo Başlığı';
	$lang->edit->table_header_none = 'hiçbiri';
	$lang->edit->table_header_left = 'sol';
	$lang->edit->table_header_top = 'üst';
	$lang->edit->table_header_both = 'hepsi';
	$lang->edit->table_size = 'Tablo Boyutu';
	$lang->edit->table_width = 'Tablo Genişliği';

	$lang->edit->upper_left = 'Üst Sol';
	$lang->edit->upper_center = 'Üst Merkez';
	$lang->edit->upper_right = 'Üst Sağ';
	$lang->edit->bottom_left = 'Alt Sol';
	$lang->edit->bottom_center = 'Alt Merkez';
	$lang->edit->bottom_right = 'Alt Sağ';

	$lang->edit->no_image = 'Yüklenmiş hiçbir resim yok.';
	$lang->edit->no_multimedia = 'Yüklenmiş hiçbir görüntü yok.';
	$lang->edit->no_attachment = 'Yüklenmiş hiçbir dosya yok.';
	$lang->edit->insert_selected = 'Seçileni Ekle';
	$lang->edit->delete_selected = 'Seçileni Sil';

	$lang->edit->fieldset = 'Alanayarı';
	$lang->edit->paragraph = 'Paragraf';
	
	$lang->edit->autosave_format = 'Makaleyi yazmak için <strong>%s</strong> kadar bir süre kullandınız. Makalenizi son kaydettiğiniz zaman : <strong>%s</strong>.';
	$lang->edit->autosave_hour = '%dSaat';
	$lang->edit->autosave_hours = '%dSaat';
	$lang->edit->autosave_min = '%dDakika';
	$lang->edit->autosave_mins = '%dDakika';
	$lang->edit->autosave_hour_ago = '%dsaat önce';
	$lang->edit->autosave_hours_ago = '%d saat önce';
	$lang->edit->autosave_min_ago = '%ddakika önce';
	$lang->edit->autosave_mins_ago = '%ddakika önce';
	
	$lang->edit->upload_not_enough_quota   = 'Yeteri boş alan bulunmadığından, karşıya yükleme yapılamıyor.';
	$lang->edit->break_or_paragraph = 'Enter는 줄바꿈, Shift+Enter는 문단바꿈입니다.';
?>
