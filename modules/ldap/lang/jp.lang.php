<?php
    /**
     * @file   modules/ldap/lang/ko.lang.php
     * @author zero <zero@nzeo.com>　翻訳：ミニミ
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->ldap = 'LDAP認証連動';
    $lang->use_ldap = 'LDAP認証連動を使用する';
    $lang->ldap_server = 'LDAPサーバーアドレス';
    $lang->ldap_port = 'LDAPサーバーポート番号';
    $lang->ldap_userdn_suffix = 'ユーザーサフィックス';
    $lang->ldap_basedn = 'ベースDN（suffix）';

    $lang->ldap_email_entry = '会員メールアカウントカラム';
    $lang->ldap_nickname_entry = '会員名カラム';
    $lang->ldap_username_entry = '会員のニックネームカラム';
    $lang->ldap_group_entry = '会員のグループカラム';

    $lang->about_use_ldap = 'LDAP認証連動のためには、下記のサーバー情報とともに上にチェックして下さい。';
    $lang->about_ldap_server = 'LDAPサーバー情報を入力してください。'; 
    $lang->about_ldap_port = 'LDAPサーバーのポート（port）番号情報を入力してください。';
    $lang->about_ldap_userdn_suffix = '認証のためのユーザーサフィックスを入力してください。 (例: @abc.com)';
    $lang->about_ldap_basedn = 'ディレクトリの ベースDN（suffix）をログインして下さい。 (例: dc=abc,dc=com)';

    $lang->about_ldap_email_entry = 'LDAP情報中、会員のメールアカウント情報として使うカラム名を入力してください。 (重複不可)';
    $lang->about_ldap_username_entry = 'LDAP情報中、会員名情報として使うカラム名を入力してください。 (重複可能)';
    $lang->about_ldap_nickname_entry = 'LDAP情報中、会員のニックネーム情報として使うカラム名を入力してください。 (重複不可)';
    $lang->about_ldap_group_entry = 'LDAP情報中、会員のグループ情報として使うカラム名を入力してください。';

?>
