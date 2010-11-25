<?php
    /**
     * @file   modules/opage/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa
     * @brief  外部ページ(opage)モジュールの基本言語パッケージ
     **/

    $lang->opage = '外部ページ';
    $lang->opage_path = '外部ドキュメントの場所';
    $lang->opage_caching_interval = 'キャッシング時間設定';

    $lang->about_opage = '外部のHTMLまたはPHPファイルをXE内部で使用出来るようにするモジュールです。<br />絶対パス、相対パスで指定出来、「http://」で始まるサーバの外部ページも表示出来ます。';
    $lang->about_opage_path= '外部ドキュメントの場所を入力して下さい。<br />「/path1/path2/sample.php」のような絶対パス、「../path2/sample.php」のような相対パスが使用出来ます。<br />「http://URL/sample.php」のように使用すると結果を読み込んで表示します。<br />現在XEがインストールされている絶対パスは次のようになっています。<br />';
    $lang->about_opage_caching_interval = '分単位で指定出来、設定された時間の間は、臨時保存されたデータを出力します。<br />他のサーバの情報を出力したり、データを出力する際、リソースが多く使われるため、数分単位でキャッシングすることをお勧めします。<br />「0」に指定するとキャッシングされません。';
	$lang->opage_mobile_path = 'モバイルバージョン文書へのパス';
	$lang->about_opage_mobile_path= "モバイルバージョンドキュメントへのパスを指定して下さい。　指定してないと一般ドキュメントのパスに指定されます。<br />/path1/path2/sample.php のような絶対パスや ../path2/sample.phpのような相対パス両方とも指定できます。<br />指定すると対応するドキュメントをウェブからダウンロードして表示します。<br />現在、XEの設置されている絶対パスは次のようになります。";
?>
