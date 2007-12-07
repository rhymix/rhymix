<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa
     * @brief  外部ページ(opage)モジュールの基本言語パッケージ
     **/

    $lang->opage = "外部ページ";
    $lang->opage_path = "外部ドキュメントの場所";
    $lang->opage_caching_interval = "キャッシング時間設定";

    $lang->about_opage = "外部のHTMLまたはPHPファイルをゼロボードXE内部で使用できるようにするモジュールです。<br />絶対パス、相対パスで指定でき、「http://」で始まるサーバの外部ページも表示できます。";
    $lang->about_opage_path= "外部ドキュメントの場所を入力してください。<br />「/path1/path2/sample.php」のような絶対パス、「../path2/sample.php」のような相対パスが使用できます。<br />「http://URL/sample.php」のように使用すると結果を読み込んで表示します。<br />現在ゼロボードXEがインストールされている絶対パスは次のようになっています。<br />";
    $lang->about_opage_caching_interval = "分単位で指定でき、設定された時間の間は、臨時保存されたデータを出力します。<br />他のサーバの情報を出力したり、データを出力する際、リソースが多く使われるため、数分単位でキャッシングすることをお勧めします。<br />「0」に指定するとキャッシングされません。";
?>
