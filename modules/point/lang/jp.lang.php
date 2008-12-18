<?php
    /**
     * @file   modules/point/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa、ミニミ
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

    $lang->cmd_point_recal = 'ポイントの初期化';
    $lang->about_cmd_point_recal = '書き込み/コメント/添付ファイル/会員登録のポイントのみ取り、全ての他のポイントを初期化します。<br />但し、会員登録ポイントは初期化後、該当会員の活動がスタートしたら付与されます。<br />データ移管などによるポイントを完全に初期化する必要がある場合など、利用は慎重に行なって下さい。';

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
    $lang->point_read_document = '書き込み閲覧';
    $lang->point_voted = '推薦';
    $lang->point_blamed = '非推薦';

    $lang->cmd_point_config = 'デフォルト設定';
    $lang->cmd_point_module_config = 'モジュール別設定';
    $lang->cmd_point_act_config = '機能別アクション設定';
    $lang->cmd_point_member_list = '会員ポイントリスト';

    $lang->msg_cannot_download = 'ポイントが不足しているため、ダウンロードできません。';

    $lang->point_recal_message = 'ただ今ポイントを適用しています。 (%d / %d)';
    $lang->point_recal_finished = 'ポイント再計算が完了しました。';
?>
