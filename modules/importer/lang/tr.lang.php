<?php
    /**
     * @file   en.lang.php
     * @author nicetwo (supershop@naver.com)
     * @brief basic english language pack of Importer
     **/

    // words for button
    $lang->cmd_sync_member = 'Senkronize';
    $lang->cmd_continue = 'Devam Et';
    $lang->preprocessing = 'Zeroboard içe aktarım için hazırlanıyor.';

    // items
    $lang->importer = 'Zeroboard Veri Alıcısı';
    $lang->source_type = 'Geçiş Hedefi';
    $lang->type_member = 'Üye Verisi';
    $lang->type_message = 'Mesaj Verisi';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Makale Verisi';
    $lang->type_syncmember = 'Üye Verisini Senkronize et';
    $lang->target_module = 'Hedef Modül';
    $lang->xml_file = 'XML Dosyası';

    $lang->import_step_title = array(
        1 => '1. adım Geçiş Hedefi',
        12 => '1-2. adım Hedef Modül',
        13 => '1-3. adım Hedef Kategori',
        2 => '2. adım XML Dosya Konumu',
        3 => '2. adım Üye ve Makale Verilerini Senkronize Et',
        99 => 'Veri İçeri Aktarılıyor',
    );

    $lang->import_step_desc = array(
        1 => 'Lütfen içe aktarmak istediğiniz XML dosyalarını yazınız.',
        12 => 'Lütfen içe aktarım yapmak istediğiniz modülü seçiniz.',
        121 => 'Gönderiler:',
        122 => 'Misafir Defteri:',
        13 => 'Lütfen içe aktarım yapmak istediğiniz kategoriyi seçiniz.',
        2 => "lütfen içe aktarılacak veriyi içeren XML dosyalarının konumunu giriniz.\nKesin yol ve ilgili yol girebilirsiniz.",
        3 => 'Veri içe aktarımından sonra üye ve makale verileri doğru olmayabilir. Eğer böyleyse lütfen verileri doğru elde etmek için, kullanıcı_kimliğiyle (user_id) senkronize ediniz.',
        99 => 'İçe Aktarılıyor...',
    );

    // guide/alert
    $lang->msg_sync_member = 'Lütfen senkronizasyonu başlatmak için Senkronizasyon düğmesine basınız.';
    $lang->msg_no_xml_file = 'XML dosyası bulunamadı. Lütfen yol doğrumu diye tekrar kontrol ediniz';
    $lang->msg_invalid_xml_file = 'Geçersiz tür XML dosyası.';
    $lang->msg_importing = '%d ögelerini %d \'den alarak içeri aktar. (eğer işlem durduysa, Devam tuşuna basınız)';
    $lang->msg_import_finished = '%d/%d ögeleri başarıyla içe aktarıldı. Bazı ögeler düzgün bir şekilde içe aktarılmamış olabilir.';
    $lang->msg_sync_completed = 'Üye, makale ve yorumların senkronizasyonu tamamlandı.';

    // blah blah..
    $lang->about_type_member = 'Eğer üye verisi içe aktaracaksanız, lütfen bu seçeneği seçiniz';
    $lang->about_type_message = 'Eğer mesaj verisi içe aktaracaksanız, lütfen bu seçeneği seçiniz';
    $lang->about_type_ttxml = 'Eğer TTXML (textcube) verisi içe aktaracaksanız, lütfen bu seçeneği seçiniz';
	$lang->about_ttxml_user_id = 'Lütfen TTXML veri yazarı atamak için kullanıcı kimliği(ID) giriniz. (kullanıcı kimliği, kayıtlı kullanıcıya ait olmalıdır)';
    $lang->about_type_module = 'Eğer makale verisi içe aktaracaksanız, lütfen bu seçeneği seçiniz';
    $lang->about_type_syncmember = 'Eğer üye ve makale verisi içe aktarıp senkronize edecekseniz, lütfen bu seçeneği seçiniz';
    $lang->about_importer = "Veri Alıcısı XE'ye, Zeroboard4, Zeroboard5 Beta veya başka programların verilerini aktarmada yardımcı olacaktır.\nİçe aktarımı gerçekleştirebilmek için öncelikle <a href=\"http://svn.xpressengine.net/migration/\" onclick=\"winopen(this.href);return false;\">XML DışAktarımcı</a>'yı kullanıp istediğiniz veriyi XML türüne çevirmelisiniz.";

    $lang->about_target_path = "Zeroboard4\'ten ekler almak için, Zeroboard4\'ün kurulu olduğu yolu giriniz.\nEğer aynı sunucuda konumlandırılmışsa, lütfen Zeroboard4\'ün yolunu bu şekilde giriniz : /home/USERID/public_html/bbs\nEğer aynı sunucuda değilse, lütfen Zeroboard4\'ün kurulu olduğu adresi giriniz. örn. http://Domain/bbs";
?>
