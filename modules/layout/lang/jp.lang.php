<?php
    /**
     * @file   modules/layout/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  レイアウト（layout）モジュールの基本言語パッケージ
     **/

    $lang->cmd_layout_management = 'レイアウト設定';
    $lang->cmd_layout_edit = 'レイアウト編集';

    $lang->layout_name = 'レイアウト名';
    $lang->layout_maker = 'レイアウト作者';
    $lang->layout_license = 'ライセンス';
    $lang->layout_history = '変更内容 ';
    $lang->layout_info = 'レイアウト情報';
    $lang->layout_list = 'レイアウトリスト';
    $lang->menu_count = 'メニュー数';
    $lang->downloaded_list = 'ダウンロードリスト';
    $lang->layout_preview_content = '内容が出力される部分です。';
    $lang->not_apply_menu = 'レイアウトの一括適用';
	$lang->layout_management = 'レイアウト管理';

    $lang->cmd_move_to_installed_list = '作成されたリスト表示';

    $lang->about_downloaded_layouts = 'ダウンロードのレイアウトリスト';
    $lang->about_title = 'モジュールとの連動をわかりやすく区分するためのタイトルを入力して下さい。';
    $lang->about_not_apply_menu = 'チェックを入れると連動するすべてのメニューのモジュールのレイアウトを一括変更します。';

    $lang->about_layout = 'レイアウトのモジュールはサイトのレイアウトを分かりやすく作成出来るようにします。<br />レイアウトの設定とメニューのリンクで様々なモジュールで完成されたサイト構築が出来ます。<br />※ ブログまたは他のモジュールのレイアウトなどの削除・修正が出来ないレイアウトは、該当モジュールにて設定を行って下さい。';
    $lang->about_layout_code = 
        "下のレイアウトコードを修正し、保存するとサービスに反映されます。
        必ずプレビューで確認してから保存して下さい。
        XEのテンプレート文法は<a href=\"#\" onclick=\"winopen('http://xe.xpressengine.net/18180861');return false;\">XEテンプレート</a>を参考して下さい。";

    $lang->layout_export = 'エクスポート';
    $lang->layout_btn_export = 'マイレイアウトをダウンロードする';
    $lang->about_layout_export = 'カスタマイズした自分のレイアウトをエクスポートします。';
    $lang->layout_import = 'インポート';
    $lang->about_layout_import = 'インポートする場合、既存の修正されたレイアウトを上書きします。インポート前にエクスポートでバックアップすることをお勧めします。';

    $lang->layout_manager = array(
        0  => 'レイアウトマネジャー',
        1  => '保存',
        2  => '取り消し',
        3  => '基本レイアウト',
        4  => '配列',
        5  => '整列',
        6  => '固定型レイアウト',
        7  => '可変型レイアウト',
        8  => '固定+(内容部分)可変',
        9  => '1段',
        10 => '2段 (内容左側配置)',
        11 => '2段 (内容右側配置)',
        12 => '3段 (内容左側配置)',
        13 => '3段 (内容中央配置)',
        14 => '3段 (内容右側配置)',
        15 => '左',
        16 => '中央',
        17 => '右',
        18 => 'すべて',
        19 => 'レイアウト',
        20 => 'ウィジェット追加',
        21 => '内容 ウィジェット追加',
        22 => '属性',
        23 => 'ウィジェットスタイル',
        24 => '修正',
        25 => '削除',
        26 => '整列',
        27 => '一行占め',
        28 => '左',
        29 => '右',
        30 => '横幅サイズ',
        31 => '高さ',
        32 => '外側余白',
        33 => '内側余白',
        34 => '上',
        35 => '左',
        36 => '右',
        37 => '下',
        38 => 'ボーダー', 
        39 => 'なし',
        40 => '背景',
        41 => '色',
        42 => '画像',
        43 => '選択',
        44 => '背景画像リピート',
        45 => 'リピート',
        46 => 'リピートしない',
        47 => '横方向リピート',
        48 => '縦方向リピート',
        49 => '適用',
        50 => '取り消し',
        51 => '初期化',
        52 => '文字',
        53 => '文字フォント',
        54 => 'テキストの色',
    );

    $lang->layout_image_repository = 'レイアウトファイル保存場所';
    $lang->about_layout_image_repository = '選択したレイアウトに使う画像・Ｆｌａｓｈファイル等のアップロード出来ます。また、エクスポートする際、一緒に含まれます。';
    $lang->msg_layout_image_target = 'gif, png, jpg, swf, flvファイルのみ可能です。';
    $lang->layout_migration = 'レイアウトのエクスポート/インポート';
    $lang->about_layout_migration = '修正したレイアウトをtar形式の圧縮ファイルにエクスポートしたり、tar形式として保存されたファイルをインポートすることが出来ます。'."\n".'(まだ、faceOffレイアウトのみエクスポート/インポートが可能です。)';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff レイアウト管理ツール',
        'description' => 'FaceOffレイアウト管理ツールはウェブ上で、手軽なレイアウト変更を可能にします。<br />下の図を参照しながら構成要素と機能を理解し、自由にレイアウトをカスタマイズしてみて下さい。',
        'layout' => 'FaceOffは上のようなHTML構造になっています。<br />この構造にてCSSを用いた「レイアウト／配列／整列」の調整が可能になり、さらにStyleを使った自由なカスタマイズが出来ます。<br />ウィジェットの追加はExtension(e1、e2)と Neck、 Kneeにて可能です。<br />その他にもBody、Layout、Header、Body、FooterはStyleをカスタマイズが出来、Contentではモジュールの内容が出力されます。',
        'setting' => '左側上段のメニューの説明<br/><ul><li>保存 : 設定内容を保存します。</li><li>取り消し : 設定内容を保存せずに、差し戻します。</li><li>初期化 : 何の設定もない白紙状態（もしくはインストール時のデフォールト状態）に戻ります。</li><li>レイアウトタイプ : 固定／可変／固定+可変（内容）型のレイアウトを指定します。</li><li>配列 : Body部分に2つのExtensionとContentを配列します。</li><li>整列 : レイアウトの位置を整列します。</li></ul>',
        'hotkey' => 'マウスを使って各スペースを選択しながら、Hot Keyを利用すると、より便利なカスタマイズ出来ます。<br/><ul><li>tabキー : ウィジェットが選択されてない場合、Header、Body、 Footer順に選択されます。ウィジェットが選択されている場合は、次のウィジェットに選択されます。</li><li>Shift + tabキー : tabキーと逆の役割をします。</li><li>Esc : 何も選択されてない場合、Escを押すとNeck、Extension(e1、e2)、Knee順に選択され、また、ウィジェットが選択されている場合は選択されたウィジェットを囲む領域が選択されます。</li><li>矢印 : ウィジェットが選択されている時、矢印キーを用いて、ウィジェットを他の領域に移せます。</li></ul>',
        'attribute' => 'ウィジェットを除いた各領域はすべて背景の色・イメージ・文字のテキスト色(「a」タグを含む)の指定が可能です。',

    );
	$lang->mobile_layout_list = "モバイルレイアウトリスト";
	$lang->mobile_downloaded_list = "ダウンロードしたモバイルレイアウト";
	$lang->apply_mobile_view = "モバイルスキン使用";
	$lang->about_apply_mobile_view = "活性化すると連結されている全てのモジュールでモバイルスキンが適用されます。";
?>
