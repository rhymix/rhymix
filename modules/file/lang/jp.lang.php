<?php
    /**
     * @file   modules/file/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  添付ファイル（file）モジュールの基本言語パッケージ
     **/

    $lang->file = '添付ファイル';
    $lang->file_name = 'ファイル名';
    $lang->file_size = 'ファイルサイズ';
    $lang->download_count = 'ダウンロード数';
    $lang->status = '状態';
    $lang->is_valid = '有効';
    $lang->is_stand_by = '待機';
    $lang->file_list = '添付ファイルリスト';
    $lang->allow_outlink = '外部からのファイルリンク';
    $lang->allow_outlink_site = '外部からのファイルリンクを許可するサイト';
    $lang->allow_outlink_format = '外部からのファイルリンクを許可する拡張子';
    $lang->allowed_filesize = 'ファイルサイズ制限';
    $lang->allowed_attach_size = '書き込みへの添付制限';
    $lang->allowed_filetypes = '添付可能な拡張子';
    $lang->enable_download_group = 'ダウンロード可能グループ';

    $lang->about_allow_outlink = 'リファラーによって外部からのファイルリンクを制御出来ます。(*.wmv, *.mp3などのメディアファイルは除く)';
    $lang->about_allow_outlink_format = '外部からのファイルリンク設定に構わず、常に外部からのリンクを許可する拡張子です。複数登録時には、「半角コンマ（,）」区切りで記入して下さい。<br />eg)txt,doc,zip,pdf';
    $lang->about_allow_outlink_site = '外部からのファイルリンク設定に構わず、常に外部からのリンクを許可するURLです。複数登録時には、改行で記入して下さい。<br />ex)http://xpressengine.com/';
    $lang->about_allowed_filesize = '一つのファイルに対して、アップロード出来るファイルの最大サイズを指定します（管理者除外）。';
    $lang->about_allowed_attach_size = '一つの書き込みに対して、管理者以外のユーザーが添付出来る最大サイズを指定します。';
    $lang->about_allowed_filetypes = 'ここで指定された種類のファイルのみ添付出来ます。"*.拡張子"で指定し、 ";"で区切って任意の拡張子を追加して指定出来ます。 （管理者は制限無し）<br />ex) *.* or *.jpg;*.gif;<br />';

    $lang->cmd_delete_checked_file = '選択リスト削除';
    $lang->cmd_move_to_document = '書き込みに移動する';
    $lang->cmd_download = 'ダウンロード';

    $lang->msg_not_permitted_download = 'ダウンロード権限がありません。';
    $lang->msg_cart_is_null = '削除するファイルを選択して下さい';
    $lang->msg_checked_file_is_deleted = '%d個の添付ファイルを削除しました';
    $lang->msg_exceeds_limit_size = 'ファイルサイズの制限を超えたため、添付出来ません。';
    $lang->msg_file_not_found = 'ファイルが見つかりません。';

    $lang->file_search_target_list = array(
        'filename' => 'ファイル名',
        'filesize_more' => 'ファイルサイズ（(Byte以上）',
        'filesize_mega_more' => 'ファイルサイズ (Mb、以上)',
		'filesize_less' => 'ファイルサイズ (byte, 以下)',
		'filesize_mega_less' => 'ファイルサイズ (Mb, 以下)',
        'download_count' => 'ダウンロード数（以上）',
        'user_id' => 'ユーザーＩＤ',
        'user_name' => '名前',
        'nick_name' => 'ニックネーム',
        'regdate' => '登録日',
        'ipaddress' => 'IPアドレス',
    );
	$lang->msg_not_allowed_outlink = '外部リンクからのダウンロードは許可されていません。'; 
    $lang->msg_not_permitted_create = 'ファイルまたはディレクトリを生成できません。';
	$lang->msg_file_upload_error = 'ファイルアップロードに失敗しました。';

?>
