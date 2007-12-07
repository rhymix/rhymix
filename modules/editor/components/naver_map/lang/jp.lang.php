<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa、ミニミ
     * @brief  ウィジウィグエディター（editor） > マルチメディアリンク（naver_map）コンポネント言語パッケージ
     **/

    $lang->map_width = "横幅サイズ";
    $lang->map_height = "縦幅サイズ";

    // 表示メッセージ
    $lang->about_address = "例）분당 정자동, 역삼";
    $lang->about_address_use = "検索ウィンドウで住所を検索した後、出力された結果を選択して、「追加」ボタンを押せば、書き込みの内容に地図が追加されます。";

    // エラーメッセージ
    $lang->msg_not_exists_addr = "検索対象がありません。";
    $lang->msg_fail_to_socket_open = "郵便番号を検索するサーバとの接続に失敗しました。";
    $lang->msg_no_result = "検索結果がありません。";

    $lang->msg_no_apikey = "ネイバーマップを使用するためには、ネイバーマップのOpenAPIキーを取得しなければなりません。\nOpenAPIキーを 管理者 > ウィジウィグエディター > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">ネイバーマップコンポネント設定</a>を選択した後、入力してください。";
?>
