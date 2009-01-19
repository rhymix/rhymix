<?php
    /**
     * @file   modules/file/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa、ミニミ
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
    $lang->allowed_filesize = 'ファイルサイズ制限';
    $lang->allowed_attach_size = '書き込みへの添付制限';
    $lang->allowed_filetypes = '添付可能な拡張子';
    $lang->enable_download_group = 'ダウンロード可能グループ';

    $lang->about_allow_outlink = 'リファラーによって外部からのファイルリンクを制御出来ます。(*.wmv, *.mp3などのメディアファイルは除く)';
    $lang->about_allow_outlink_site = '外部からのファイルリンク設定に構わず、常に外部からのリンクを許可するURLです。複数登録時には、改行で記入して下さい。<br />ex)http://www.zeroboard.com';
    $lang->about_allowed_filesize = '一つのファイルに対して、アップロードできるファイルの最大サイズを指定します（管理者除外）。';
    $lang->about_allowed_attach_size = '一つの書き込みに対して、添付できる最大サイズを指定します（管理者除外）。';
    $lang->about_allowed_filetypes = 'アップロードできるように設定されたファイルのみが添付できます。"*.拡張子"で指定し、 ";"で区切って任意の拡張子を追加して指定できます（管理者除外）。<br />ex) *.* or *.jpg;*.gif;<br />';

    $lang->cmd_delete_checked_file = '選択リスト削除';
    $lang->cmd_move_to_document = '書き込みに移動する';
    $lang->cmd_download = 'ダウンロード';

    $lang->msg_not_permitted_download = 'ダウンロード権限がありません。';
    $lang->msg_cart_is_null = '削除するファイルを選択してください';
    $lang->msg_checked_file_is_deleted = '%d個の添付ファイルを削除しました';
    $lang->msg_exceeds_limit_size = 'ファイルサイズの制限を超えたため、添付できません。';

    $lang->file_search_target_list = array(
        'filename' => 'ファイル名',
        'filesize' => 'ファイルサイズ（(Byte以上）',
        'filesize_mega' => 'ファイルサイズ (Mb、以上)',
        'download_count' => 'ダウンロード数（以上）',
        'user_id' => 'ユーザーＩＤ',
        'user_name' => '名前',
        'nick_name' => 'ニックネーム',
        'regdate' => '登録日',
        'ipaddress' => 'IPアドレス',
    );
?>
