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
		'auto_select_lang' => false,
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
		'type' => 'mailfunction',
	),
	'view' => array(
		'minify_scripts' => 'common',
		'concat_scripts' => 'none',
		'server_push' => false,
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
		'log_slow_queries' => 0,
		'log_slow_triggers' => 0,
		'log_slow_widgets' => 0,
		'log_filename' => null,
		'display_type' => 'comment',
		'display_content' => array(),
		'display_to' => 'admin',
		'allow' => array(),
	),
	'seo' => array(
		'main_title' => '',
		'subpage_title' => '',
		'document_title' => '',
		'og_enabled' => false,
		'og_extract_description' => false,
		'og_extract_images' => false,
		'og_use_timestamps' => false,
	),
	'mediafilter' => array(
		'iframe' => array(),
		'object' => array(),
	),
	'mobile' => array(
		'enabled' => true,
		'tablets' => false,
	),
	'use_prepared_statements' => true,
	'use_rewrite' => true,
	'use_sso' => false,
	'other' => array(),
);
