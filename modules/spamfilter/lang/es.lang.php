<?php
    /**
     * @archivo   modules/spamfilter/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paquete del idioma español (básico)
     **/

    // relacionado a la acciona
    $lang->cmd_denied_ip = "Lista negra de las direcciones IP";
    $lang->cmd_denied_word = "Lista negra de palabras";

    // palabras generales
    $lang->spamfilter = "Filtro de Spam";
    $lang->denied_ip = "IP prohibido";
    $lang->interval = "Intervalo para el filtro de spam";
    $lang->limit_count = "Número limite";
    $lang->check_trackback = "Chequear trackback";
    $lang->word = "Palabra";
    $lang->hit = '히트';
    $lang->latest_hit = '최근 히트';

    // para las palabras de descripcion
    $lang->about_interval = "Se bloquea el posteo de todo tipo de documentos durante el tiempo asignado.";
    $lang->about_limit_count = "Si Usted excede el número límite del posteo,\n Su documento serán reconocido como un spam, y su dirección IP sera agregada en la lista negra.";
    $lang->about_denied_ip = "Usted puede asignar rangos de direcciones IP como 127.0.0.* usando *.";
    $lang->about_denied_word = "Cuando Usted agrega una palabra a la lista negra de palabras,\n documentos con esa palabra no será registrado.";
    $lang->about_check_trackback = "En un documento sólo se permite un trackback por IP.";

    // para registrar un documento
    $lang->msg_alert_limited_by_config = 'Se prohibe poestear antes de %s segundos.\n Si Usted trata varias veces, su dirección IP puede ser agregada en la lista negra.';
    $lang->msg_alert_denied_word = 'La palabra "%s" no esta permitido para ser posteado.';
    $lang->msg_alert_registered_denied_ip = 'Su dirección IP fue agregaa en la lista negra,\n por lo cual Usted puede tener limitaciones en su uso normal de este sitio.\n Si Usted tiene alguna inquietud sobre el tema, por favor contactese con el administrador del sitio.';
    $lang->msg_alert_trackback_denied = 'Sólo un trackback por documento está permitido.';
?>