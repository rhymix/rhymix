<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  Đào Đức Duy (ducduy.dao.vn@vietxe.net)								  ░░
			░░	* @Website:  http://vietxe.net													  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░	   		*/

    $lang->cmd_layout_management = 'Thiết lập giao diện';
    $lang->cmd_layout_edit = 'Sửa giao diện';

    $lang->layout_name = 'Tên giao diện';
    $lang->layout_maker = "Người tạo";
    $lang->layout_license = 'Giấy phép';
    $lang->layout_history = "Cập nhật";
    $lang->layout_info = "Thông tin giao diện";
    $lang->layout_list = 'Danh sách giao diện';
    $lang->menu_count = 'Menu';
    $lang->downloaded_list = 'Danh sách Download';
    $lang->layout_preview_content = 'Khu vực nội dung sẽ hiển thị.';
    $lang->not_apply_menu = 'Áp dụng giao diện';
	$lang->layout_management = '레이아웃 관리';

    $lang->cmd_move_to_installed_list = "Danh sách đã tạo";

    $lang->about_downloaded_layouts = "Danh sách đã Download";
    $lang->about_title = 'Xin hãy nhập tiêu đề của giao diện cho dễ dàng lựa chọn về sau.';
    $lang->about_not_apply_menu = 'Nếu chọn, tất cả các giao diện đang sử dụng sẽ được thay đổi thành giao diện này.';

    $lang->about_layout = "Module giao diện giúp bạn tạo ra giao diện của Website một cách dễ dàng.<br />Bằng cách sử dụng thiết lập giao diện và kết nối Menu, hình dạng hoàn thành của Website sẽ được trình bày bổ xung với nhiều Module.<br />Giao diện nào xuất hiện (<font color='red'>*</font>) là những giao diện không thể xóa hay điều chỉnh được Module. ";
    $lang->about_layout_code = 
        "Nó sẽ được áp dụng vào Website ngay khi bạn bấm '<b>Lưu</b>' sau khi sửa đổi.
        Hãy bấm '<b>Xem trước</b>' trước khi bấm '<b>Lưu</b>'.
        Bạn có thể tham khảo cách sửa giao diện tại <a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\"><b>XE Template</b></a>.";

    $lang->layout_export = 'Xuất ra';
    $lang->layout_btn_export = 'Download giao diện của tôi';
    $lang->about_layout_export = 'Xuất giao diện sửa chữa hiện tại.';
    $lang->layout_import = 'Nhập vào';
    $lang->about_layout_import = 'Giao diện nguyên bản sẽ bị xóa khi bạn nhập vào. Hãy xuất ra để lưu giao diện hiện thời trước khi nhập vào.';

    $lang->layout_manager = array(
        0  => 'Quản lý giao diện',
        1  => 'Lưu lại',
        2  => 'Loại bỏ',
        3  => 'Form',
        4  => 'Giãn ra',
        5  => 'Thu lại',
        6  => 'Cố định giao diện',
        7  => 'Giao diện biến thiên',
        8  => 'Cố định+Biến thiên (Nội dung)',
        9  => '1 ô',
        10 => '2 ô (Trái của nội dung)',
        11 => '2 ô (Phải của nội dung)',
        12 => '3 ô (Trái của nội dung)',
        13 => '3 ô (Giữa của nội dung)',
        14 => '3 ô (Phải của nội dung)',
        15 => 'Trái',
        16 => 'Giữa',
        17 => 'Phải',
        18 => 'Dàn đều',
        19 => 'Giao diện',
        20 => 'Thêm Widget',
        21 => 'Thêm Widget nội dung',
        22 => 'Thuộc tính',
        23 => 'Kiểu dáng Widget',
        24 => 'Điều chỉnh',
        25 => 'Xóa',
        26 => 'Căn chỉnh',
        27 => 'Chiếm 1 hàng',
        28 => 'Trái',
        29 => 'Phải',
        30 => 'Chiều rộng',
        31 => 'Chiều cao',
        32 => 'Lề',
        33 => 'Lót',
        34 => 'Đỉnh',
        35 => 'Trái',
        36 => 'Phải',
        37 => 'Dưới',
        38 => 'Viền', 
        39 => 'Không',
        40 => 'Nền',
        41 => 'Màu',
        42 => 'Hình ảnh',
        43 => 'Lựa chọn',
        44 => 'Lặp lại nền',
        45 => 'Lặp lại',
        46 => 'Không lặp',
        47 => 'Lặp lại chiều rộng',
        48 => 'Lặp lại chiều cao',
        49 => 'Áp dụng',
        50 => 'Loại bỏ',
        51 => 'Thiết lập lại',
        52 => 'Chữ',
        53 => 'Kiểu chữ',
        54 => 'Màu chữ',
    );

    $lang->layout_image_repository = 'Nơi chứa giao diện';
    $lang->about_layout_image_repository = 'Bạn có thể Upload File hình ảnh hoặc Flash cho giao diện đã chọn. Nó sẽ đi kèm khi xuất giao diện ra.';
    $lang->msg_layout_image_target = 'Chỉ cho phép những định dạng File: .gif, .png, .jpg, .swf, .flv';
    $lang->layout_migration = 'Di chuyển giao diện';
    $lang->about_layout_migration = 'You can export or import editted layout as tar file'."\n".'(So far only FaceOff supports exports/imports)';

    $lang->about_faceoff = array(
        'title' => 'Quản lý giao diện XpressEngine FaceOff',
        'description' => 'Quản lý giao diện FaceOff sẽ giúp bạn tao ra một giao diện cho riêng mình một cách dễ dàng.<br/>Xin hãy thiết kế giao diện của mình với những thành phần và những chức năng hiển thị phía dưới.',
        'layout' => 'FaceOff có cấu trúc HTML như trên.<br/>bạn có thể thu vào hoặc giãn ra với CSS, hay sử dụng kiểu dáng để thiết kế.<br/>Bạn có thể thêm Widget từ phần mở rộng (e1, e2), Neck và Knee.<br/>Ngoài ra Body, Giao diện, Header, Body, Footer có thể được thiết kế theo kích cỡ, và Content sẽ hiển thị nội dung.',
        'setting' => 'Menu phía bên trái.<br/><ul><li>"Lưu lại": là lưu lại những thiết lập hiện tại.</li><li>"Loại bỏ": là bỏ qua những thay đổi hiện tại và trở lại.</li><li>"Thiết lập lại": là xóa bỏ tất cả những thay đổi.</li><li>"Form": đặt Form dạng Cố định, Biến thiên, Cố định+Biến thiên (Nội dung).</li><li>"Thu nhỏ": là thu nhỏ hai phần mở rộng và nội dung.</li><li>"Căn chỉnh" : là sắp xếp sự thẳng hàng.</li></ul>',
        'hotkey' => 'Bạn có thể thiết kế giao diện của mình dễ dàng hơn nữa với những phím tắt.<br/><ul><li>"Tab": trừ khi một Widget được chọn, Header, Body, Footer sẽ được chọn trong lệnh. Nếu không, Widget tiếp theo sẽ được chọn.</li><li>"Shift+Tab": nó ngược lại với phím "Tab".</li><li>"Esc": Nếu không có gì được chọn, Neck, Extension (e1, e2 ), Knee sẽ được lựa chọn theo thứ tự, nếu một Widget được chọn, kích thước Widget sẽ được lựa chọn.</li><li>"4 phím mũi tên": Nếu Widget đã được chọn, nó sẽ di chuyển Widget tới một vị trí mới.</li></ul>',
        'attribute' => 'Bạn có thể đặt màu nền / hình nền tới mọi khu vực trừ Widget, và màu chữ (bao gồm cả Tag).',

    );
	$lang->mobile_layout_list = "Mobile Layout List";
	$lang->mobile_downloaded_list = "Downloaded Mobile Layouts";
	$lang->apply_mobile_view = "Apply Mobile View";
	$lang->about_apply_mobile_view = "All connected module use mobile view to display when accessing with mobile device.";
?>
