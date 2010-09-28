<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  DucDuy Dao (webmaster@xpressengine.vn)								  ░░
			░░	* @Website:  http://xpressengine.vn												  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
*/

    $lang->file = 'Đính kèm';
    $lang->file_name = 'Tên File';
    $lang->file_size = 'Dung lượng';
    $lang->download_count = 'Lượt Download';
    $lang->status = 'Trạng thái';
    $lang->is_valid = 'Hợp lệ';
    $lang->is_stand_by = 'Chờ duyệt';
    $lang->file_list = 'Danh sách đính kèm';
    $lang->allow_outlink = 'Link từ bên ngoài';
	$lang->allow_outlink_format = 'Những định dạng cho phép';
    $lang->allow_outlink_site = 'Những Website cho phép';
    $lang->allowed_filesize = 'Dung lượng tối đa';
    $lang->allowed_attach_size = 'Dung lượng file đính kèm';
    $lang->allowed_filetypes = 'Những định dạng cho phép';
    $lang->enable_download_group = 'Nhóm được phép Download';

    $lang->about_allow_outlink = 'Những định dạng Link File từ bên ngoài được phép đính kèm.(Ngoại trừ định dạng Media *.wmv, *.mp3)';
	$lang->about_allow_outlink_format = 'Những định dạng này sẽ được phép liên kết. Hãy sử dụng dấu (,) để thêm nhiều định dạng .<br />Ví dụ: .hwp, .doc, .zip, .pdf';
    $lang->about_allow_outlink_site = 'Những Website được phép liên kết. Hãy nhập địa chỉ của những Website được phép.<br />Ví dụ: http://xpressengine.com/';
	$lang->about_allowed_filesize = 'Giới hạn dung lượng mỗi File đính kèm. (Ngoại trừ Administrators)';
    $lang->about_allowed_attach_size = 'Giới hạn dung lượng tối đa cho tất cả các File đính kèm trong một bài viết. (Ngoại trừ Administrators)';
    $lang->about_allowed_filetypes = 'Chỉ được phép đính kèm những File có đuôi được liệt kê trong danh sách.<br />Để thêm những dạng File được phép đính kèm, bạn sử dụng "*.[đuôi]".<br />Để cho phép nhiều dạng đuôi File hãy đặt dấu ";" vào giữa các dạng đuôi.<br />Ví dụ: *.* (Cho phép tất cả) hay *.jpg;*.gif;<br />(Ngoại trừ Administrators)';

    $lang->cmd_delete_checked_file = 'Xóa File đã chọn';
    $lang->cmd_move_to_document = 'Chèn vào bài viết';
    $lang->cmd_download = 'Download';

    $lang->msg_not_permitted_download = 'Bạn không được quyền Download.';
    $lang->msg_cart_is_null = 'Xin vui lòng chọn File để xóa.';
    $lang->msg_checked_file_is_deleted = '%d đính kèm đã được xóa.';
    $lang->msg_exceeds_limit_size = 'Dung lượng File quá lớn.';
	$lang->msg_file_not_found = 'Không tìm thấy File.';


    $lang->file_search_target_list = array(
        'filename' => 'Tên File',
        'filesize_more' => 'Dung lượng (Tối thiểu Byte)',
        'filesize_mega_more' => 'Dung lượng (Tối thiểu MB)',
		'filesize_less' => 'Dung lượng (Tối đa Byte)',
		'filesize_mega_less' => 'Dung lượng (Tối đa MB)',
        'download_count' => 'Lượt Download',
        'regdate' => 'Ngày gửi',
        'user_id' => 'ID đăng nhập',
        'user_name' => 'Tên Sử dụng',
        'nick_name' => 'Nickname',
        'ipaddress' => 'IP',
    );
	$lang->msg_not_allowed_outlink = 'Không cho phép tải file từ những trang khác ngoài trang này.'; 
    $lang->msg_not_permitted_create = '파일 또는 디렉토리를 생성할 수 없습니다.';
	$lang->msg_file_upload_error = '파일 업로드 중 에러가 발생하였습니다.';

?>
