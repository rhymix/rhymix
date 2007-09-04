<?php
    /**
     * @archivo   modules/spamfilter/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paquete del idioma espanol (basico)
     **/

    // relacionado a la acciona
    $lang->cmd_denied_ip = "Lista negra de las direcciones IP";
    $lang->cmd_denied_word = "Lista negra de palabras";

    // palabras generales
    $lang->spamfilter = "Filtro de Spam";
    $lang->denied_ip = "IP prohibido";
    $lang->interval = "Intervalo para el filtro de spam";
    $lang->limit_count = "Numero limite";
    $lang->check_trackback = "Chequear trackback";
    $lang->word = "Palabra";

    // para las palabras de descripcion
    $lang->about_interval = "Se bloquea el posteo de ningun tipo de documentos durante el tiempo asignado.";
    $lang->about_limit_count = "Si Usted excede el numero limite del posteo,\n Su documento seran reconocido como un spam, y su direccion IP sera agregada en la lista negra.";
    $lang->about_denied_ip = "Usted puede asignar rangos de direcciones IP como 127.0.0.* usando *.";
    $lang->about_denied_word = "Cuando Usted agrega una palabra a la lista negra de palabras,\n documentos con esa palabra no sera registrado.";
    $lang->about_check_trackback = "En un documento solo se permite un trackback por IP.";

    // para registrar un documento
    $lang->msg_alert_limited_by_config = 'Se prohibe poestear antes de %s segundos.\n Si Usted trata varias veces, su direccion IP puede ser agregada en la lista negra.';
    $lang->msg_alert_denied_word = 'La palabra "%s" no esta permitido para ser posteado.';
    $lang->msg_alert_registered_denied_ip = 'Su direccion IP fue agregaa en la lista negra,\n por lo cual Usted puede tener limitaciones en su uso normal de este sitio.\n Si Usted tiene alguna inquietud sobre el tema, por favor contacte con el administrador del sitio.'; 
    $lang->msg_alert_trackback_denied = 'Solo un trackback por documento esta permitido.';
?>
