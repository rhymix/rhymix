<?php
use \Codeception\Configuration;

$I = new InstallTester($scenario);

$config = (!$this->env) ? Configuration::suiteSettings('Install', Configuration::config()) : Configuration::suiteEnvironments('Install')[$this->env];

$db_config = $config['modules']['config']['Db'];

$dsn = $db_config['dsn'];
$dsn = preg_split('/[;:]/', $dsn);
$db_type = array_shift($dsn);
$dbinfo = [
    'type' => $db_type,
    'user' => $db_config['user'],
    'password' => $db_config['password'],
    'dbname' => 'rhymix',
    'port' => ((isset($db_config['port']) && $db_config['port'])?: 3306),
];
foreach($dsn as $piece) {
    list($key, $val) = explode('=', $piece);
    $dbinfo[$key] = $val;
}

if(file_exists(_XE_PATH_ . 'config/install.config.php')) {
    $I->deleteFile(_XE_PATH_ . 'config/install.config.php');
}

// Step 1 : License Agreement
$I->wantTo('Install RhymiX');
$I->amOnPage('/index.php?l=ko');
$I->setCookie('l', 'ko');
$I->seeElement('//div[@id="progress"]/ul/li[1][@class="active"]');
$I->seeElement('input[name="license_agreement"]');
$I->submitForm('#body', ['act' => 'procInstallLicenseAggrement', 'license_agreement' => 'Y']);

// Step 2 : Environment Check
$I->seeInCurrentUrl('act=dispInstallCheckEnv');
$I->seeElement('#task-checklist-confirm');
$I->click('#task-checklist-confirm');

// Step 3 : DB Setup
$I->seeInCurrentUrl('act=dispInstallSelectDB');
$I->seeElement('select[name="db_type"]');
$I->submitForm('#body', [
	'act' => 'procDBSetting',
	'db_type' => 'mysqli_innodb',
    'db_hostname' => $dbinfo['host'],
    'db_port' => $dbinfo['port'],
    'db_userid' => $dbinfo['user'],
    'db_password' => $dbinfo['password'],
    'db_database' => $dbinfo['dbname'],
    'db_table_prefix' => 'rx'
]);

// Step 4 : Create Admin Account
$I->seeInCurrentUrl('act=dispInstallManagerForm');
$I->seeElement('select[name="time_zone"]');
$I->fillField('#aMail', 'admin@admin.net');
$I->submitForm('#body', [
    'act' => 'procInstall',
    'time_zone' => '+0900',
    'db_type' => 'mysqli_innodb',
    'email_address' => 'admin@admin.net',
    'password' => 'admin',
    'password2' => 'admin',
    'nick_name' => 'admin',
    'user_id' => 'admin'
]);

// Step 5 : Complete
$I->dontSeeElement('//div[@id="progress"]/ul/li');

// Step 6 : Login
$I->amOnPage('/index.php?act=dispMemberLoginForm');
$I->fillField('user_id', 'admin@admin.net');
$I->submitForm('.login-body form', [
    'act' => 'procMemberLogin',
    'user_id' => 'admin@admin.net',
    'password' => 'admin',
    'success_return_url' => '/index.php?module=admin'
]);

// Step 7 : Admin Module
$I->seeInCurrentUrl('module=admin');
$I->seeElement('#gnbNav');
$I->seeElement('#content .x_page-header');
