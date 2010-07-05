<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  zero (zero@nzeo.com)                                                 ░░
			░░  * @Trans  :  DucDuy Dao (webmaster@xpressengine.vn)								  ░░
			░░	* @Website:  http://xpressengine.vn												  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
*/
			
    $lang->admin_info = 'Thông tin Administrator';
    $lang->admin_index = 'Trang chủ Admin';
    $lang->control_panel = 'Bảng điều khiển';
    $lang->start_module = 'Module trang chủ';
    $lang->about_start_module = 'Bạn có thể chọn một Module và đặt là trang chủ của Website.';

    $lang->module_category_title = array(
        'service' => 'Thiết lập dịch vụ',
        'member' => 'Thiết lập thành viên',
        'content' => 'Thiết lập nội dung',
        'statistics' => 'Thống kê',
        'construction' => 'Xây dựng giao diện',
        'utility' => 'Thiết lập tiện ích',
        'interlock' => 'Tiện ích nâng cao',
        'accessory' => 'Dịch vụ phụ',
        'migration' => 'Chuyển đổi dữ liệu',
        'system' => 'Thiết lập hệ thống',
    );

    $lang->newest_news = "Tin mới nhất";
    
    $lang->env_setup = "Thiết lập ";
    $lang->default_url = "URL mặc định";
    $lang->about_default_url = "Nếu bạn sử dụng tính năng trang Web ảo (Ví dụ: PlanetXE, cafeXE), hãy chọn URL mặc định (địa chỉ trang chủ), khi khi kích hoạt SSO với thư mục hay Module làm việc.";

    $lang->env_information = "Thông tin";
    $lang->current_version = "Phiên bản";
    $lang->current_path = "Thư mục cài đặt";
    $lang->released_version = "Phiên bản mới nhất";
    $lang->about_download_link = "Đã có phiên bản mới nhất của XE.\n Hãy bấm vào Link để Download.";
    
    $lang->item_module = "Danh sách Module";
    $lang->item_addon  = "Danh sách Addon";
    $lang->item_widget = "Danh sách Widget";
    $lang->item_layout = "Danh sách Layout";

    $lang->module_name = "Tên Module";
    $lang->addon_name = "Tên Addon";
    $lang->version = "Phiên bản";
    $lang->author = "Thiết kế";
    $lang->table_count = "Table";
    $lang->installed_path = "Thư mục đã cài đặt";

    $lang->cmd_shortcut_management = "Sửa Menu";

    $lang->msg_is_not_administrator = 'Dành riêng Administrator';
    $lang->msg_manage_module_cannot_delete = 'Không thể xóa những phím tắt của Module, Addon, Layout, Widget.';
    $lang->msg_default_act_is_null = 'Phím tắt đã không được tạo, bởi vì bạn không được đặt quyền là quản lý toàn diện.';

    $lang->welcome_to_xe = 'Chào mừng bạn đến với trang quản lý của XE!';
    $lang->about_admin_page = "Trang Admin này vẫn đang được phát triển,\n Chúng tôi sẽ thêm vào những nội dung chủ yếu từ những ý kiến của người sử dụng.";
    $lang->about_lang_env = "Để hiển thị ngôn ngữ đã chọn là mặc định. Hãy bấm [Lưu] phía dưới để lưu lại.";

    $lang->xe_license = 'XE sử dụng giấy phép GPL';
    $lang->about_shortcut = 'Bạn có thể loại bỏ phím tắt của Module được sử dụng thường xuyên trên danh sách.';

    $lang->yesterday = "Hôm qua";
    $lang->today = "Hôm nay";

    $lang->cmd_lang_select = "Ngôn ngữ";
    $lang->about_cmd_lang_select = "Chỉ chọn được những ngôn ngữ có sẵn.";
    $lang->about_recompile_cache = "Bạn có thể sắp xếp lại File Cache cho những việc đã làm hoặc bị lỗi.";
    $lang->use_ssl = "Sử dụng SSL";
    $lang->ssl_options = array(
        'none' => "Không sử dụng",
        'optional' => "Tùy chỉnh",
        'always' => "Luôn luôn"
    );
    $lang->about_use_ssl = "Nếu bạn chọn 'Tùy chỉnh', SSL sẽ sử dụng và những công việc như đăng kí, sửa thông tin thành viên, .<br />Chỉ chọn 'Luôn luôn' khi Website của bạn đang chạy trên Server có hỗ trợ https.";
    $lang->server_ports = "Cổng kết nối";
    $lang->about_server_ports = "Nếu Host của bạn sử dụng cổng khác cổng mặc định 80 cho HTTP, 443 cho HTTPS, bạn nên xác định và nhập chính xác cổng kết nối.";
    $lang->use_db_session = 'Xác nhận Database';
    $lang->about_db_session = 'PHP sẽ xác nhận với Database. Có thể cải thiện được tốc độ của Website.';
    $lang->sftp = "Sử dụng SFTP";
    $lang->ftp_get_list = "Nhận danh sách";
    $lang->ftp_remove_info = 'Xóa thông tin FTP.';
	$lang->msg_ftp_invalid_path = 'Không tìm thấy thông tin của thư mục bạn đã nhập trên FTP.';
	$lang->msg_self_restart_cache_engine = 'Hãy thiết lập lại bộ nhớ Cache hoặc Deamon Cache.';
	$lang->mobile_view = 'Xem bằng di động';
	$lang->about_mobile_view = 'Nếu truy cập bằng thiết bị di động, nội dung sẽ được bố trí theo từng loại thiết bị.';
    $lang->autoinstall = 'Cập nhật tự động';
?>
