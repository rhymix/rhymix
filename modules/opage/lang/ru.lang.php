<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->opage = "Внешняя страница";
    $lang->opage_path = "Расположение внешнего документа";
    $lang->opage_caching_interval = "Установить время кеширования";

    $lang->about_opage = "Этот модуль позволяет использовать внешние HTML или PHP файлы в XE.<br />Требует ввода абсолютного или относительного пути. Если URL начинается с 'http://', внешняя страница с другого сервера будет показана.";
    $lang->about_opage_path= "Пожалуйста, введите размещение внешнего документа.<br />Абсолютный путь как '/path1/path2/sample.php', так и относительный как '../path2/sample.php' могут быть использованы.<br />Если Вы введете путь как 'http://url/sample.php', результат будет сначала получен и затем показан.<br />Это текущий абсолютный путь к XE.<br />";
    $lang->about_opage_caching_interval = "Единица измерения равна одной минуте. Это отображает временно сохраненные данные для присвоенного времени.<br />Рекомендуется устанавливать разумное время кеширования, если множество ресурсов нуждаются в показе данных с других серверов.<br />Значение 0 отключает кеширование.";
	$lang->opage_mobile_path = 'Location of External Document for Mobile View';
    $lang->about_opage_mobile_path= "Please input the location of external document for mobile view. If not inputted, it uses the the external document specified above.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php' , the result will be received and then displayed.<br />This is current XE's absolute path.<br />";
?>
