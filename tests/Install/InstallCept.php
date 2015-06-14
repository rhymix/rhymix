<?php
use \Codeception\Configuration;

$I = new InstallTester($scenario);

$config = (!$this->env) ? Configuration::suiteSettings('Install', Configuration::config()) : Configuration::suiteEnvironments('Install')[$this->env];

$db_config = $config['modules']['config']['Db'];

$dsn = $db_config['dsn'];
$dsn = split('[;:]', $dsn);
$db_type = array_shift($dsn);
$dbinfo = [
    'type' => $db_type,
    'user' => $db_config['user'],
    'password' => $db_config['password'],
    'dbname' => 'xe_install',
    'port' => ((isset($db_config['port']) && $db_config['port'])?: 3306),
];
foreach($dsn as $piece) {
    list($key, $val) = explode('=', $piece);
    $dbinfo[$key] = $val;
}

if(\Filehandler::exists(_XE_PATH_ . 'config/install.config.php')) {
    $I->deleteFile(_XE_PATH_ . 'config/install.config.php');
}

// Step 1
$I->wantTo('Install XE Core');
$I->amOnPage('/index.php?l=ko');
$I->setCookie('l', 'ko');
$I->seeElement('//div[@id="progress"]/ul/li[1][@class="active"]');
$I->seeElement('#content .language');
$I->seeElement('//ul[@class="language"]/li[2]/strong');
$I->click('#task-choose-language');

// Step 2 : License Agreement
$I->seeInCurrentUrl('act=dispInstallLicenseAgreement');
$I->seeElement('//div[@id="progress"]/ul/li[2][@class="active"]');
$I->see('사용권 동의', '#content');
$I->submitForm('.x_form-horizontal', ['act' => 'procInstallLicenseAggrement', 'license_agreement' => 'Y']);

// Step 3 : checkenv
$I->seeInCurrentUrl('act=dispInstallCheckEnv');
$I->seeElement('//div[@id="progress"]/ul/li[3][@class="active"]');
$I->seeElement('#content .x_icon-ok-sign');
$I->click('#task-checklist-confirm');

// Step 5 : SelectDB
$I->seeInCurrentUrl('act=dispInstallSelectDB');
$I->seeElement('//div[@id="progress"]/ul/li[5][@class="active"]');
$I->submitForm('#content form', ['db_type' => 'mysqli', 'act' => 'dispInstallDBForm']);

// Step 6 : db info
// $I->seeInCurrentUrl('act=dispInstallDBForm');
$I->seeElement('//div[@id="progress"]/ul/li[6][@class="active"]');
$I->submitForm('#content form', [
    'act' => 'procMysqlDBSetting',
    'db_type' => 'mysqli',
    'db_userid' => $dbinfo['user'],
    'db_password' => $dbinfo['password'],
    'db_database' => $dbinfo['dbname'],
    'db_hostname' => $dbinfo['host'],
    'db_port' => $dbinfo['port'],
    'db_table_prefix' => 'xe'
]);


// Step 7 : dispInstallConfigForm
$I->seeInCurrentUrl('act=dispInstallConfigForm');
$I->seeElement('//div[@id="progress"]/ul/li[7][@class="active"]');
$I->seeElement('select[name=time_zone]');
$I->submitForm('#content form', ['act' => 'procConfigSetting', 'time_zone' => '+0900']);


// Step 8 : dispInstallManagerForm
$I->seeInCurrentUrl('act=dispInstallManagerForm');
$I->seeElement('//div[@id="progress"]/ul/li[8][@class="active"]');
$I->fillField('#aMail', 'admin@admin.net');
$I->submitForm('#content form', [
    'act' => 'procInstall',
    'db_type' => 'mysqli',
    'email_address' => 'admin@admin.net',
    'password' => 'admin',
    'password2' => 'admin',
    'nick_name' => 'admin',
    'user_id' => 'admin'
]);

// Step 9
$I->dontSeeElement('//div[@id="progress"]/ul/li');
$I->amOnPage('/index.php?act=dispMemberLoginForm');

$I->fillField('user_id', 'admin@admin.net');
$I->submitForm('.login-body form', [
    'act' => 'procMemberLogin',
    'user_id' => 'admin@admin.net',
    'password' => 'admin',
    'success_return_url' => '/index.php?module=admin'
]);

$I->seeInCurrentUrl('module=admin');
$I->seeElement('#gnbNav');
$I->seeElement('#content .x_page-header');
$I->see('설치 환경 수집 동의', 'h2');

