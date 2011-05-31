<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  DucDuy Dao (webmaster@xpressengine.vn)								  ░░
			░░	* @Website:  http://xpressengine.vn												  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
*/

    $lang->introduce_title = 'Cài đặt XE';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);
    $lang->install_condition_title = "Xin hãy kiểm tra những yêu cầu cài đặt.";
    $lang->install_checklist_title = array(
			'php_version' => 'Phiên bản PHP',
            'permission' => 'Sự cho phép',
            'xml' => 'XML Library',
            'iconv' => 'ICONV Library',
            'gd' => 'GD Library',
            'session' => 'Thiết lập Session.auto_start',
            'db' => 'DB',
        );

    $lang->install_checklist_desc = array(
			'php_version' => '[Bắt buộc] Nếu phiên bản của PHP là 5.2.2, XE sẽ không thể cài đặt vì có lỗi.',
            'permission' => '[Bắt buộc] Thư mục cài đặt của XE hay ./files directory\ phải CHMOD thành 707',
            'xml' => '[Bắt buộc] XML Library cần thiết cho việc truyền thông File XML.',
            'session' => '[Bắt buộc] File thiết lập của PHP (php.ini) \'Session.auto_start\' phải là 0 theo thứ tự số cho phiên làm việc của XE hoạt động.',
            'iconv' => '<b>Iconv</b> cần phải được cài đặt cho việc chuyển đổi ngôn ngữ thàng UTFF-8 của những ngôn ngữ khác.',
            'gd' => '<b>GD Library</b> cần phải được cài đặt cho việc chuyển đổi hình ảnh.',
        );

    $lang->install_checklist_xml = 'Cài đặt XML Library';
    $lang->install_without_xml = 'XML Library đã không được cài đặt.';
    $lang->install_checklist_gd = 'Cài đặt GD Library';
    $lang->install_without_gd  = 'GD Library đã không được cài đặt cho sự chuyển đổi hình ảnh.';
    $lang->install_checklist_gd = 'Cài đặt GD Library';
    $lang->install_without_iconv = 'Iconv Library đã không được cài đặt cho việc xử lý những đặc tính.';
    $lang->install_session_auto_start = 'Đã có lỗi xảy ra, có lẽ do sự thiết đặt PHP. session.auto_start không phải là 1';
    $lang->install_permission_denied = 'Sự cho phép của thư mục cài đặt không phải là 707';

    $lang->cmd_agree_license = 'Tôi đã đọc và đồng ý với giấy phép này.';
    $lang->cmd_install_fix_checklist = 'Tôi đã thay đổi để phù hợp với yêu cầu cài đặt.';
    $lang->cmd_install_next = 'Tiếp tục cài đặt';
    $lang->cmd_ignore = 'Bỏ qua';

    $lang->db_desc = array(
        'mysql' => 'Dùng chức năng <b>mysql*()</b> để sử dụng MySql Database.<br />Giao dịch được vô hiệu hóa bởi File Database được tạo ra bởi myisam.',
        'mysqli' => 'Dùng chức năng <b>mysqli*()</b> để sử dụng MySql Database.<br />Giao dịch được vô hiệu hóa bởi File Database được tạo ra bởi myisam.',
        'mysql_innodb' => 'Dùng chức năng <b>innodb</b> để sử dụng MySql Database.<br />Giao dịch được kích hoạt cho innodb',
        'sqlite2' => 'Hỗ trợ <b>sqlite2</b> khi lưu Database thành File.<br />Khi cài đặt, File Database phải được tạo ra tại chỗ không sử dụng được từ Web.<br />(Không khẳng định sẽ hoạt động ổn định)',
        'sqlite3_pdo' => 'Hỗ trợ <b>sqlite3</b> bởi PDO của PHP.<br />Khi cài đặt, File Database phải được tạo ra tại chỗ không sử dụng được từ Web.',
        'cubrid' => 'Sử dụng <b>CUBRID</b> Database.  <a href="#" onclick="window.open(this.href);return false;" class="manual">Hướng dẫn</a>',
        'postgresql' => 'Sử dụng <b>PostgreSql</b> Database.',
        'firebird' => 'Sử dụng <b>firebird</b> Database.',
    );

    $lang->form_title = 'Hãy nhập thông tin Database và thông tin Administrator';
    $lang->db_title = 'Xin hãy nhập thông tin Database';
    $lang->db_type = 'Định dạng Database';
    $lang->select_db_type = 'Xin hãy chọn Database bạn muốn sử dụng.';
    $lang->db_hostname = 'Hostname';
    $lang->db_port = 'Port';
    $lang->db_userid = 'Tên truy cập';
    $lang->db_password = 'Mật khẩu';
    $lang->db_database = 'Tên Database';
    $lang->db_database_file = 'File Database';
    $lang->db_table_prefix = 'Tên Table';

    $lang->admin_title = 'Thông tin Administrator';

    $lang->env_title = 'Cấu hình';
    $lang->use_optimizer = 'Tối ưu hóa';
    $lang->about_optimizer = 'Nếu tối ưu hóa được kích hoạt, người sử dụng sẽ truy cập nhanh hơn vì những File CSS / JS sẽ được nén lại trước khi được tải xuống. <br /> Tuy vậy, sự tối ưu này cũng làm ảnh hưởng một chút tới File CSS và JS. Nếu bạn tắt, Website của bạn tải chậm hơn.';
    $lang->use_rewrite = 'Mod Rewrite';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = "Nếu Host của bạn hỗ trợ Mod Rewrite, khi địa chỉ có dạng <b>http://blah/?document_srl=123</b> sẽ được rút ngắn thành <b>http://blah/123</b>";
	$lang->about_sso = '사용자가 한 번만 로그인하면 기본 사이트와 가상 사이트에 동시에 로그인이 되는 기능입니다. 가상 사이트를 사용할 때만 필요합니다.';
    $lang->time_zone = 'Múi giờ';
    $lang->about_time_zone = "Nếu thời gian của khu vực bạn không tự động cập nhật. Bạn có thể chọn thời gian để hiển thị cho Website.";
    $lang->qmail_compatibility = 'Mở Qmail';
    $lang->about_qmail_compatibility = 'Nó sẽ cho phép gửi thư từ MTA mà không phân biệt CRLF.';
    $lang->about_database_file = 'Sqlite lưu trữ dữ liệu trong một File, vì vậy cần tới sự truy cập đến nó trong Database. <br/><span style="color:red">Hãy CHMOD thành 707.</span>';
    $lang->success_installed = 'Chúc mừng bạn đã cài đặt XE thành công!';
    $lang->msg_cannot_proc = 'Môi trường cài đặt không thích hợp.';
    $lang->msg_already_installed = 'Một phiên bản nào đó của XE đã được cài đặt từ trước.<br />Xin hãy kiểm tra lại!';
    $lang->msg_dbconnect_failed = "Đã có lỗi xảy ra khi kết nối tới Database.\nXin vui lòng kiểm tra lại thông tin!";
    $lang->msg_table_is_exists = "Table đã có sẵn trên Database.\nFile Config đã đuwọc thiết lập lại.";
    $lang->msg_install_completed = "Đã cài đặt XE thành công!.\nXin cảm ơn đã sử dụng XE!";
    $lang->msg_install_failed = "Đã có lỗi xảy ra khi tạo File cài đặt.";
    $lang->ftp_get_list = "Nhận danh sách";
	$lang->msg_license_agreement = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->msg_read_all = '전문 읽기';
	$lang->msg_license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->msg_license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->msg_intall_env_agreement = '설치 환경 수집 동의';
	$lang->msg_intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
