<?php
    /**
     * @file   es.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  basic language pack of external page module
     **/

    $lang->opage = "Page Exteriores";
    $lang->opage_path = "Ubicacion del documento externo";
    $lang->opage_caching_interval = "Establezca el tiempo de cache";

    $lang->about_opage = 'Este modulo permite el uso externo de archivos html o php en XE. <br /> Permite ruta absoluta o relativa, y si la URL comienza con "http://", se puede mostrar la pagina externa del servidor.';
    $lang->about_opage_path= "Por favor ingrese la ubicacion del documento externos. <br /> Ambos ruta absoluta como '/ path1/path2/sample.php' o ruta relativa como \"../path2/sample.php\" puede ser utilizado. <br /> Si la via de entrada, como \"http://url/sample.php\", el resultado sera recibido y, a continuacion se muestran. <br /> Esta es la actual XE ruta absoluta.<br />";
    $lang->about_opage_caching_interval = "La unidad es minuto, y se muestra temporal de los datos guardados por el tiempo asignado. <br /> Se recomienda a la cache para una buena vez si una gran cantidad de recursos se necesitan otros servidores cuando se muestran los datos o la informacion. <br /> Un valor de 0 no cache.";
	$lang->opage_mobile_path = 'Location of External Document for Mobile View';
    $lang->about_opage_mobile_path= "Please input the location of external document for mobile view. If not inputted, it uses the the external document specified above.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php' , the result will be received and then displayed.<br />This is current XE's absolute path.<br />";
?>
