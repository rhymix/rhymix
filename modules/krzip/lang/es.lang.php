<?php
    /**
     * @archivo  modules/krzip/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario  Paquete del idioma espanol (Solo los contenidos basicos)
     **/

    // Palabras normales
    $lang->krzip = "Codigo Postal de Corea";
    $lang->krzip_server_hostname = "Nombre del servidor para el chequeo de codigo postal";
    $lang->krzip_server_port = "Puero del servidor para el chequeo de codigo postal";
    $lang->krzip_server_query = "Ruta del servidor para el chequeo de codigo postal";

    // Descripciones
    $lang->about_krzip_server_hostname = "Ingresar el dominio del servidor para chequear el codigo postal y recibir la lista de resultado";
    $lang->about_krzip_server_port = "Ingresar el numero del puerto del servidor para chequear el codigo postal";
    $lang->about_krzip_server_query = "Ingresar el query url que sera pedido para chequear el codigo postal";

    // Mensajes de error
    $lang->msg_not_exists_addr = "Objetivo para la busqueda no existe";
    $lang->msg_fail_to_socket_open = "No se puede conectar al servidor de cheque de codigo postal";
?>
