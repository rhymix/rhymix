<?php
    /**
     * @file   modules/document/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  ドキュメント（document）モジュールの基本言語パッケージ
     **/

    $lang->document_list = 'ドキュメントリスト';
    $lang->thumbnail_type = 'サムネールタイプ';
    $lang->thumbnail_crop = 'トリミングする';
    $lang->thumbnail_ratio = '比率に合わせる';
    $lang->cmd_delete_all_thumbnail = 'すべてのサムネール削除';
    $lang->title_bold = 'タイトル太字';
    $lang->title_color = 'タイトルの色';
    $lang->new_document_count = '新規';

    $lang->parent_category_title = '上位カテゴリ名';
    $lang->category_title = 'カテゴリ名';
    $lang->category_color = 'カテゴリフォント色';
    $lang->expand = '拡張表示';
    $lang->category_group_srls = 'グループ制限';

    $lang->cmd_make_child = '下位カテゴリ追加';
    $lang->cmd_enable_move_category = 'カテゴリ位置変更（選択後上のメニューをドラッグして下さい）';

    $lang->about_category_title = 'カテゴリ名を入力して下さい。';
    $lang->about_expand = 'チェックすると常に展開された状態になります。';
    $lang->about_category_group_srls = '選択したグループのみ、現在のカテゴリの指定が出来ます。';
    $lang->about_category_color = 'カテゴリのフォント色を設定します。';

    $lang->cmd_search_next = '継続検索';

    $lang->cmd_temp_save = '一時保存';

	$lang->cmd_toggle_checked_document = '選択項目反転';
    $lang->cmd_delete_checked_document = '選択項目削除';
    $lang->cmd_document_do = 'この書き込みを..';

    $lang->msg_cart_is_null = '削除する書き込みを選択して下さい。';
    $lang->msg_category_not_moved = '移動出来ません。';
    $lang->msg_is_secret = '非公開設定の書き込みです。';
    $lang->msg_checked_document_is_deleted = '%d個の書き込みが削除されました。';

    $lang->move_target_module = '移動対象モジュール';

    // 管理者ページで検索する内容
    $lang->search_target_list = array(
        'title' => 'タイトル',
        'content' => '内容',
        'user_id' => 'ユーザーＩＤ',
        'member_srl' => '会員番号',
        'user_name' => 'ユーザ名',
        'nick_name' => 'ニックネーム',
        'email_address' => 'メールアドレス',
        'homepage' => 'ホームページURL',
        'is_notice' => 'お知らせ',
        'is_secret' => '非公開書き込み',
        'tags' => 'タグ',
        'readed_count' => '閲覧数（以上）',
        'voted_count' => '推薦数（以上）',
        'comment_count ' => 'コメント数（以上）',
        'trackback_count ' => 'トラックバック数（以上）',
        'uploaded_count ' => '添付ファイル数（以上）',
        'regdate' => '登録日',
        'last_update' => '最近修正日',
        'ipaddress' => 'IPアドレス',
    );

    $lang->alias = 'アリアス（Alias）';
    $lang->history = '履歴';
    $lang->about_use_history = '履歴機能を使用するかを設定します。履歴機能を使用すると文書修正のバージョンを管理し、過去のバージョンから復元することも可能です。';
    $lang->trace_only = '記録だけ残す';

    $lang->cmd_trash = "ごみ箱";
    $lang->cmd_restore = "差し戻し";
    $lang->cmd_restore_all = "すべて差し戻し";

    $lang->in_trash = "ごみ箱";
    $lang->trash_nick_name = "削除者のニックネーム";
    $lang->trash_date = "削除日";
    $lang->trash_description = "理由";

    // 管理者ページでのごみ箱の検索対象
    $lang->search_target_trash_list = array(
        'title' => 'タイトル',
        'content' => '内容',
        'user_id' => 'ユーザーID',
        'member_srl' => '会員番号',
        'user_name' => 'ユーザー名',
        'nick_name' => 'ニックネーム',
        'trash_member_srl' => '削除者会員番号',
        'trash_user_name' => '削除者ユーザー名',
        'trash_nick_name' => '削除者ニックネーム',
        'trash_date' => '削除日',
        'trash_ipaddress' => '削除者のIPアドレス',
    );

    $lang->success_trashed = "ごみ箱に移動させました。";
    $lang->msg_not_selected_document = '選択された書き込みがありません。';
?>
