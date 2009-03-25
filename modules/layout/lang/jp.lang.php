<?php
    /**
     * @file   modules/layout/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  レイアウト（layout）モジュールの基本言語パッケージ
     **/

    $lang->cmd_layout_management = 'レイアウト設定';
    $lang->cmd_layout_edit = 'レイアウト編集';

    $lang->layout_name = 'レイアウト名';
    $lang->layout_maker = "レイアウト作者";
    $lang->layout_license = 'ライセンス';
    $lang->layout_history = "変更内容 ";
    $lang->layout_info = "レイアウト情報";
    $lang->layout_list = 'レイアウトリスト';
    $lang->menu_count = 'メニュー数';
    $lang->downloaded_list = 'ダウンロードリスト';
    $lang->layout_preview_content = '内容が出力される部分です。';
    $lang->not_apply_menu = 'レイアウトの一括適用';

    $lang->cmd_move_to_installed_list = "作成されたリスト表示";

    $lang->about_downloaded_layouts = "ダウンロードのレイアウトリスト";
    $lang->about_title = 'モジュールとの連動をわかりやすく区分するためのタイトルを入力して下さい。';
    $lang->about_not_apply_menu = 'チェックを入れると連動するすべてのメニューのモジュールのレイアウトを一括変更します。';

    $lang->about_layout = "レイアウトのモジュールはサイトのレイアウトを分かりやすく作成できるようにします。レイアウトの設定とメニューのリンクで様々なモジュールで完成されたサイトデザインができます。<br />★削除・修正ができないレイアウトはブログまたは他のモジュールのレイアウトであるため、該当するモジュールで設定を行って下さい。";
    $lang->about_layout_code = 
        "下のレイアウトコードを修正し、保存するとサービスに反映されます。
        必ずプレビューで確認してから保存して下さい。
        XEのテンプレート文法は<a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XEテンプレート</a>を参考して下さい。";

    $lang->layout_export = 'エクスポート';
    $lang->layout_btn_export = 'マイレイアウトをダウンロードする';
    $lang->about_layout_export = 'カスタマイズした自分のレイアウトをエクスポートします。';
    $lang->layout_import = 'インポート';
    $lang->about_layout_import = 'インポートする場合、既存の修正されたレイアウトを上書きします。インポート前にエクスポートでバックアップすることをお勧めします。';
    $lang->layout_manager = array(
        0  => 'レイアウトマネジャー',
        1  => '保存',
        2  => '取り消し',
        3  => '형태',
        4  => '配列',
        5  => '整列',
        6  => '固定型レイアウト',
        7  => '可変型レイアウト',
        8  => '固定+可変(内容)',
        9  => '1칸',
        10 => '2칸 (내용 왼쪽)',
        11 => '2칸 (내용 오른쪽)',
        12 => '3칸 (내용 왼쪽)',
        13 => '3칸 (내용 가운데)',
        14 => '3칸 (내용 오른쪽)',
        15 => '左',
        16 => '中央',
        17 => '右',
        18 => '전체',
        19 => 'レイアウト',
        20 => 'ウィジェット 추가',
        21 => '내용 ウィジェット 추가',
        22 => '属性',
        23 => 'ウィジェットスタイル',
        24 => '修正',
        25 => '削除',
        26 => '整列',
        27 => '一行차지',
        28 => '左',
        29 => '右',
        30 => '横幅サイズ',
        31 => '高さ',
        32 => '바깥 여백',
        33 => '안쪽 여백',
        34 => '上',
        35 => '左',
        36 => '右',
        37 => '下',
        38 => 'ボーダー', 
        39 => 'なし',
        40 => '背景',
        41 => '색상',
        42 => '画像',
        43 => '選択',
        44 => '배경 그림 반복',
        45 => '반복',
        46 => '반복 안함',
        47 => '가로 반복',
        48 => '세로 반복',
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
        'description' => 'FaceOff レイアウト管理ツールはウェブ上で手軽なレイアウト変更が可能に出来ます。<br/>아래 그림을 보시고 구성요소와 기능을 이용하여 원하시는 레이아웃을 만드세요',
        'layout' => 'FaceOffは上のようなHTML構造になっています。<br/>이 구조에서 CSS를 이용하여 형태/배열/정렬을 할 수 있고 또 Style을 이용하여 꾸밀 수 있습니다.<br/>ウィジェット 추가는 Extension(e1, e2)와 Neck, Knee에서 가능합니다.<br/>이 외 Body, Layout, Header, Body, Footer는 Style을 꾸밀 수 있고 Content는 모듈의 내용이 출력됩니다.',
        'setting' => '좌측 상단의 메뉴에 대해 설명 드립니다.<br/><ul><li>保存 : 설정된 내용을 저장합니다.</li><li>취소 : 설정한 내용을 저장하지 않고 돌아갑니다.</li><li>초기화 : 아무 설정도 되어 있지 않은 백지 상태로 돌립니다</li><li>형태 : 고정/ 가변/ 고정+가변(내용)의 형태를 지정합니다.</li><li>배열 : Extension 2개와 Content를 배열합니다.</li><li>정렬 : 레이아웃의 위치를 정렬시킬 수 있습니다.</li></ul>',
        'hotkey' => '마우스로 각 영역을 선택하면서 Hot Key를 이용하시면 더 쉽게 꾸미실 수 있습니다.<br/><ul><li>tab 키 : ウィジェット이 선택되어 있지 않으면 Header, Body, Footer 순으로 선택됩니다. ウィジェット이 선택되어 있다면 다음 ウィジェット으로 선택이 이동됩니다.</li><li>Shift + tab키 : tab키와 반대 역할을 합니다.</li><li>Esc : 아무것도 선택되어 있지 않을때 Esc를 누르면 Neck, Extension(e1,e2),Knee 순서대로 선택이 되며 ウィジェット이 선택되어 있다면 선택된 ウィジェット을 감싸는 영역이 선택됩니다.</li><li>방향키 : ウィジェット이 선택되어 있을때 방향키를 이용하여 ウィジェット을 다른 영역으로 이동시킬 수 있습니다.</li></ul>',
        'attribute' => 'ウィジェット을 제외한 각 영역들은 모두 배경 색/ 이미지를 지정할 수 있고 글자색(a 태그 포함됨)을 정할 수 있습니다.',

    );
?>
