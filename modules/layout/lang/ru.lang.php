<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->cmd_layout_management = 'Настройки лейаута';
    $lang->cmd_layout_edit = 'Редактировать лейаут';

    $lang->layout_name = 'Имя лейаута';
    $lang->layout_maker = "Разработчик лейаута";
    $lang->layout_license = 'License';
    $lang->layout_history = "Обновления";
    $lang->layout_info = "Информация лейаута";
    $lang->layout_list = 'Список лейаутов';
    $lang->menu_count = 'Меню';
    $lang->downloaded_list = 'Список закачек';
    $lang->layout_preview_content = 'Содержимое отображается здесь';
    $lang->not_apply_menu = 'Применить лейауты';
	$lang->layout_management = '레이아웃 관리';

    $lang->cmd_move_to_installed_list = "Просмотреть созданный список";

    $lang->about_downloaded_layouts = "Список скаченных лейаутов";
    $lang->about_title = 'Пожалуйста, введите название, которое легко проверить при подключении к модулю';
    $lang->about_not_apply_menu = 'Все подключенные лейауты модулей будут изменены при включении это опции.';

    $lang->about_layout = "Модуль лейаутов помогает Вам создать лейаут сайта с легкостью.<br />Используя настройки лейаута и подключение меню, полная форма сайта будет отображена множеством модулей.<br />* Теми лейаутами, которые невозможно удалить или изменить, являются лейауты блога и лейауты других модулей.";
    $lang->about_layout_code = 
        "Применения к службе будут проиведены, когда Вы сохраните код лейаут после редактирование.
        Пожалуйста, сначала используйте предпросмотр кода и затем сохраните его.
        Вы можете обратиться к грамматике шаблонов XE с <a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE Template</a>.";

    $lang->layout_export = 'Export';
    $lang->layout_btn_export = 'Download My Layout';
    $lang->about_layout_export = 'Export currently editted layout.';
    $lang->layout_import = 'Import';
    $lang->about_layout_import = 'Original layout will be deleted when you import. Please export current layout before importing.';
    
	$lang->layout_manager = array(
        0  => 'Layout Manager',
        1  => 'Save',
        2  => 'Cancel',
        3  => 'Form',
        4  => 'Array',
        5  => 'Arrange',
        6  => 'Fixed Layout',
        7  => 'Variable Layout',
        8  => 'Fixed+Variable (Content)',
        9  => '1 Cell',
        10 => '2 Cells (left of content)',
        11 => '2 Cells (right of content)',
        12 => '3 Cells (left of content)',
        13 => '3 Cells (center of content)',
        14 => '3 Cells (right of content)',
        15 => 'Left',
        16 => 'Center',
        17 => 'Right',
        18 => 'All',
        19 => 'Layout',
        20 => 'Add Widget',
        21 => 'Add Content Widget',
        22 => 'Attribute',
        23 => 'Widget Style',
        24 => 'Modify',
        25 => 'Delete',
        26 => 'Align',
        27 => 'Occupy a Line',
        28 => 'Left',
        29 => 'Right',
        30 => 'Width',
        31 => 'Height',
        32 => 'Margin',
        33 => 'Padding',
        34 => 'Top',
        35 => 'Left',
        36 => 'Right',
        37 => 'Bottom',
        38 => 'Border', 
        39 => 'None',
        40 => 'Background',
        41 => 'Color',
        42 => 'Image',
        43 => 'Select',
        44 => 'Repeat Background',
        45 => 'Repeat',
        46 => 'No Repeat',
        47 => 'Repeat Width',
        48 => 'Repeat Height',
        49 => 'Apply',
        50 => 'Cancel',
        51 => 'Reset',
        52 => 'Text',
        53 => 'Font',
        54 => 'Font Color',
    );

    $lang->layout_image_repository = 'Layout Repository';
    $lang->about_layout_image_repository = 'You can upload images/flash files for selected layout. They will be included in exports';
    $lang->msg_layout_image_target = 'Only gif, png, jpg, swf, flv files are allowed';
    $lang->layout_migration = 'Layout Migration';
    $lang->about_layout_migration = 'You can export or import editted layout as tar file'."\n".'(So far only FaceOff supports exports/imports)';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff Layout Manager',
        'description' => 'FaceOff Layout Manager willl help you design layout on the web easily.<br/>Please design your own layout with components and functions as shown below.',
        'layout' => 'FaceOff has HTML structure as above.<br/>You can arrange/align with CSS, or use Style to design.<br/>You can add widget from Extension(e1, e2), Neck and Knee.<br/>Also Body, Layout, Header, Body, Footer can designed by Style, and Content will display content.',
        'setting' => 'Let me explain you the upper menu on left.<br/><ul><li>Save : Save current settings.</li><li>Cancel : Discard current settings and go back.</li><li>Reset : Clear current settings</li><li>Form : Set form as Fixed/ Variable/ Fixed+Variable(Content).</li><li>Arrange : Arrange 2 Extensions and Content.</li><li>Align : Align the position of layout.</li></ul>',
        'hotkey' => 'You can design your layout more easily with Hot Keys.<br/><ul><li>tab : Unless a widget is selected, Header, Body, Footer will be selected in order. If not, next widget will be selected.</li><li>Shift + tab : It does the opposite function to tab key.</li><li>Esc : If nothing is selected, Neck, Extension(e1,e2),Knee will be selected in order, if a widget is selected, area of the widget will be selected.</li><li>Arrow Key : If a widget is selected, arrow key will move the widget to other areas.</li></ul>',
        'attribute' => 'You can set background color/image to every area except widget, and font color(include <a> tag).',

    );
	$lang->mobile_layout_list = "Mobile Layout List";
	$lang->mobile_downloaded_list = "Downloaded Mobile Layouts";
	$lang->apply_mobile_view = "Apply Mobile View";
	$lang->about_apply_mobile_view = "All connected module use mobile view to display when accessing with mobile device.";
?>
