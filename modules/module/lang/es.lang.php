<?php
    /**
     * @archivo   modules/module/lang/es.lang.php
     * @autor zero (zero@nzeo.com)
     * @sumario  Paquete del idioma español 
     **/

    $lang->module_list = "Lista de Módulos";
    $lang->module_index = "Lista de Módulos";
    $lang->module_category = "Categoría de Módulos ";
    $lang->module_info = "Información";
    $lang->add_shortcut = "Añadir en el menú del administrador";
    $lang->module_action = "Acción";
    $lang->module_maker = "Módulo del desarrollador";
    $lang->module_history = "Historia de actualización";
    $lang->category_title = "Título de categoría";
    $lang->header_text = 'Encabezado';
    $lang->footer_text = 'Pie de página';
    $lang->use_category = 'Usar categoría';
    $lang->category_title = 'Título de categoría';
    $lang->checked_count = 'Múmero de los documentos selecionados';
    $lang->skin_default_info = 'información del tema predefinido';
    $lang->skin_maker = 'Desarrollador de temas';
    $lang->skin_maker_homepage = "Página Web del desarrollador de temas";
    $lang->open_rss = 'Abrir RSS';
    $lang->open_rss_types = array(
        'Y' => 'Abrir todo',
        'H' => 'Abrir el sumario',
        'N' => 'No abrir',
    );
    $lang->module_copy = "Copy Module";

    $lang->cmd_add_shortcut = "añadir acceso directo";
    $lang->cmd_install = "Instalar";
    $lang->cmd_update = "Actualizar";
    $lang->cmd_manage_category = 'Manejo de categorías';
    $lang->cmd_manage_grant = 'Manejo de atribuciones';
    $lang->cmd_manage_skin = 'Manejo de temas';
    $lang->cmd_manage_document = 'Manejo de documentos';

    $lang->msg_new_module = "Crear un nuevo módulo";
    $lang->msg_update_module = "Modificar el módulo";
    $lang->msg_module_name_exists = "El nombre ya existe. Por favor tratar con otro nombre.";
    $lang->msg_category_is_null = 'No existe categoría registrada.';
    $lang->msg_grant_is_null = 'No existe el objetivo de atribución registrado.';
    $lang->msg_no_checked_document = 'No existe documento seleccionado.';
    $lang->msg_move_failed = 'No pudo moverse';
    $lang->msg_cannot_delete_for_child = 'No puede eliminar la categoría si posee subcategoría.';

    $lang->about_browser_title = "Esto es el valor que se mostrará en el título del navegador. También es usado en RSS/Trackback.";
    $lang->about_mid = "El nombre del módulo será usado como http://direccion/?mid=NombreMódulo.\n(sólo alfabeto español, números y el gión abajo(_) son permitidos)";
    $lang->about_default = "Si selecciona esta opción, se mostrara de manera predefinida sin el valor de mid.";
    $lang->about_module_category = "Es posible manejar a traves de categoría.\n EL URL para en manejo del módulo de categoría es <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manejo de módulo > Módulo de categoría </a>.";
    $lang->about_description= 'Descripción usada para la administración.';
    $lang->about_default = 'Si selecciona esta opción, se mostrará de manera predefinida sin el valor de mid.';
    $lang->about_header_text = 'El contenido se mostrará en la parte superior del módulo.(tags de html permitido)';
    $lang->about_footer_text = 'El contenido se mostrará en la parte inferior del módulo.(tags de html permitido)';
    $lang->about_skin = 'Usted puede elegir un tema del módulo.';
    $lang->about_use_category = 'Si selecciona esta opción, la función de categoría sera activada.';
    $lang->about_list_count = 'Usted puede definir el número límite de los documentos a mostrar en una página.(Predefinido es 1)';
    $lang->about_page_count = 'Usted puede definir el número de página enlazada para mover páginas en un botón de la página.(Predefinido es 10)';
    $lang->about_admin_id = 'Usted puede definir el administrador de atribuciones superiores al módulo.\n Usted puede asignar múltiples IDs,<br />utilizando una ","(coma) \n(pero el administrador del módulo no puede acceder al sitio de la pógina del administrador.)';
    $lang->about_grant = 'Si usted desea desactivar a todos los objetos teniendo atribuciones especificas, incluso el usuario no conectado pueden tener atribuciones.';
    $lang->about_open_rss = 'Usted puede seleccionar RSS abierto al público en el módulo actual.\nIndependiente de la atribución de ver, dependiendo de la opción de RSS puede ser abierto al público.';
    $lang->about_module = "Zeroboard XE consiste de módulos excepto la librería básica.\n Módulo del Manejo del Módulo muestra todos los módulos instalados y ayuda el manejo de ellos.\nLos módulos usados frecuentemente puede manejar fácilmente a través de función de [Añadir acceso directo].";

	$lang->about_extra_vars_default_value = 'Si múltiples valores predefinidos son requeridos, usted puede enlazar con la coma(,).';
?>
