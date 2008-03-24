<?php
    /**
     * @archivo   es.lang.php
     * @autor zero (zero@nzeo.com)
     * @sumario Paquete del idioma español para importar.
     **/

    // Palabras para los botones
    $lang->cmd_sync_member = 'Sincronizar';
    $lang->cmd_continue = 'Continuar';
    $lang->preprocessing = '데이터 이전을 위한 사전 준비중입니다.';

    // Especificaciones
    $lang->importer = 'Transferir los datos de zeroboard';
    $lang->source_type = 'Objetivo a transferir';
    $lang->type_member = 'Información del usuario';
    $lang->type_message = 'Mensaje de Datos';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Información del documento.';
    $lang->type_syncmember = 'Sincronizar la información del usuario';
    $lang->target_module = 'Objetivo del módulo';
    $lang->xml_file = 'Archivo XML';

    $lang->import_step_title = array(
        1 => 'Paso 1. Seleccione el objetivo a transferir',
        12 => 'Paso 1-2. Seleccione el objetivo del módulo ',
        13 => 'Paso 1-3. Seleccione la categoría del módulo',
        2 => 'Paso 2. Subir el archivo XML',
        3 => 'Paso 2. Sincronizar las informaciones del usuario y la del documento',
    );

    $lang->import_step_desc = array(
        1 => 'Seleccione el tipo de archivo XML a transfrerir.',
        12 => 'Seleccione el módulo para transferir los datos.',
        13 => 'Seleccione la categoría para transferir los datos.',
        2 => "Ingrese la ubicación del archivo XML para transfer los datos.\nPuede ser ruta absoluto o relativo.",
        3 => 'La información del usuario y del documento podría ser incorrecto luego de la transferencia. Si ese es el caso, sincroniza para la corrección basado a la ID del usuario.',
    );

    // Guía/ Alerta
    $lang->msg_sync_member = 'Al presionar el botón sincronizar comenzará a sincronizar la información del usuario y la del artículo.';
    $lang->msg_no_xml_file = 'No se puede encontrar el archivo XML. Verifique su ruta.';
    $lang->msg_invalid_xml_file = 'Tipo de archivo XML inválido.';
    $lang->msg_importing = 'Ingresando %d dotos de %d. (Si esto mantiene paralizado presione el botón "Continuar".)';
    $lang->msg_import_finished = '%d/%d datos fueron completamente ingresados. Dependiendo del caso, pueden haber algunos datos no ingresados.';
    $lang->msg_sync_completed = 'Sincronización del usuario, artículo y respuestas finalizadas.';

    // bla bla...
    $lang->about_type_member = 'Seleccione esta opción si estas transferiendo la información del usuario.';
    $lang->about_type_message = 'Si está transfiriendo la información de mensajes, seleccione esta opción';
    $lang->about_type_ttxml = '	Si está transfiriendo la TTXML (textcube) información, seleccione esta opción';
    $lang->about_ttxml_user_id = 'Por favor, de entrada ID de usuario establecer como autor de la transferencia de TTXML. (Identificación de usuario debe ser firmado ya en marcha)';
    $lang->about_type_module = 'Seleccione esta opción si estas transfeririendo información del documento de los tableros';
    $lang->about_type_syncmember = 'Seleccione esta opción cuando tenga que sincronizar la información del usuario luego de haber transferido la información del usuario y del artículo.';
    $lang->about_importer = "Es posible trasferir los datos de Zeroboard4, zb5beta o de otros programas a ZeroBoardXE.\nPara la transferencia debe utilizar <a href=\"http://svn.zeroboard.com/zeroboard_xe/migration_tools/\" onclick=\"winopen(this.href);return false;\">XML Exporter</a> para transformar los datos en archivo XML, y luego subir ese archivo.";

    $lang->about_target_path = "Para descargar los archivos adjuntos de ZeroBoard4, ingresa la ubicación de ZeroBoard4 instalado.\nSi esta en el mismo servidor escriba la ubicación de ZeroBoard4 como por ejemplo: /home/ID/public_html/bbs o si esta en otro servidor escriba la ubicación de ZeroBoard4 instalado como por ejemplo: http://dominio/bbs";
?>
