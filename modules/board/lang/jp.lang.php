<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa、ミニミ
     * @brief  掲示板(board)モジュールの基本言語パッケージ
     **/

    $lang->board = "掲示板"; 

    $lang->except_notice = "공지사항 제외";

    $lang->cmd_manage_menu = 'メニュー管理';
    $lang->cmd_make_child = '下位カテゴリ追加';
    $lang->cmd_enable_move_category = "カテゴリ位置変更（選択後上のメニューをドラッグして下さい）";
    $lang->cmd_remake_cache = 'キャッシュファイル再生性';
    $lang->cmd_layout_setup = 'レイアウト設定';
    $lang->cmd_layout_edit = 'レイアウト編集';

    // 項目
    $lang->parent_category_title = '上位カテゴリ名';
    $lang->category_title = 'カテゴリ名';
    $lang->expand = '拡張表示';
    $lang->category_group_srls = 'グループ制限';
    $lang->search_result = '検索結果';

    // ボタンに使用する用語
    $lang->cmd_board_list = '掲示板リスト';
    $lang->cmd_module_config = '掲示板共通設定';
    $lang->cmd_view_info = '掲示板情報';

    // その他
    $lang->about_category_title = 'カテゴリ名を入力して下さい。';
    $lang->about_expand = 'チェックすると常に展開された状態になります。';
    $lang->about_category_group_srls = '選択したグループのみ現在のカテゴリが見えるようになります（XMLファイルを直接閲覧すると表示されます）。';
    $lang->about_layout_setup = 'ブログのレイアウトのコードを直接修正します。ウィジェットコードを好きなところに入力、又は管理して下さい。';
    $lang->about_board_category = 'ブログのカテゴリを作成します。<br />ブログのカテゴリが誤作動する場合、「キャッシュファイルの再生性」を手動で行うことで解決できます。';
    $lang->about_except_notice = "목록 상단에 늘 나타나는 공지사항을 일반 목록에서 공지사항을 출력하지 않도록 합니다.";
    $lang->about_board = "掲示板の生成、および管理する掲示板モジュールです。\n生成後、リストからモジュール名を選択すると詳細な設定ができます。\n掲示板のモジュール名はURLになりますので注意してください。 (ex : http://ドメイン/zb/?mid=モジュール名)";
?>
