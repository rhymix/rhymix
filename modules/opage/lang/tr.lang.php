<?php
    /**
     * @file   en.lang.php
     * @author NHN (evelopers@xpressengine.com)
     * @brief  basic language pack of external page module
     **/

    $lang->opage = "Harici Sayfa";
    $lang->opage_path = "Harici Belgenin Konumu";
    $lang->opage_caching_interval = "Önbelleğe Alma Zamanı";

    $lang->about_opage = "Bu modül, harici html veya php dosyalarının XE\'nin içinde kullanılmasına olanak tanır.<br />Kesin ya da ilgili yola izin verir ve eğer url 'http://' ile başlıyorsa , modül sunucunun harici sayfasını gösterebilir.";
    $lang->about_opage_path= "Lütfen harici dosyaların konumunu giriniz.<br />'/path1/path2/sample.php' gibi kesin yollar ve  '../path2/sample.php' gibi ilgili yollar kullanılabilir.<br />Eğer 'http://url/sample.php' gibi bir yol giriyorsanız, sonuç önce alınacak sonra gösterilecektir.<br />Bu XE\'nin kesin yoludur.<br />";
    $lang->about_opage_caching_interval = "Birim dakikadır ve belirlenen zaman için kaydedilmiş geçici dosyayı gösterir.<br />Diğer sunucuların veri veya bilgileri gösterilirken, eğer fazla sayıda kaynağın gösterilmesi gerekiyorsa, uygun zamanın önbelleğe alınması önerilmiştir. <br />0 değeri verilirse, önbelleğe alınma işlemi gerçekleşmeyecektir.";

	$lang->opage_mobile_path = 'Harici Dosyanın Hareketli Görünüm için Konumu';
    $lang->about_opage_mobile_path= "Lütfen hareketli görünüm için harici dosyanın konumunu giriniz. Eğer konum girilmediyse, harici belgenin belirlenmişini kullanır.<br />'/path1/path2/sample.php' gibi kesin yol veya '../path2/sample.php' gibi ilgili yol kullanılabilir.<br />Eğer 'http://url/sample.php' gibi bir adres girdiyseniz , sonuç önce alınacak sonra görüntülenecektir.<br />Bu XE\'nin kesin yoludur.<br />";
?>
