<?php
    /**
     * @archivo   modules/module/lang/es.lang.php
     * @autor zero (zero@nzeo.com)
     * @sumario  Paquete del idioma español 
     **/

    $lang->virtual_site = "Virtual Site";
    $lang->module_list = "Lista de Módulos";
    $lang->module_index = "Lista de Módulos";
    $lang->module_category = "Categoría de Módulos ";
    $lang->module_info = "Información";
    $lang->add_shortcut = "Añadir en el menú del administrador";
    $lang->module_action = "Acción";
    $lang->module_maker = "Módulo del desarrollador";
    $lang->module_license = 'License';
    $lang->module_history = "Historia de actualización";
    $lang->category_title = "Título de categoría";
    $lang->header_text = 'Encabezado';
    $lang->footer_text = 'Pie de página';
    $lang->use_category = 'Usar categoría';
    $lang->category_title = 'Título de categoría';
    $lang->checked_count = 'Múmero de los documentos selecionados';
    $lang->skin_default_info = 'información del tema predefinido';
    $lang->skin_author = 'Desarrollador de tema';
    $lang->skin_license = 'License';
    $lang->skin_history = 'Historia de actualización';
    $lang->module_copy = "Copy Module";
    $lang->module_selector = "Module Selector";
    $lang->do_selected = "선택된 것들을...";
    $lang->bundle_setup = "일괄 기본 설정";
    $lang->bundle_addition_setup = "일괄 추가 설정";
    $lang->bundle_grant_setup = "일괄 권한 설정";
    $lang->lang_code = "언어 코드";
    $lang->filebox = "파일박스";

    $lang->header_script = "헤더 스크립트";
    $lang->about_header_script = "html의 &lt;header&gt;와 &lt;/header&gt; 사이에 들어가는 코드를 직접 입력할 수 있습니다.<br />&lt;script, &lt;style 또는 &lt;meta 태그등을 이용하실 수 있습니다";

    $lang->grant_access = "Access";
    $lang->grant_manager = "Management";

    $lang->grant_to_all = "All users";
    $lang->grant_to_login_user = "Logged users";
    $lang->grant_to_site_user = "Joined users";
    $lang->grant_to_group = "Specification group users";

    $lang->cmd_add_shortcut = "añadir acceso directo";
    $lang->cmd_install = "Instalar";
    $lang->cmd_update = "Actualizar";
    $lang->cmd_manage_category = 'Manejo de categorías';
    $lang->cmd_manage_grant = 'Manejo de atribuciones';
    $lang->cmd_manage_skin = 'Manejo de temas';
    $lang->cmd_manage_document = 'Manejo de documentos';
    $lang->cmd_find_module = '모듈 찾기';
    $lang->cmd_find_langcode = 'Find lang code';

    $lang->msg_new_module = "Crear un nuevo módulo";
    $lang->msg_update_module = "Modificar el módulo";
    $lang->msg_module_name_exists = "El nombre ya existe. Por favor tratar con otro nombre.";
    $lang->msg_category_is_null = 'No existe categoría registrada.';
    $lang->msg_grant_is_null = 'No existe el objetivo de atribución registrado.';
    $lang->msg_no_checked_document = 'No existe documento seleccionado.';
    $lang->msg_move_failed = 'No pudo moverse';
    $lang->msg_cannot_delete_for_child = 'No puede eliminar la categoría si posee subcategoría.';

    $lang->about_browser_title = "Esto es el valor que se mostrará en el título del navegador. También es usado en RSS/Trackback.";
    $lang->about_mid = "El nombre del módulo será usado como http://direccion/?mid=NombreMódulo.\n(sólo alfabeto español+[alfabeto español, números y el gión abajo(_)] son permitidos)";
    $lang->about_default = "Si selecciona esta opción, se mostrara de manera predefinida sin el valor de mid.";
    $lang->about_module_category = "Es posible manejar a traves de categoría.\n EL URL para en manejo del módulo de categoría es <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manejo de módulo > Módulo de categoría </a>.";
    $lang->about_description= 'Descripción usada para la administración.';
    $lang->about_default = 'Si selecciona esta opción, se mostrará de manera predefinida sin el valor de mid.';
    $lang->about_header_text = 'El contenido se mostrará en la parte superior del módulo.(tags de html permitido)';
    $lang->about_footer_text = 'El contenido se mostrará en la parte inferior del módulo.(tags de html permitido)';
    $lang->about_skin = 'Usted puede elegir un tema del módulo.';
    $lang->about_use_category = 'Si selecciona esta opción, la función de categoría sera activada.';
    $lang->about_list_count = 'Usted puede definir el número límite de los documentos a mostrar en una página.(Predefinido es 20)';
    $lang->about_search_list_count = 'Usted puede configurar el número de artículos que se exponen cuando se utiliza la función de búsqueda o categoría. (Por defecto es 20)';
    $lang->about_page_count = 'Usted puede definir el número de página enlazada para mover páginas en un botón de la página.(Predefinido es 10)';
    $lang->about_admin_id = 'Usted puede definir el administrador de atribuciones superiores al módulo.\n Usted puede asignar múltiples IDs.';
    $lang->about_grant = 'Si usted desea desactivar a todos los objetos teniendo atribuciones especificas, incluso el usuario no conectado pueden tener atribuciones.';
    $lang->about_grant_deatil = '가입한 사용자는 cafeXE등 분양형 가상 사이트에 가입을 한 로그인 사용자를 의미합니다';
    $lang->about_module = "XE consiste de módulos excepto la librería básica.\n Módulo del Manejo del Módulo muestra todos los módulos instalados y ayuda el manejo de ellos.";

	$lang->about_extra_vars_default_value = 'Si múltiples valores predefinidos son requeridos, usted puede enlazar con la coma(,).';
    $lang->about_search_virtual_site = "가상 사이트(카페XE등)의 도메인을 입력하신 후 검색하세요.<br/>가상 사이트이외의 모듈은 내용을 비우고 검색하시면 됩니다.  (http:// 는 제외)";
    $lang->about_langcode = "언어별로 다르게 설정하고 싶으시면 언어코드 찾기를 이용해주세요";
    $lang->about_file_extension= "%s 파일만 가능합니다.";
?>
