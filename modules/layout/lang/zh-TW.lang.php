<?php
    /**
     * @file   modules/layout/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com> 翻譯：royallin
     * @brief  版面設計(layout)模組正體中文語言
     **/

    $lang->cmd_layout_management = '版面設置';
    $lang->cmd_layout_edit = '版面編輯';

    $lang->layout_name = '版面名稱';
    $lang->layout_maker = "版面作者";
    $lang->layout_license = '版權';
    $lang->layout_history = "更新記錄";
    $lang->layout_info = "版面資訊";
    $lang->layout_list = '版面列表';
    $lang->menu_count = '選單數量';
    $lang->downloaded_list = '版面選擇';
    $lang->layout_preview_content = '顯示內容的部分。';
    $lang->not_apply_menu = '套用版面';

    $lang->cmd_move_to_installed_list = "檢視建立列表";

    $lang->about_downloaded_layouts = "已下載的版面列表";
    $lang->about_title = '連結模組時，請輸入容易區分的標題。';
    $lang->about_not_apply_menu = '更新所有被連結到選單的版面模組。';

    $lang->about_layout = "版面設計模組使網站製作變得更簡單。<br />透過版面設置及選單的連結，利用多種模組可以輕鬆製作組合出完整的網站。<br />- 無法刪除和修改的版面，可能是部落格或其他模組的原始樣板，因此應到相關模組進行設置。";
    $lang->about_layout_code = 
        "儲存修改後的版面，即可生效。
	     儲存之前，請先預覽後再儲存。
        XE版面設計語法，請參考<a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE 樣版</a>。";

    $lang->layout_export = '版面匯出';
    $lang->layout_btn_export = '下載版面';
    $lang->about_layout_export = '可匯出目前修改過的版面。';
    $lang->layout_import = '版面匯入';
    $lang->about_layout_import = '版面匯入時，將會刪除目前的版面設置。
    版面匯入前，請先匯出備份目前的版面設置。';
    $lang->layout_manager = array(
        0  => '版面管理者',
        1  => '儲存',
        2  => '取消',
        3  => '模式',
        4  => '樣式',
        5  => '對齊',
        6  => '固定版面',
        7  => '變動版面',
        8  => '固定+變動(內容)',
        9  => '1欄',
        10 => '2欄 (左側內容區)',
        11 => '2欄 (右側內容區)',
        12 => '3欄 (左側內容區)',
        13 => '3欄 (居中內容區)',
        14 => '3欄 (右側內容區)',
        15 => '靠左對齊',
        16 => '置中對齊',
        17 => '靠右對齊',
        18 => '整體',
        19 => '版面',
        20 => '新增Widget',
        21 => '新增內容',
        22 => '屬性',
        23 => 'Widget樣式',
        24 => '修改',
        25 => '刪除',
        26 => '對齊',
        27 => '換行',
        28 => '靠左對齊',
        29 => '靠右對齊',
        30 => '寬度',
        31 => '高度',
        32 => '邊距',
        33 => '內距',
        34 => '上',
        35 => '左',
        36 => '右',
        37 => '下',
        38 => '外框', 
        39 => '無',
        40 => '背景',
        41 => '顏色',
        42 => '圖片',
        43 => '選擇',
        44 => '背景重複',
        45 => '重複',
        46 => '不重複',
        47 => '水平重複',
        48 => '垂直重複',
        49 => '應用',
        50 => '取消',
        51 => '重置',
        52 => '字型',
        53 => '字體',
        54 => '文字顏色',
    );

    $lang->layout_image_repository = '版面檔案庫';
    $lang->about_layout_image_repository = '可在所選擇的版面中上傳圖片/Flash檔案。匯出時將包含此檔案。';
    $lang->msg_layout_image_target = '只允許上傳gif, png, jpg, swf, flv等檔案格式。';
    $lang->layout_migration = '版面匯出/匯入';
    $lang->about_layout_migration = '可將修改過的版面匯出成tar檔案或是直接匯入現有的tar檔案。'."\n".'(此功能目前只能用於faceOff版面中';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff版面管理者',
        'description' => 'FaceOff Layout版面管理者，可於線上修改與設計版面。<br/>下圖為版面架構示意圖和功能簡介，瞭解後發揮創意製作出自己想要的版面吧!',
        'layout' => 'FaceOff及HTML的架構是相同。<br/>이 구조에서 CSS를 이용하여 형태/배열/정렬을 할 수 있고 또 Style을 이용하여 꾸밀 수 있습니다.<br/>위젯 추가는 Extension(e1, e2)와 Neck, Knee에서 가능합니다.<br/>이 외 Body, Layout, Header, Body, Footer는 Style을 꾸밀 수 있고 Content는 모듈의 내용이 출력됩니다.',
        'setting' => '左上方的選單說明：<br/><ul><li>儲存 : 儲存設定內容</li><li>取消 : 不儲存設定內容並返回上一頁</li><li>重置 : 重新設置回到最原始的版面設定</li><li>模式 : 可設定版面模式為固定/ 變動/ 固定+變動(內容)</li><li>樣式 : 可設置兩個Extension區域和Content區域</li><li>對齊 : 可選擇版面的對齊方式</li></ul>',
        'hotkey' => '除了可利用滑鼠選取各區域外，也能使用熱鍵選取：<br/><ul><li>tab鍵 : 當沒有選取Widget時，選擇順序是： Header, Footer, Body；當有選取Widget時，將會選擇下一個Widget。</li><li>Shift + tab鍵 : 功能和tab鍵相反</li><li>Esc鍵 : 當沒有選擇區域時，選擇順序是： Neck, Extension(e1,e2),Knee；當有選擇Widget時，將會選則此Widget所屬的區域。</li><li>方向鍵 : 當有選擇Widget時，可利用方向鍵作移動。</li></ul>',
        'attribute' => '除了Widget以外的各個區域都可以指定背景顏色/圖片及文字顏色(包含a標籤)。',

    );
	$lang->mobile_layout_list = "Mobile Layout List";
	$lang->mobile_downloaded_list = "Downloaded Mobile Layouts";
	$lang->apply_mobile_view = "Apply Mobile View";
	$lang->about_apply_mobile_view = "All connected module use mobile view to display when accessing with mobile device.";
?>
