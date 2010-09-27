<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  DucDuy Dao (webmaster@xpressengine.vn)								  ░░
			░░	* @Website:  http://xpressengine.vn												  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
*/

    $lang->document_list = 'Danh sách bài viết';
    $lang->thumbnail_type = 'Định dạng hình nhỏ';
    $lang->thumbnail_crop = 'Hình cắt';
    $lang->thumbnail_ratio = 'Tỉ lệ';
    $lang->cmd_delete_all_thumbnail = 'Xóa tất cả hình nhỏ';
    $lang->move_target_module = "Vị trí Module";
    $lang->title_bold = 'Chữ đậm';
    $lang->title_color = 'Màu';
    $lang->new_document_count = 'Bài viết mới';

    $lang->parent_category_title = 'Tên thể loại chính';
    $lang->category_title = 'Tên thể loại nhỏ';
    $lang->category_color = 'Màu chữ';
    $lang->expand = 'Mở rộng';
    $lang->category_group_srls = 'Nhóm được cho phép';

    $lang->cmd_make_child = 'Thêm thể loại nhỏ';
    $lang->cmd_enable_move_category = "Thay đổi vị trí thể loại (Kéo lên Menu trên sau khi lựa chọn)";

    $lang->about_category_title = 'Hãy nhập tên thể loại';
    $lang->about_expand = 'Nếu sử dụng tùy chọn này, Thể loại sẽ luôn luôn được trải rộng.';
    $lang->about_category_group_srls = 'Chỉ những nhóm đã chọn mới được phép sử dụng thể loại này.';
    $lang->about_category_color = 'Bạn có thể đặt màu cho thể loại.';

    $lang->cmd_search_next = 'Tìm tiếp';

    $lang->cmd_temp_save = 'Lưu tạm thời';

	$lang->cmd_toggle_checked_document = 'Khôi phục những bài đã chọn';
    $lang->cmd_delete_checked_document = 'Xóa những bài đã chọn';
    $lang->cmd_document_do = 'Bình chọn / Phê bình';

    $lang->msg_cart_is_null = 'Xin hãy chọn bài viết để xóa.';
    $lang->msg_category_not_moved = 'Không thể di chuyển';
    $lang->msg_is_secret = 'Bài viết này đã đặt bí mật';
    $lang->msg_checked_document_is_deleted = '%d bài viết đã được xóa.';

    // Search targets in admin page
        $lang->search_target_list = array(
        'title' => 'Tiêu đề',
        'content' => 'Nội dung',
        'user_id' => 'ID sử dụng',
        'member_srl' => 'Mã thành viên',
        'user_name' => 'Tên',
        'nick_name' => 'Nickname',
        'email_address' => 'Email',
        'homepage' => 'Trang chủ',
        'is_notice' => 'Chú ý',
        'is_secret' => 'Bí mật',
        'tags' => 'Tag',
        'readed_count' => 'Lượt xem',
        'voted_count' => 'Lượt bình chọn',
        'comment_count ' => 'Số bình luận',
        'trackback_count ' => 'Số liên kết Web',
        'uploaded_count ' => 'Số đính kèm',
        'regdate' => 'Ngày gửi',
        'last_update' => 'Cập nhật lần cuối',
        'ipaddress' => 'IP',
    );
	
    $lang->alias = "Bí danh";
    $lang->history = "Lịch sử";
    $lang->about_use_history = "Chức năng này sẽ lưu lại những thay đổi trên bài viết. Nếu sử dụng chức năng này, bạn có thể khôi phục lại trạng thái ban đầu của bài viết.";
    $lang->trace_only = "Chỉ theo dõi";
	
	$lang->cmd_trash = "Thùng rác";
    $lang->cmd_restore = "Khôi phục";
    $lang->cmd_restore_all = "Khôi phục tất cả";

    $lang->in_trash = "Thùng rác";
    $lang->trash_nick_name = "Người xóa";
    $lang->trash_date = "Ngày xóa";
    $lang->trash_description = "Mô tả";

    // 관리자 페이지에서 휴지통의 검색할 대상
    $lang->search_target_trash_list = array(
        'title' => 'Tiêu đề',
        'content' => 'Nội dung',
        'user_id' => 'ID',
        'member_srl' => 'Mã số thành viên',
        'user_name' => 'Tên thật',
        'nick_name' => 'NickName',
        'trash_member_srl' => 'Mã số người xóa',
        'trash_user_name' => 'Tên người xóa',
        'trash_nick_name' => 'NickName người xóa',
        'trash_date' => 'Ngày xóa',
        'trash_ipaddress' => 'IP Người xóa',
	);

    $lang->success_trashed = "Đã chuyển tới thùng rác thành công.";
    $lang->msg_not_selected_document = '선택된 문서가 없습니다.';
?>
