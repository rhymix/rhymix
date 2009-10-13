<?php
    /**
     * @archivo   modules/point/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paqqete del idioma español del módulo de puntos
     **/

    $lang->point = "Punto"; 
    $lang->level = "Nivel"; 

    $lang->about_point_module = "Usted puede entregar puntos a las acciones de escribir/agregar comentarios/subir/descargar.";
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
    $lang->disable_read_document = '글 열람 금지';
    $lang->about_disable_read_document = '포인트가 없을 경우 글 열람을 금지하게 됩니다';

    $lang->level_point_calc = 'Punto por punto cálculo';
    $lang->expression = 'Por favor, de entrada mediante el uso de Javascript fórmula nivel variable <b> i </ b>. Ex) Math.pow (i, 2) * 90';
    $lang->cmd_exp_calc = 'Calcular';
    $lang->cmd_exp_reset = 'Restablecer';

    $lang->cmd_point_recal = '포인트 초기화';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일/회원가입 점수만 이용하여 모든 포인트 점수를 초기화 합니다.<br />회원 가입 점수는 초기화 후 해당 회원이 활동을 하면 부여되고 그 전에는 부여되지 않습니다.<br />데이터 이전등을 하여 포인트를 완전히 초기화 해야 할 경우에만 사용하세요.';

    $lang->point_link_group = 'Grupo de cambio de nivel';
    $lang->point_group_reset_and_add = '설정된 그룹 초기화 후 새 그룹 부여';
    $lang->point_group_add_only = '새 그룹만 부여';
    $lang->about_point_link_group = 'Si especifica nivel para un grupo específico, a los usuarios se les asigna en el grupo cuando se adavnce al nivel por conseguir puntos.';

    $lang->about_module_point = "Usted puede definir los puntos para cada módulo y los módulos que no tengan ningun valor usarán punto predefinido.<br />Todos los puntos serán restituidos al actuar en forma contraria.";

    $lang->point_signup = 'Fecha del';
    $lang->point_insert_document = 'Al escribir documento';
    $lang->point_delete_document = 'Al borrar documento';
    $lang->point_insert_comment = 'Al agregar comentarios';
    $lang->point_delete_comment = 'Al eliminar comentarios';
    $lang->point_upload_file = 'Al subri archivos';
    $lang->point_delete_file = 'Al borrar archivos';
    $lang->point_download_file = 'Al descargar archivos (Excepto imágenes)';
    $lang->point_read_document = 'El Reading';
    $lang->point_voted = '추천 받음';
    $lang->point_blamed = '비추천 받음';


    $lang->cmd_point_config = 'Configuración predefinida';
    $lang->cmd_point_module_config = 'Configuración del módulo';
    $lang->cmd_point_act_config = 'Configuración de actos';
    $lang->cmd_point_member_list = 'Lista de puntos del usuario';

    $lang->msg_cannot_download = "No tiene puntos suficientes para descagar";
    $lang->msg_disallow_by_point = "포인트가 부족하여 글을 읽을 수 없습니다 (필요포인트 : %d, 현재포인트 : %d)";

    $lang->point_recal_message = 'Ajuste de Punto. (%d / %d)';
    $lang->point_recal_finished = 'Punto cálculo está acabado.';
?>
