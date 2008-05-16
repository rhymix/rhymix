<?php
    /**
     * @file   modules/point/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  ポイント（point）モジュールの基本言語パッケージ
     **/

    $lang->point = "ポイント"; 
    $lang->level = "レベル"; 

    $lang->about_point_module = "ポイントモジュールでは、書き込み作成/コメント作成/アップロード/ダウンロードなどのユーザの活動に対してポイントの計算を行います。但し、ポイントモジュールでは設定のみを行い、アドオンでポイントシステムを「使用」に設定しなければポイントは累積されません。";
    $lang->about_act_config = "掲示板、ブログなどのモジュールごと書き込み作成・削除/コメント作成・削除などのアクションがあります。掲示板/ブログ以外のモジュールにポイントシステムを連動させたい場合は、各機能のアクションの「act値」を追加します。連動は「,（コンマ）」で区切って追加します。";

    $lang->max_level = '最高レベル';
    $lang->about_max_level = '最高レベルを指定することができます。最高レベルは「1000」がマクシマムなので、レベルアイコンに注意が必要です。';

    $lang->level_icon = 'レベルアイコン';
    $lang->about_level_icon = 'レベルアイコンは、「./modules/point/icons/レベル.gif」で指定されるため、最高レベルとアイコンセットが異なる場合がありますので、注意してください。';

    $lang->point_name = 'ポイント名';
    $lang->about_point_name = 'ポイントの名前、単位が指定できます。';

    $lang->level_point = 'レベルポイント';
    $lang->about_level_point = '下の各レベルのポイントが増加したり、減少するとレベルが調整されます。';

    $lang->disable_download = 'ダウンロード禁止';
    $lang->about_disable_download = 'チェックするとポイントがない場合、ダウンロードを禁止します（イメージファイル除外）。';

    $lang->level_point_calc = 'レベル別ポイント計算';
    $lang->expression = 'レベル変数<b>i</b>を使用してJavaスクリプト数式を入力してください（例: Math.pow(i, 2) * 90）';
    $lang->cmd_exp_calc = '計算';
    $lang->cmd_exp_reset = '初期化';

    $lang->cmd_point_recal = '포인트 초기화';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일/회원가입 점수만 이용하여 모든 포인트 점수를 초기화 합니다.<br />회원 가입 점수는 초기화 후 해당 회원이 활동을 하면 부여되고 그 전에는 부여되지 않습니다.<br />데이터 이전등을 하여 포인트를 완전히 초기화 해야 할 경우에만 사용하세요.';

    $lang->point_link_group = 'グループ連動';
    $lang->about_point_link_group = 'グループにレベルを指定すると、該当レベルになったらグループが変更されます。 ただし、新しいグループに変更されると以前自動登録されたグループは消去されます。';

    $lang->about_module_point = 'モジュール別にポイントを指定することができますが、指定されていないモジュールでは、デフォルトポイントが使用されます。すべてのポイント数は、反対のアクションを行った際には原状復帰されます。';

    $lang->point_signup = '加入';
    $lang->point_insert_document = '書き込み作成';
    $lang->point_delete_document = '書き込み削除';
    $lang->point_insert_comment = 'コメント作成';
    $lang->point_delete_comment = 'コメント削除';
    $lang->point_upload_file = 'アップロード';
    $lang->point_delete_file = 'ファイル削除';
    $lang->point_download_file = 'ダウンロード';
    $lang->point_read_document = '書き込み照会';
    $lang->point_voted = '추천 받음';
    $lang->point_blamed = '비추천 받음';


    $lang->cmd_point_config = 'デフォルト設定';
    $lang->cmd_point_module_config = 'モジュール別設定';
    $lang->cmd_point_act_config = '機能別アクション設定';
    $lang->cmd_point_member_list = '会員ポイントリスト';

    $lang->msg_cannot_download = 'ポイントが不足しているため、ダウンロードできません。';

    $lang->point_recal_message = 'ただ今ポイントを適用しています。 (%d / %d)';
    $lang->point_recal_finished = 'ポイント再計算が完了しました。';
?>
