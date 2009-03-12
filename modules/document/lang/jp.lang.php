<?php
    /**
     * @file   modules/document/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa、ミニミ
     * @brief  ドキュメント（document）モジュルの基本言語パッケージ
     **/

    $lang->document_list = 'ドキュメントリスト';
    $lang->thumbnail_type = 'サムネールタイプ';
    $lang->thumbnail_crop = 'トリミングする';
    $lang->thumbnail_ratio = '比率に合わせる';
    $lang->cmd_delete_all_thumbnail = 'すべてのサムネール削除';
    $lang->title_bold = 'タイトル太字';
    $lang->title_color = 'タイトルの色';
    $lang->new_document_count = '새글';

    $lang->parent_category_title = '上位カテゴリ名';
    $lang->category_title = 'カテゴリ名';
    $lang->category_color = 'カテゴリフォント色';
    $lang->expand = '拡張表示';
    $lang->category_group_srls = 'グループ制限';
    $lang->cmd_make_child = '下位カテゴリ追加';
    $lang->cmd_enable_move_category = "カテゴリ位置変更（選択後上のメニューをドラッグして下さい）";
    $lang->about_category_title = 'カテゴリ名を入力して下さい。';
    $lang->about_expand = 'チェックすると常に展開された状態になります。';
    $lang->about_category_group_srls = '選択したグループのみ、現在のカテゴリの指定が出来ます。';
    $lang->about_category_color = 'カテゴリのフォント色を設定します。';

    $lang->cmd_search_next = '継続検索';

    $lang->cmd_temp_save = '一時保存';

	$lang->cmd_toggle_checked_document = '選択項目反転';
    $lang->cmd_delete_checked_document = '選択項目削除';
    $lang->cmd_document_do = 'この書き込みを..';

    $lang->msg_cart_is_null = '削除する書き込みを選択してください。';
    $lang->msg_category_not_moved = '移動できません。';
    $lang->msg_is_secret = '非公開設定の書き込みです。';
    $lang->msg_checked_document_is_deleted = '%d個の書き込みが削除されました。';

    $lang->move_target_module = "移動対象モジュール";

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
    $lang->alias = "Alias";
    $lang->history = "히스토리";
    $lang->about_use_history = "히스토리 기능의 사용여부를 지정합니다. 히스토리 기능을 사용할 경우 문서 수정시 이전 리비전을 기록하고 복원할 수 있습니다.";
    $lang->trace_only = "흔적만 남김";
?>
