<?php
    /**
     * @file   es.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  CafeXE (homepage) del módulo, el idioma por defecto
     **/

    $lang->cafe = 'CafeXE'; 
    $lang->cafe_id = "카페 접속 ID"; 
    $lang->cafe_title = 'Nombre de café';
    $lang->cafe_description = 'Description of cafe';
    $lang->cafe_banner = 'Banner of Cafe';
    $lang->module_type = 'Meta';
    $lang->board = 'Boletines';
    $lang->page = 'Página';
    $lang->module_id = 'Módulo ID';
    $lang->item_group_grant = 'Mostrar grupo';
    $lang->cafe_info = 'Cafe Información';
    $lang->cafe_admin = 'Gerente de café';
    $lang->do_selected_member = 'Los miembros seleccionados: ';
    $lang->cafe_latest_documents = '카페 최신 글';
    $lang->cafe_latest_comments = '카페 최신 댓글';
    $lang->mycafe_list = '가입한 카페';
    $lang->cafe_creation_type = '카페 접속 방법';
    $lang->about_cafe_creation_type = '사용자들이 카페를 생성할때 카페 접속 방법을 정해야 합니다. Site ID는 http://기본주소/ID 로 접속 가능하고 Domain 접속은 입력하신 도메인의 2차 도메인(http://domain.mydomain.net) 으로 카페가 생성됩니다';
    $lang->cafe_main_layout = '카페 메인 레이아웃';

    $lang->default_layout = '기본 레이아웃';
    $lang->about_default_layout = '카페가 생성될때 설정될 기본 레이아웃을 지정할 수 있습니다';
    $lang->enable_change_layout = '레이아웃 변경';
    $lang->about_change_layout = '선택하시면 개별 카페에서 레이아웃 변경을 허용할 수 있습니다';
    $lang->allow_service = '허용 서비스';
    $lang->about_allow_service = '개별 카페에서 사용할 기본 서비스를 설정할 수 있습니다';

    $lang->cmd_make_cafe = 'Café creación';
    $lang->cmd_import = 'Import';
    $lang->cmd_export = 'Export';
    $lang->cafe_creation_privilege = 'Café creación privilegio';

    $lang->cafe_main_mid = '카페 메인 ID';
    $lang->about_cafe_main_mid = '카페 메인 페이지를 http://주소/ID 값으로 접속하기 위한 ID값을 입력해주세요.';

    $lang->default_menus = array(
        'home' => 'Inicio',
        'notice' => 'Anuncios',
        'levelup' => 'Deungeopsincheong',
        'freeboard' => 'Tablón de anuncios',
        'view_total' => 'Ver el artículo completo',
        'view_comment' => 'Historia',
        'cafe_album' => 'Café álbum',
        'menu' => 'Menú',
        'default_group1' => 'Miembro de espera',
        'default_group2' => 'Asociado',
        'default_group3' => 'Miembro',
    );

    $lang->cmd_admin_menus = array(
        'dispHomepageManage' => 'Café Conjunto',
        'dispHomepageMemberGroupManage' => 'Miembro Grupo de Gestión',
        'dispHomepageMemberManage' => 'Lista de miembros',
        'dispHomepageTopMenu' => 'Menú Principal de Gestión de',
        "dispHomepageComponent" => "Conjunto de características",
        'dispHomepageCounter' => 'Estadísticas de acceso',
        'dispHomepageMidSetup' => 'Módulo detalle establecido',
    );
    $lang->cmd_cafe_registration = 'La creación de Cafe';
    $lang->cmd_cafe_setup = 'Café Conjunto';
    $lang->cmd_cafe_delete = 'Eliminar Cafe';
    $lang->cmd_go_home = 'Ir a Portada';
    $lang->cmd_go_cafe_admin = 'Cafe Todos Administrar';
    $lang->cmd_change_layout = '변경';
    $lang->cmd_select_index = '초기화면 선택';
    $lang->cmd_add_new_menu = '새로운 메뉴 추가';
    $lang->default_language = '기본 언어';
    $lang->about_default_language = '처음 접속하는 사용자의 언어 설정을 지정할 수 있습니다.';

    $lang->about_cafe_act = array(
        'dispHomepageManage' => 'Cafe의 모양을 꾸밀 수 있습니다',
        'dispHomepageMemberGroupManage' => 'Cafe 내에서 사용되는 그룹 관리를 할 수 있습니다',
        'dispHomepageMemberManage' => 'Cafe에 등록된 회원들을 보거나 관리할 수 있습니다',
        'dispHomepageTopMenu' => 'Cafe의 상단이나 좌측등에 나타나는 일반적인 메뉴를 수정하거나 추가할 수 있습니다',
        "dispHomepageComponent" => "에디터 컴포넌트/ 애드온을 활성화 하거나 설정을 변경할 수 있습니다",
        'dispHomepageCounter' => 'Cafe의 접속 현황을 볼 수 있습니다',
        'dispHomepageMidSetup' => 'Cafe에서 사용하는 게시판, 페이지등의 모듈 세부 설정을 할 수 있습니다',
    );
    $lang->about_cafe = 'Cafe 서비스 관리자는 다수의 Cafe를 만들 수 있고 또 각 Cafe를 편하게 설정할 수 있도록 합니다.';
    $lang->about_cafe_title = 'Cafe 이름은 관리를 위해서만 사용될 뿐 서비스에는 나타나지 않습니다';
    $lang->about_menu_names = 'Café en el nombre del idioma que aparezca en el menú para que usted pueda especificar. <br/> Puede entrar en un sólo aplica a todos ustedes ';
    $lang->about_menu_option = 'selección de menú puede elegir para abrir saechangeuro. <br /> Menús desplegables se comportarán de acuerdo con el diseño';
    $lang->about_group_grant = 'Cuando aparezca el menú, seleccionar un grupo de grupos seleccionados. <br/> Miembros no pueden ver cuando se apaga todas las';
    $lang->about_module_type = 'Tablones de anuncios, y el enlace URL de la página para crear un módulo. <br/> Saengseonghu no puede ser modificado';
    $lang->about_browser_title = 'Acceso a un menú de su navegador para que aparezca en el título se';
    $lang->about_module_id = 'Boletines, jeopsokhalttae jideung dirección de la página se utiliza. <br/> Sí) de dominio http:// Dominio/ [Módulo ID], dominio http:// Dominio/? Media = [Módulo ID]';
    $lang->about_menu_item_url = 'Cuando es la dirección de la URL como vínculo de conexión. <br/> Http:// excepto para las palabras';
    $lang->about_menu_image_button = 'Menyumyeong pueden usar un menú en lugar de la imagen.';
    $lang->about_cafe_delete = 'Todos los módulos están conectados en caso de supresión de Café (Boletín, página jideung) y será suprimido en consecuencia geuldeulyi. <br /> ¿Necesita atención';
    $lang->about_cafe_admin = 'Cafe Manager se puede configurar. <br/> Cafe Manager dirección es http:// /? = Ley dispHomepageManage el administrador puede conectarse a una página que no existe, el usuario no está registrado como administrador';

    $lang->confirm_change_layout = 'Si cambia el diseño de algunas de la información relativa al diseño puede desaparecer. ¿Te gustaría cambiar?';
    $lang->confirm_delete_menu_item = 'Eliminación de un elemento de menú o de la página, el módulo está conectado con el boletín se eliminarán. Si desea eliminar?';
    $lang->msg_module_count_exceed = '허용된 모듈의 개수를 초과하였기에 생성할 수 없습니다';
    $lang->msg_not_enabled_id = '사용할 수 없는 아이디입니다';
    $lang->msg_same_site = '동일한 가상 사이트의 모듈은 이동할 수가 없습니다';
    $lang->about_move_module = '가상사이트와 기본사이트간의 모듈을 옮길 수 있습니다.<br/>다만 가상사이트끼리 모듈을 이동하거나 같은 이름의 mid가 있을 경우 예기치 않은 오류가 생길 수 있으니 꼭 가상 사이트와 기본 사이트간의 다른 이름을 가지는 모듈만 이동하세요';
?>
