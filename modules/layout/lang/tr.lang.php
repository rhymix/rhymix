<?php
    /**
     * @file   modules/layout/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  Layout module's basic language pack
     **/

    $lang->cmd_layout_management = 'Yerleşim Düzeni Ayarları';
    $lang->cmd_layout_edit = 'Yerleşim Düzeni Düzenle';

    $lang->layout_name = 'Yerleşim Düzeni İsmi';
    $lang->layout_maker = "Yerleşim Düzeni Geliştiricisi";
    $lang->layout_license = 'Lisans';
    $lang->layout_history = "Güncellemeler";
    $lang->layout_info = "Yerleşim Düzeni Bilgisi";
    $lang->layout_list = 'Yerleşim Düzeni Listesi';
    $lang->menu_count = 'Menüler';
    $lang->downloaded_list = 'İndirme Listesi';
    $lang->layout_preview_content = 'Burada görüntülenecek içerikler';
    $lang->not_apply_menu = 'Yerleşim düzenlerini bütünüyle onayla';
	$lang->layout_management = 'Yerleşim Düzeni Yönetimi';

    $lang->cmd_move_to_installed_list = "Oluşturulmuş Yerleşim Düzenlerini Görüntüle";

    $lang->about_downloaded_layouts = "İndirilmiş yerleşim düzenlerinin listesi";
    $lang->about_title = 'Modüle bağlanılacağı zaman kolayca doğrulanacak başlığı giriniz';
    $lang->about_not_apply_menu = 'Menüyle bağlanmış modüllerin yerleşim düzenlerinin hepsi bu seçenekle değiştirilecketir.';

    $lang->about_layout = "Yerleşim düzeni (layout) modülü size, sitenin yerleşim düzenini kolayca oluşturma imkanı sunar.<br />Yerleşim düzeni ayarını ve menü bağlantısını kullandığınzda, websitelerin çeşitli modüllerle tamamlanmış şekilleri gösterilecektir.<br />* Blogların veya diğer modüllerin varsayılan yerleşim düzenlerini silemezseniz; onları, kendi ayar sayfalarından silmeniz gerekmektedir. ";
    $lang->about_layout_code = 
        "Yerleşim düzeni kodunu düzenledikten sonra kaydettiğinizde, hizmete girecektir.
        Lütfen önce kodunuzun önizlemesini yapın ve sonra kaydedin.
        XE\'nin şablolunu örnek alabilirsiniz : <a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE Template</a>.";

    $lang->layout_export = 'Dışa Aktar';
    $lang->layout_btn_export = 'Yerleşim Düzenimi İndir';
    $lang->about_layout_export = 'Düzenlenmiş mevcut yerleşim düzenini dışa aktar.';
    $lang->layout_import = 'İçe Aktar';
    $lang->about_layout_import = 'İçe yeni bir yerleşim düzeni aktarmanız durumunda, önceden değiştirdiğiniz yerleşim düzeni silinecektir. Lütfen içe aktarım yapmadan önce, mevcut yerleşim düzeninin yedeğini almış olmak için dışa aktarınız.';

    $lang->layout_manager = array(
        0  => 'Yerleşim Düzeni Yöneticisi',
        1  => 'Kaydet',
        2  => 'İptal',
        3  => 'Biçim',
        4  => 'Dizi',
        5  => 'Yerleştir',
        6  => 'Sabit Yerleşim Düzeni',
        7  => 'Değişken Yerleşim Düzeni',
        8  => 'Sabit+Değişken (İçerik)',
        9  => 'Hücre 1',
        10 => 'Hücre 2 (içeriğin sol kısmında)',
        11 => 'Hücre 2 (içeriğin sağ kısmında)',
        12 => 'Hücre 3 (içeriğin sol kısmında)',
        13 => 'Hücre 3 (içeriğin orta kısmında)',
        14 => 'Hücre 3 (içeriğin sol kısmında)',
        15 => 'Sol',
        16 => 'Orta',
        17 => 'Sağ',
        18 => 'Tümü',
        19 => 'Yerleşim Düzeni',
        20 => 'Widget ekle',
        21 => 'İçerik Widgetı ekle',
        22 => 'Öznitelik',
        23 => 'Widget Tarzı',
        24 => 'Düzenle',
        25 => 'Sil',
        26 => 'Hizala',
        27 => 'Satır atla',
        28 => 'Sol',
        29 => 'Sağ',
        30 => 'Genişlik',
        31 => 'Uzunluk',
        32 => 'Kenar Boşluğu',
        33 => 'Dolgu',
        34 => 'Üst',
        35 => 'Sol',
        36 => 'Sağ',
        37 => 'Alt',
        38 => 'Kenarlık', 
        39 => 'Hiçbiri',
        40 => 'Arkaplan',
        41 => 'Renk',
        42 => 'Resim',
        43 => 'Seç',
        44 => 'Arkaplan Tekrarı',
        45 => 'Tekrar',
        46 => 'Tekrar Yok',
        47 => 'Genişlik Tekrarı',
        48 => 'Uzunluk Tekrarı',
        49 => 'Uygula',
        50 => 'İptal',
        51 => 'Sıfırla',
        52 => 'Metin',
        53 => 'Yazı Tipi',
        54 => 'Yazı Rengi',
    );

    $lang->layout_image_repository = 'Yerleşim Düzeni Havuzu';
    $lang->about_layout_image_repository = 'Seçili yerleşim düzeni için resimler/flashlar ekleyebilirsiniz. Dışa aktarımda onlar da beraber aktarılacaktır';
    $lang->msg_layout_image_target = 'Sadece gif, png, jpg, swf, flv dosyalarına izin verilmiştir';
    $lang->layout_migration = 'Yerleşim Düzeni Geçişi';
    $lang->about_layout_migration = 'Değiştirilmiş yerleşim düzenlerini tar dosyası olarak içe veya dışa aktarabilirsiniz.'."\n".'(Şimdilik sadece FaceOff iç/dış aktarım desteği sunmaktadır)';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff Yerleşim Düzeni Yöneticisi',
        'description' => 'FaceOff Yerleşim Düzeni Yöneticisi, tarayıcınızı kullanarak kolayca yerleşim düzenini tasarlamanıza yardımcı olacaktır.<br/>Lütfen kendi yerleşim düzeninize, aşağıda da gösterildiği gibi, bileşenler ve özellikler tasarlayınız.',
        'layout' => 'FaceOff yukardaki gibi bir HTML yapısına sahiptir.<br/>Tasarlamak için Style kullanabilir ya da CSS ile ayarlayabilirsiniz.<br/>Extension(e1, e2), Neck ve Knee den widget ekleyebilirsiniz.<br/>Aynı zamanda Body, Layout, Header, Body, Footer; Style tarafından tasarlanabilir, ve İçerik, içeriği gösterecektir.',
        'setting' => 'Sol üsteki menüyü açıklayalım..<br/><ul><li>Kaydet : Mevcut ayarları kaydeder.</li><li>İptal : Mevcut ayarlardan vazgeçer ve geri döner.</li><li>Sıfırla : Mevcut ayarları temizler</li><li>Biçim : Biçim, Sabit/ Değişken/ Sabit+Değişken(İçerik).</li><li>Yerleştir : 2 Uzantı ve İçerik yerleştirir.</li><li>Hizala : Yerleşim düzeninin konumunu hizalar.</li></ul>',
        'hotkey' => 'Yerleşim düzenini kısayol tuşlarıyla daha kolay tasarlayabilirsiniz.<br/><ul><li>sekme tuşu(tab) : Bir widget seçilmedikçe; Header, Body, Footer sırasıyla seçilecektir. Eğer seçilmezse, bir sonraki widget seçilecektir.</li><li>Shift + sekme tuşu : Sekme tuşunun tersi hareket gerçekleştirir.</li><li>Esc : Eğer hiçbir şey seçilmediyse, Neck, Extension(e1,e2),Knee sırasıyla seçilecektir, eğer bir widget seçilmişse, widget alanı seçilecektir.</li><li>Yön Tuşları : Eğer bir widget seçiliyse, yön tuşları widget uygulanan yönlere hareket ettirecektir</li></ul>',
        'attribute' => 'Widget harici, tüm alanların arkaplan rengini/resmini ve yazı rengini(<a> etiketi de dahil) ayarlayabilirsiniz.',

    );

	$lang->mobile_layout_list = "Hareketli Yerleşim Düzeni Listesi";
	$lang->mobile_downloaded_list = "İndirilmiş Hareketli Yerleşim Düzenleri";
	$lang->apply_mobile_view = "Hareketli Görünümü Uygula";
	$lang->about_apply_mobile_view = "Hareketli cihazlarla bağlanırken, bağlı tüm modüller hareketli görünüme geçerler.";
?>
