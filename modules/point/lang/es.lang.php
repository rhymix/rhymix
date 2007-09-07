<?php
    /**
     * @archivo   modules/point/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paqqete del idioma español del módulo de puntos
     **/

    $lang->point = "Punto"; 
    $lang->level = "Nivel"; 

    $lang->about_point_module = "Usted puede entregar puntos a las acciones de escribir/agregar comentarios/subir/descargar.<br />Pero sólo se puede configurar en módulo de puntos, y los puntos sólo seran acumulados luego de haber activado addon de puntos";
    $lang->about_act_config = "Cada módulo ya sea el tablero o blog tiene sus propias actiones como escribir/eliminar/agregar comentarios/eliminar comentarios/etc. <br />Usted puede añadir valores de actos para enlazar con sistema de módulo de puntos excepto el tablero y blog.<br />El enlace debe hacerse con una coma(,).";

    $lang->max_level = 'Nivel Máximo';
    $lang->about_max_level = 'Usted puede definir el nivel máximo. Los íconos del nivel deberan ser considerados y el nivel máximo de puntos límite es 1000';

    $lang->level_icon = 'Iconos del nivel';
    $lang->about_level_icon = 'Ruta de íconos del nivel es ./module/point/icons/[level].gif y el nivel máximo puede ser diferente con el conjunto de íconos. Tenga cuidado';

    $lang->point_name = 'Nombre del punto';
    $lang->about_point_name = 'Usted puede otorgar nombre o unidad de punto';

    $lang->level_point = 'Nivel del punto';
    $lang->about_level_point = 'El nivel sera ajustado cuando los puntos alcancen a cada nivel de puntos o cuando disminuyen a cada nivel de puntos';

    $lang->disable_download = 'Prohibida la descarga';
    $lang->about_disable_download = "Se prohibe la descarga de archivos al tener los puntos insuficientes.. (Excepto los archivos de imagen)";

    $lang->about_module_point = "Usted puede definir los puntos para cada módulo y los módulos que no tengan ningun valor usarán punto predefinido.<br />Todos los puntos serán restituidos al actuar en forma contraria.";

    $lang->point_insert_document = 'Al escribir documento';
    $lang->point_delete_document = 'Al borrar documento';
    $lang->point_insert_comment = 'Al agregar comentarios';
    $lang->point_delete_comment = 'Al eliminar comentarios';
    $lang->point_upload_file = 'Al subri archivos';
    $lang->point_delete_file = 'Al borrar archivos';
    $lang->point_download_file = 'Al descargar archivos (Excepto imágenes)';


    $lang->cmd_point_config = 'Configuración predefinida';
    $lang->cmd_point_module_config = 'Configuración del módulo';
    $lang->cmd_point_act_config = 'Configuración de actos';
    $lang->cmd_point_member_list = 'Lista de puntos del usuario';

    $lang->msg_cannot_download = "No tiene puntos suficientes para descagar";
?>
