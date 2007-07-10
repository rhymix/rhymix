<?php
    /**
     * @file   modules/poll/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  アンケート（poll）モジュールの基本言語パッケージ
     **/

    $lang->poll = "アンケート調査"; 
    $lang->poll_stop_date = "アンケート調査の終了日";
    $lang->poll_join_count = "参加者";
    $lang->poll_checkcount = "必須選択項目数";

    $lang->cmd_poll_list = 'アンケートのリスト表示';
    $lang->cmd_delete_checked_poll = '選択項目削除';
    $lang->cmd_apply_poll = 'アンケート調査へ参加する';
    $lang->cmd_delete_checked_poll = '選択のアンケート削除';

    $lang->success_poll = 'アンケート調査へのご応募ありがとうございます。';

    $lang->msg_already_poll = '既にアンケート調査に応募しました。';
    $lang->msg_cart_is_null = '削除する書き込みを選択してください。';
    $lang->msg_checked_poll_is_deleted = '%d個のアンケートが削除されました。';
    $lang->msg_check_poll_item = 'アンケート調査のアイテムを選択してください（アンケート調査ごと必須の選択アイテムが異なる場合があります）。';
    $lang->msg_cart_is_null = '削除するアンケートを選択してください。';
    $lang->msg_checked_poll_is_deleted = '%d個のアンケートが削除されました。';
    $lang->msg_poll_not_exists = '選択したアンケートは存在しません。';

    $lang->cmd_null_item = "アンケート調査に登録するアイテムがありません。\nもう一度設定してください。";

    $lang->confirm_poll_submit = "アンケート調査にご応募しますか？";

    $lang->search_target_list = array(
        'title' => 'タイトル',
        'regdate' => '登録日',
        'ipaddress' => 'IPアドレス',
    );
?>
