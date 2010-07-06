<?php
    /**
     * @file   modules/layout/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Layout module's basic language pack
     **/

    $lang->cmd_layout_management = 'Layout Setting';
    $lang->cmd_layout_edit = 'Edit Layout';

    $lang->layout_name = 'Layout Name';
    $lang->layout_maker = "Layout Developer";
    $lang->layout_license = 'License';
    $lang->layout_history = "Updates";
    $lang->layout_info = "Layout Info";
    $lang->layout_list = 'Layout List';
    $lang->menu_count = 'Menus';
    $lang->downloaded_list = 'Download List';
    $lang->layout_preview_content = 'The content gets displayed here';
    $lang->not_apply_menu = 'Apply Layouts';

    $lang->cmd_move_to_installed_list = "View created list";

    $lang->about_downloaded_layouts = "List of downloaded layouts";
    $lang->about_title = 'Please input the title that is easy to verify when connecting to module';
    $lang->about_not_apply_menu = 'All connected module\'s layout will be changed by checking this option.';

    $lang->about_layout = "Layout module helps you to create the site's layout easily.<br />By using layout setting and menu connection, website's completed shape will be displayed with various modules.<br />* Those layouts which are unabled to delete or modify are the blog or other module's layout. ";
    $lang->about_layout_code = 
        "It will be applied to the service when you save the layout code after editing it.
        Please first preview your code and then save it.
        You can refer grammar of XE's template from <a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE Template</a>.";

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
