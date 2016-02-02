<?php

/**
 * Default configuration for Rhymix
 * 
 * Copyright (c) Rhymix Developers and Contributors
 */
return array(
	'config_version' => '2.0',
	'db' => array(
		'master' => array(
			'type' => 'mysql',
			'host' => 'localhost',
			'port' => 3306,
			'user' => null,
			'pass' => null,
			'database' => null,
			'prefix' => null,
			'charset' => null,
			'engine' => null,
		),
	),
	'cache' => array(),
	'ftp' => array(
		'host' => 'localhost',
		'port' => 21,
		'path' => null,
		'user' => null,
		'pass' => null,
		'pasv' => true,
		'sftp' => false,
	),
	'crypto' => array(
		'encryption_key' => null,
		'authentication_key' => null,
		'session_key' => null,
	),
	'locale' => array(
		'default_lang' => 'ko',
		'enabled_lang' => array('ko'),
		'default_timezone' => 'Asia/Seoul',
		'internal_timezone' => 32400,
	),
	'url' => array(
		'default' => null,
		'http_port' => null,
		'https_port' => null,
		'ssl' => 'none',
	),
	'session' => array(
		'delay' => false,
		'use_db' => false,
		'domain' => null,
		'path' => null,
		'lifetime' => 0,
		'refresh' => 300,
	),
	'file' => array(
		'umask' => '022',
	),
	'mail' => array(
		'transport' => 'mail',
		'smtp_host' => null,
		'smtp_port' => null,
		'smtp_security' => 'none',
		'smtp_user' => null,
		'smtp_pass' => null,
		'api_domain' => null,
		'api_token' => null,
		'api_user' => null,
		'api_pass' => null,
	),
	'view' => array(
		'minify_scripts' => 'common',
		'concat_scripts' => 'none',
		'use_gzip' => false,
	),
	'admin' => array(
		'allow' => array(),
		'deny' => array(),
	),
	'lock' => array(
		'locked' => false,
		'title' => 'Maintenance',
		'message' => '',
		'allow' => array(),
	),
	'debug' => array(
		'enabled' => true,
		'log_errors' => true,
		'log_queries' => false,
		'log_slow_queries' => 1,
		'log_slow_triggers' => 1,
		'log_slow_widgets' => 1,
		'display_type' => 'comment',
		'display_to' => 'admin',
		'allow' => array(),
	),
	'embedfilter' => array(
		'iframe' => array(),
		'object' => array(),
	),
	'use_mobile_view' => true,
	'use_prepared_statements' => true,
	'use_rewrite' => true,
	'use_sso' => false,
);
