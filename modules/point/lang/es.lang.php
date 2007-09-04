<?php
    /**
     * @archivo   modules/point/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paqqete del idioma espanol del modulo de puntos
     **/

    $lang->point = "Punto"; 
    $lang->level = "Nivel"; 

    $lang->about_point_module = "Usted puede entregar puntos a las acciones de escribir/agregar comentarios/subir/descargar.<br />Pero solo se puede configurar en modulo de puntos, y los puntos solo seran acumulados luego de haber activado addon de puntos";
    $lang->about_act_config = "Cada modulo ya sea el tablero o blog tiene sus propias actiones como escribir/eliminar/agregar comentarios/eliminar comentarios/etc. <br />Usted puede anadir valores de actos para enlazar con sistema de modulo de puntos excepo el tablero y blog.<br />El enlace debe hacerse con una coma(,).";

    $lang->max_level = 'Nivel Maximo';
    $lang->about_max_level = 'Usted puede definir el nivel maximo. Los iconos del nivel deberan ser considerados y el nivel maximo de puntos limite es 1000';

    $lang->level_icon = 'Iconos del nivel';
    $lang->about_level_icon = 'Ruta de iconos del nivel is ./module/point/icons/[level].gif y el nivel maximo puede ser diferente con el conjunto de iconos. Tenga cuidado';

    $lang->point_name = 'Nombre del punto';
    $lang->about_point_name = 'Usted puede otorgar nombre o unidad de punto';

    $lang->level_point = 'Nivel del punto';
    $lang->about_level_point = 'El nivel sera ajustado cuando los puntos alcancen a cada nivel de puntos o cuando disminuyen a cada nivel de puntos';

    $lang->disable_download = 'Prohibida la descarga';
    $lang->about_disable_download = "Se prohibe la descarga de archivos al tener los puntos insuficientes.. (Excepto los archivos de imagen)";

    $lang->about_module_point = "Usted puede definir los puntos para cada modulo y los modulos que no tengan ningun valor usaran punto predefinido.<br />Todos los puntos seran restituidos al actuar en forma contraria.";

    $lang->point_insert_document = 'Al escribir documento';
    $lang->point_delete_document = 'Al borrar documento';
    $lang->point_insert_comment = 'Al agregar comentarios';
    $lang->point_delete_comment = 'Al eliminar comentarios';
    $lang->point_upload_file = 'Al subri archivos';
    $lang->point_delete_file = 'Al borrar archivos';
    $lang->point_download_file = 'Al descargar archivos (Excepto imagenes)';


    $lang->cmd_point_config = 'Configuracion predefinida';
    $lang->cmd_point_module_config = 'Configuracion del modulo';
    $lang->cmd_point_act_config = 'Configuracion de actos';
    $lang->cmd_point_member_list = 'Lista de puntos del usuario';

    $lang->msg_cannot_download = "No tiene puntos suficientes para la descarga";
?>
