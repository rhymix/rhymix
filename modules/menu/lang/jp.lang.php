<?php
    /**
     * @file   modules/menu/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、liahona、ミニミ
     * @brief  メニュー（menu）モジュールの基本言語パッケージ
     **/

    $lang->cmd_menu_insert = 'メニュー生成';
    $lang->cmd_menu_management = 'メニュー設定';

    $lang->menu = 'メニュー'; 
    $lang->menu_count = 'メニュー数';
    $lang->menu_management = 'メニュー管理';
    $lang->depth = 'スレッド';
    $lang->parent_menu_name = '上位メニュー名';
    $lang->menu_name = 'メニュー名';
    $lang->menu_srl = 'メニュー固有番号';
    $lang->menu_id = 'メニュー名';
    $lang->menu_url = 'リンクURL';
    $lang->menu_open_window = '新しいウィンドウズで開く';
    $lang->menu_expand = '拡張表示';
    $lang->menu_img_btn = 'イメージボタン';
    $lang->menu_normal_btn = '一般ボタン';
    $lang->menu_hover_btn = 'マウスオーバー';
    $lang->menu_active_btn = '選択時のボタン';
    $lang->menu_group_srls = 'グループ制限';
    $lang->layout_maker = 'レイアウト作者';
    $lang->layout_history = '変更内容';
    $lang->layout_info = 'レイアウト情報';
    $lang->layout_list = 'レイアウトリスト';
    $lang->downloaded_list = 'ダウンロードリスト';
    $lang->limit_menu_depth = '表示スレッド';

    $lang->cmd_make_child = '下位メニュー追加';
    $lang->cmd_move_to_installed_list = '生成されたリスト表示';
    $lang->cmd_enable_move_menu = 'メニュー移動（選択後メニューをドラッグして下さい）';
    $lang->cmd_search_mid = 'mid 検索';

    $lang->msg_cannot_delete_for_child = '下位メニューが存在するメニューは削除出来ません。';

    $lang->about_title = 'モジュールをリンクする際に分かりやすいタイトルを入力して下さい。';
    $lang->about_menu_management = "メニュー管理は、選択されたレイアウトで使用するメニューを構成出来るようにします。<br />一定レベルまでメニューの構成が出来、入力したメニューをクリックすると詳細情報が入力出来ます。<br />フォルダーのイメージをクリックするとメニューを拡張することが出来ます。<br />もしメニューが正常に表示されない場合は、 「キャッシュファイル再生成」ボタンをクリックして情報を更新して下さい。<br />* 一定レベル以上のメニューは正しく表示されない場合があります。";
    $lang->about_menu_name = '管理及びイメージボタンではない場合、メニュー名として表示されるタイトルです。';
    $lang->about_menu_url = 'メニュー選択時、移動するURLです。<br />他のモジュールとリンクを張る場合はＩＤの値のみ入力して下さい。<br />内容がない場合は、メニューを選択しても何の動作もありません。';
    $lang->about_menu_open_window = 'メニュー選択時、新しいウィンドウで開くかを指定することが出来ます。';
    $lang->about_menu_expand = 'ツリーメニュー（tree_menu.js）を利用すると常に拡張表示（すべて表示）の状態にすることが出来ます。';
    $lang->about_menu_img_btn = 'イメージボタンを登録するとレイアウトで自動的にイメージボタンに入れ替わって表示されます。';
    $lang->about_menu_group_srls = 'グループを選択すると該当するグループのユーザにのみメニューが表示されます（XMLファイルを直接開くと情報が表示されます）。';

    $lang->about_menu = "メニューモジュールは、メニュー管理機能にて生成モジュールの整理やレイアウトとのリンクを設定し、簡単に管理者画面上でサイトを構築出来るようにします。\nメニューはサイトを管理するというより、モジュールとレイアウトをリンクし、様々なメニューを表示させる情報だけ持っています。";

    $lang->alert_image_only = 'イメージ(画像)ファイルのみ登録出来ます。';
?>
