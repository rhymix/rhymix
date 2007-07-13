<?php

    function matchDocument($matches) {
        $addon_info = $GLOBALS['__g_addon_info__'];

        $source_code = $matches[0];
        $document_srl = $matches[1];
        $member_srl = $matches[2];

        // 사용자 입력을 지원하면 해당 회원의 정보에서 구글 키를 가져옴
        if($member_srl && $addon_info->user_ad_client) {
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            $key = $member_info->{$addon_info->user_ad_client};
            if($key) {
                $addon_info->ad_client = $key;
                $addAdSense->ad_type = '';
            }
        }

        $adsense_code = addAdSense($addon_info);

        return $source_code.$adsense_code;
    }

    function addAdSense($addon_info) {
        $script_code = <<<EndOfScript
<script type="text/javascript"><!--
google_ad_client = "{$addon_info->ad_client}";
google_ad_width = "{$addon_info->ad_width}";
google_ad_height = "{$addon_info->ad_height}";
google_ad_format = "{$addon_info->ad_format}";
google_ad_type = "{$addon_info->ad_type}";
google_ad_channel = "{$addon_info->ad_channel}";
google_color_border = "{$addon_info->color_border}";
google_color_bg = "{$addon_info->color_bg}";
google_color_link = "{$addon_info->link_color}";
google_color_text = "{$addon_info->text_color}";
google_color_url = "{$addon_info->url_color}";
google_ui_features = "{$addon_info->ui_features}";
//-->
</script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
EndOfScript;

        if($addon_info->background_image) $backgroundStyle = sprintf('background-image:url(%s)', $addon_info->background_image);

        $script_code = sprintf('<div style="width:%dpx;height:%dpx;%s;margin:10px 0 10px 0px;">%s</div>',$addon_info->ad_width, $addon_info->ad_height, $backgroundStyle, $script_code);

        return $script_code;
    }

?>
