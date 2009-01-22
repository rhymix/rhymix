<?php
    /**
     * @file   modules/issuetracker/lang/es.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  El idioma por defecto del módulo Issuetracker
     **/

     $lang->issuetracker = 'Duco cuestiones actualización';
     $lang->about_issuetracker = 'Calendario para la gestión de proyectos, el código de acceso, gestión y distribución, para gestionar el problema, el módulo es';

     $lang->cmd_project_list = 'Lista de proyectos';
     $lang->cmd_view_info = 'Información del Proyecto';
     $lang->cmd_project_setting = 'Configuración del proyecto';
     $lang->cmd_release_setting = 'Configuración de despliegue';
     $lang->cmd_insert_package = 'Añadir paquete';
     $lang->cmd_insert_release = 'Añadir distribución';
     $lang->cmd_attach_file = 'Archivo Adjuntos';
     $lang->cmd_display_item = 'Meta pantalla';

     $lang->cmd_resolve_as = 'Cambio de estado';
     $lang->cmd_reassign = 'Cambiar Propietario';
     $lang->cmd_accept = 'Aceptar';

     $lang->svn_url = 'SVN dirección';
     $lang->about_svn_url = 'Versión SVN de la gestión del proyecto de obras, por favor, la dirección';
     $lang->svn_cmd = 'SVN ubicación del archivo ejecutable';
     $lang->about_svn_cmd = 'SVN svn para trabajar con el cliente, por favor, introduzca la ubicación del archivo ejecutable. (ej: / usr / bin / svn)';
     $lang->diff_cmd = 'Ejecute el archivo de ubicación DIFF';
     $lang->about_diff_cmd = 'SVN revisión de la comparación, por favor, introduzca la ubicación del archivo ejecutable de la diferencia. (ej: / usr / bin / diff)';
     $lang->svn_userid = 'SVN autenticación ID';
     $lang->about_svn_userid = 'Introduce el número de autenticación, en caso necesario';
     $lang->svn_passwd = 'SVN contraseña de autenticación';
     $lang->about_svn_passwd = 'Introduzca la contraseña para la autenticación, en caso necesario';

     $lang->issue = 'Tema';
     $lang->total_issue = 'Todos los números';
     $lang->milestone = $lang->milestone_srl = 'Plan de';
     $lang->priority = $lang->priority_srl = 'Prioridad';
     $lang->type = $lang->type_srl = 'Tipo';
     $lang->component = $lang->component_srl = 'Componentes';
     $lang->assignee = 'Propietario';
     $lang->status = 'Estado';
     $lang->action = 'Comportamiento';

     $lang->history_format_not_source = '<span class="target"> [objetivo] </ span> <span a class="key"> [clave] </ span> Cambiar';
     $lang->history_format = '<span class="source"> [fuente] </ span> <span class="target"> en el [objetivo] </ span> <span a class="key"> [clave] </ span> Cambiar';

     $lang->project = 'Proyecto';

     $lang->deadline = 'Completa hasta';
     $lang->name = 'Nombre';
     $lang->complete = 'Completa';
     $lang->completed_date = 'Completado';
     $lang->order = 'Orden';
     $lang->package = $lang->package_srl = 'Paquete';
     $lang->release = $lang->release_srl = 'Distribución';
     $lang->release_note = 'Registro de la distribución';
     $lang->release_changes = 'Cambios';
     $lang->occured_version = $lang->occured_version_srl = 'Planteadas versión';
     $lang->attached_file = 'Adjuntos';
     $lang->filename = 'Nombre del archivo';
     $lang->filesize = 'Tamaño de archivo';

     $lang->status_list = array(
             'new' => 'Nuevo',
             'reviewing' => 'Geomtojung',
             'assign' => 'Asignación',
             'resolve' => 'Resolver',
             'reopen' => 'Recurrencia',
             'postponed' => 'Pendiente',
             'duplicated' => 'Duplicar',
             'invalid' => 'Problemas o',
    );

     $lang->about_milestone = 'Establecer el plan de desarrollo';
     $lang->about_priority = 'Para establecer las prioridades.';
     $lang->about_type = 'Establece el tipo de problema (por ejemplo, los problemas, las mejoras)';
     $lang->about_component = 'Establece el objetivo de componentes del problema';

     $lang->project_menus = array(
             'dispIssuetrackerViewMilestone' => 'Plan de desarrollo',
             'dispIssuetrackerViewIssue' => 'Problemas de lectura',
             'dispIssuetrackerNewIssue' => 'Crear problemas',
             'dispIssuetrackerTimeline' => 'Cronología',
             'dispIssuetrackerViewSource' => 'Código de Lectura',
             'dispIssuetrackerDownload' => 'Descargar',
             'dispIssuetrackerAdminProjectSetting' => 'Escenario',
    );

    $lang->msg_not_attched = 'Por favor, adjunte el archivo';
    $lang->msg_attached = 'El archivo se ha registrado';
    $lang->msg_no_releases = 'Distribuciones no son registrados';

    $lang->cmd_document_do = 'Este problema ..';
    $lang->not_assigned = 'No asignado';
    $lang->not_assigned_description = 'No es asignado una lista de cuestiones.';
    $lang->timeline_msg = array(
        'changed' => 'Cambiar',
        'created' => 'Creación'
    );

    $lang->cmd_manage_issue = 'Cuestiones de gestión';
?>
