<?php
    /**
     * @file   modules/spamfilter/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English Language Pack (basic)
     **/

    // action related
    $lang->cmd_denied_ip = "IP Adresleri Kara Listesi";
    $lang->cmd_denied_word = "Yasak Sözcük Listesi";

    // general word
    $lang->spamfilter = "Spam filtreleyici";
    $lang->denied_ip = "IP Adresi";
    $lang->interval = "Spam filtreleyici için aralık";
    $lang->limit_count = "Gönderi Sınırı";
    $lang->check_trackback = "Geri izlemeleri kontrol et.";
    $lang->word = "Kelime";
    $lang->hit = 'Hit';
    $lang->latest_hit = 'Son Hit\'ler';

    // for description word
    $lang->about_interval = "Belirlenen süre içerisinde gönderilmeye çalışılan tüm yazılar engellenecektir.";
    $lang->about_limit_count = "Gönderi sınırını aşarsanız,\n o IP bir spam olarak kabul edilecek ve dolayısıyla yazı göndermede, yorum yapmada ve geri izlemede bulunmada sınırlamalarla karşılaşacaktır.";
    $lang->about_denied_ip = "* işaretini kullanarak 127.0.0.* şeklinde IP adres aralığı ekleyebilirsiniz.";
    $lang->about_denied_word = "Yasak sözcük listesine bir kelime eklediğinizde,\n o kelimeyi içeren tüm yazılar engellenecektir.";
    $lang->about_check_trackback = "Geri izlemeler için yazı başına tek bir IP'ye izin verilir.";

    // to post a message
    $lang->msg_alert_limited_by_config = '%s saniyede bir yazı göndermek mümkün değildir.\n Denemeyi sürdürürseniz, IP adresiniz kara listeye alınacaktır.';
    $lang->msg_alert_denied_word = ' "%s" kelimesinin kullanılmasına izin verilmemektedir.';
    $lang->msg_alert_registered_denied_ip = 'IP adresiniz kara listeye alındı,\n siteyi kullanırken sınırlamalarla karşılaşacaksınız.\n Bu konuda sorularınız varsa, lütfen site yöneticisi ile görüşün.';
    $lang->msg_alert_trackback_denied = 'Yazı başına sadece bir geri izlemeye izin verilmektedir.';
?>