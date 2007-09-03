<?php
    /**
     * @archivo   modules/module/lang/es.lang.php
     * @autor zero (zero@nzeo.com)
     * @sumario  Paquete del idioma espanol 
     **/

    $lang->module_list = "Lista de Modulos";
    $lang->module_index = "Lista de Modulos";
    $lang->module_category = "Categoria de Modulo";
    $lang->module_info = "Informacion";
    $lang->add_shortcut = "Anadir en el menu del administrador";
    $lang->module_action = "Accion";
    $lang->module_maker = "Modulo del desarrollador";
    $lang->module_history = "Historia de actualizacion";
    $lang->category_title = "Titulo de categoria";
    $lang->header_text = 'Encabezado';
    $lang->footer_text = 'Pie de pagina';
    $lang->use_category = 'Usar categoria';
    $lang->category_title = 'Titulo de categoria';
    $lang->checked_count = 'Mumero de los documentos selecionados';
    $lang->skin_default_info = 'informacion del tema predefinido';
    $lang->skin_maker = 'Desarrollador de temas';
    $lang->skin_maker_homepage = "Pagina Web del desarrollador de temas";
    $lang->open_rss = 'Abrir RSS';
    $lang->open_rss_types = array(
        'Y' => 'Abrir todo',
        'H' => 'Abrir el sumario',
        'N' => 'No abrir',
    );

    $lang->cmd_add_shortcut = "anadir acceso directo";
    $lang->cmd_install = "Instalar";
    $lang->cmd_update = "Actualizar";
    $lang->cmd_manage_category = 'Manejo de categorias';
    $lang->cmd_manage_grant = 'Manejo de atribuciones';
    $lang->cmd_manage_skin = 'Manejo de temas';
    $lang->cmd_manage_document = 'Manejo de documentos';

    $lang->msg_new_module = "Crear un nuevo modulo";
    $lang->msg_update_module = "Modificar el modulo";
    $lang->msg_module_name_exists = "El nombre ya existe. Por favor tratar con otro nombre.";
    $lang->msg_category_is_null = 'No existe categoria registrada.';
    $lang->msg_grant_is_null = 'No existe el objetivo de atribucion registrado.';
    $lang->msg_no_checked_document = 'No existe documento seleccionado.';
    $lang->msg_move_failed = 'No pudo moverse';
    $lang->msg_cannot_delete_for_child = 'No puede eliminar categoria si posee subcategoria.';

    $lang->about_browser_title = "Esto es el valor que se mostrara en el titulo del navegador. Tambien es usado en RSS/Trackback.";
    $lang->about_mid = "El nombre del modulo sera usado como http://direccion/?mid=NombreModulo.\n(solo alfabeto espanol, numeros y el gion abajo(_) son permitidos)";
    $lang->about_default = "Si selecciona esta opcion, se mostrara de manera predefinida sin el valor de mid.";
    $lang->about_module_category = "Es posible manejar a traves de categoria.\n EL URL para en manejo del modulo de categoria es <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manejo de modulo > Modulo de categoria </a>.";
    $lang->about_description= 'Descripcion usada para la administracion.';
    $lang->about_default = 'Si selecciona esta opcion, se mostrara de manera predefinida sin el valor de mid.';
    $lang->about_header_text = 'El contenido se mostrara en la parte superior del modulo.(tags de html permitido)';
    $lang->about_footer_text = 'El contenido se mostrara en la parte inferior del modulo.(tags de html permitido)';
    $lang->about_skin = 'Usted puede elegir un tema del modulo.';
    $lang->about_use_category = 'Si selecciona esta opcion, la funcion de categoria sera activada.';
    $lang->about_list_count = 'Usted puede definir el numero limite de los documentos a mostrar en un pagina.(Predefinido es 1)';
    $lang->about_page_count = 'Usted puede definir el numero de pagina enlazada pra mover paginas in un boton de la pagina.(Predefinido es 10)';
    $lang->about_admin_id = 'Usted puede definir el administrador de atribuciones superiores al modulo.\n Usted puede asignar multiples IDs,<br />utilizando una ","(coma) \n(pero el administrador del modulo no puede acceder al sitio de la pagina del administrador.)';
    $lang->about_grant = 'Si usted desea desactivar a todos los objetos teniendo atribuciones especificas, incluso el usuario no conectado pueden tener atribucione.';
    $lang->about_open_rss = 'Usted puede seleccionar RSS abierto al publico en el modulo actual.\nIndependiente de la atribucion de ver, dependiendo de la opcion de RSS puede ser abierto al publico.';
    $lang->about_module = "Zeroboard XE consiste de modulos excepto la libreria basica.\n Modulo del Manejo del Modulo muestra todos los modulos instalados y ayuda el manejo de ellos.\nLos modulos usados frecuentemente puede manejar facilmente a traves de funcion de [Anadir acceso directo].";

	$lang->about_extra_vars_default_value = 'Si multiples valores predefinidos son requeridos, usted puede enlazar con la coma(,).';
?>
