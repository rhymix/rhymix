<?php
    /**
     * @file   es.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Importer(importer) paquete lingual
     **/

    // Lenguage para botones
    $lang->cmd_sync_member = 'Sincronizar';
    $lang->cmd_continue = 'Continuar';

    // especificación
    $lang->importer = 'Transferir los datos de zeroboard';
    $lang->source_type = 'Tipo de origen';
    $lang->type_member = 'Datos de miembros';
    $lang->type_module = 'Datos de articulos.';
    $lang->type_syncmember = 'Sincronizar datos de miembros';
    $lang->target_module = 'Modulo objetivo';
    $lang->xml_file = 'archivo XML';

    $lang->import_step_title = array(
        1 => 'Paso 1. seleccione la origen',
        12 => 'Paso 1-2. seleccione el modulo objetivo',
        13 => 'Paso 1-3. seleccione la categoria',
        2 => 'Paso 2. Suvir archivo de XML',
        3 => 'Paso 2. Sincronizar los datos de miembro y articulos',
    );

    $lang->import_step_desc = array(
        1 => 'Por favor seleccione el tipo de archivo XML para transfrerir.',
        12 => 'Por favor seleccione el modulo que Ud. desea para transferir los datos.',
        13 => 'Por favor seleccione la categoria para transferir.',
        2 => "Por favor inserta la ubicación de archivo XML.\nEsto puede ser paso absoluto o relativo.",
        3 => 'La información de miembro y articulos puede ser incorrecto. Sincroniza para la corrección de este problema.',
    );

    // guía/ alerta
    $lang->msg_sync_member = 'Preciónar Sincronizar para empezar sincronización de los datos miembro y articulos.';
    $lang->msg_no_xml_file = 'No puede encontrar archivo XML. Verifique sus entrada.';
    $lang->msg_invalid_xml_file = 'invalido tipo de archivo XML.';
    $lang->msg_importing = 'Escribiendo datos %d de %d. (Si esta parece paralizado haga clic en "Continuar".)';
    $lang->msg_import_finished = 'Importación de %d datos esta completo. Depende la situción, puede ser la información no puede agregar.';
    $lang->msg_sync_completed = 'Sincronización de Miembro, ariculos, y respuestas esta completo.';

    // bla bla...
    $lang->about_type_member = 'seleccione si desea transferir información de miembro.';
    $lang->about_type_module = 'seleccione si desea transferir información de articulos';
    $lang->about_type_syncmember = 'seleccione si desea sincronizar despues de transferencia';
    $lang->about_importer = "Puede importar Zeroboard4, zb5beta o datos de otras programas a ZeroBoardXE.\nPara la transferencia utiliza<a href=\"#\" onclick=\"winopen('');return false;\">Exportador XML</a> para recrear datos en archivo XML, y subir los resultos.";

    $lang->about_target_path = "Escribe la ubicación de ZeroBoard4 para bajar el archivo.\nSi existe en mismo servidor escribe la ubicación de ZeroBoard4 por ejemplo: /home/ID/public_html/bbs o si existe en otro servidor escribe URL como ejemplo: http://dominio/bbs";
?>
