<?php
use \Codeception\Configuration;

$I = new InstallTester($scenario);

if (isset($this->env))
{
    $config = Configuration::suiteEnvironments('install')[$this->env];
}
else
{
    $config = Configuration::suiteSettings('install', Configuration::config());
}

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

mkdir(_XE_PATH_ . 'files/env', 0755, true);
file_put_contents(_XE_PATH_ . 'files/env/easyinstall_last', time());

// Step 1 : License Agreement
$I->wantTo('Install Rhymix');
$I->amOnPage('/index.php?l=ko');
$I->setCookie('l', 'ko');
$I->seeElement('//div[@id="progress"]/ul/li[1][@class="active"]');
$I->seeElement('input[name="license_agreement"]');
$I->submitForm('#body', ['act' => 'procInstallLicenseAgreement', 'license_agreement' => 'Y']);

// Step 2 : Environment Check
$I->seeInCurrentUrl('act=dispInstallCheckEnv');
$I->seeElement('#task-checklist-confirm');
$I->click('#task-checklist-confirm');

// Step 3 : DB Setup
$I->seeInCurrentUrl('act=dispInstallDBConfig');
$I->seeElement('input[name="db_host"]');
$I->submitForm('#body', [
	'act' => 'procDBConfig',
	'db_type' => 'mysql',
    'db_host' => $dbinfo['host'],
    'db_port' => $dbinfo['port'],
    'db_user' => $dbinfo['user'],
    'db_pass' => $dbinfo['password'],
    'db_database' => $dbinfo['dbname'],
    'db_prefix' => 'rx',
]);

// Step 4 : Create Admin Account
$I->seeInCurrentUrl('act=dispInstallOtherConfig');
$I->seeElement('select[name="time_zone"]');
$I->fillField('#aMail', 'admin@admin.net');
$I->submitForm('#body', [
    'act' => 'procInstall',
    'time_zone' => '+0900',
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
