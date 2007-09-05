<?php
    /**
     * @archivo   modules/menu/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario  Paquete del idioma espanol para el Menu del modulo basico
     **/

    $lang->cmd_menu_insert = 'Crear Menu';
    $lang->cmd_menu_management = 'Comfiguracion del Menu';

    $lang->menu = 'Menu'; 
    $lang->menu_count = 'Numero del menu';
    $lang->menu_management = 'Configuracion del Menu';
    $lang->depth = 'Nivel';
    $lang->parent_menu_name = 'Nombre del Menu principal';
    $lang->menu_name = 'Nombre del menu';
    $lang->menu_srl = 'Numero caracteristico del menu';
    $lang->menu_id = 'ID del menu';
    $lang->menu_url = 'URL del Menu';
    $lang->menu_open_window = 'Abrir una nueva ventana';
    $lang->menu_expand = 'Expandir';
    $lang->menu_img_btn = 'Boton de imagen';
    $lang->menu_normal_btn = 'Normal';
    $lang->menu_hover_btn = 'Mouse arriba';
    $lang->menu_active_btn = 'Al momento de seleccionar';
    $lang->menu_group_srls = 'Grupos accesibles';
    $lang->layout_maker = "Marcar el diseno";
    $lang->layout_history = "Actualizar la historia";
    $lang->layout_info = "Informacion del diseno";
    $lang->layout_list = 'Lista del diseno';
    $lang->downloaded_list = 'Lista de descarga';
    $lang->limit_menu_depth = 'Exhibicion permitido';

    $lang->cmd_make_child = 'Agregar un submenu';
    $lang->cmd_move_to_installed_list = "Ver la lista de los creadores";
    $lang->cmd_enable_move_menu = "Mover el menu (Mover hacia el menu superior luego de seleccionar)";
    $lang->cmd_search_mid = "Buscar mid";

    $lang->msg_cannot_delete_for_child = 'No se puede eliminar un menu si posee un submenu.';

    $lang->about_title = 'Ingresar el titulo que sea facil de identificar al momento de conectar al modulo.';
    $lang->about_menu_management = "Configuracion del menu permite construir el menu del diseno seleccionado.\nUsted puede crear el menu hasta el nivel seleccionado y para la informacion mas detallada debe seleccionar el menu ingresado.\nEl menu sera expandido al presionar la imagen del directorio.\nSi el menu no se ve correctamente, renueva la informacion presionado el boton\"Re-crear archivo cache\".\n* El mene creado sobre el nivel limite podria no verse apropiadamente.";
    $lang->about_menu_name = 'El nombre se vera como un nombre de menu si no es un boton del administrador o boton de imagen.';
    $lang->about_menu_url = "Este es el URL al momento de seleccionar el menu.<br />Si desea conectar a otro modulo solo debe colocar el valor de id.<br />Si no hay contenido, no pasara nada aun cuando haya presinado el menu.";
    $lang->about_menu_open_window = 'Usted puede asignar si desea abrir la pagina en una nueva ventana al momento de presionar el menu.';
    $lang->about_menu_expand = 'Al usar el menu arbol(tree_menu.js) puede mantener el menu expandido.';
    $lang->about_menu_img_btn = 'Si Usted registra un boton de imagen, ese boton automaticamente reemplazara el boton de texto, y se mostrara en el diseno.';
    $lang->about_menu_group_srls = 'Si tu selecciona el grupo, solo los usuarios del grupo pueden ser el menu. (Si el archivo xml es abierto directamente, lo mostrara.)';

    $lang->about_menu = "Modulo del menu te ayudara a crear un sitio completo a traves de un conveniente manejo del menu que ordena los modulos creados y conecta con el diseno si ningun otro trabajo.\nEl menu no es un manejador del sitio, sino que permite conectar los modulos con el diseno y a traves del diseno puede expresar variados estilos del menu.";
?>
