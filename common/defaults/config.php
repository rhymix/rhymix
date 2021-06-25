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
	'cache' => array(
		'type' => null,
		'ttl' => 86400,
		'servers' => array(),
		'truncate_method' => 'delete',
	),
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
		'unregistered_domain_action' => 'display',
		'http_port' => null,
		'https_port' => null,
		'ssl' => 'none',
		'rewrite' => 1,
	),
	'session' => array(
		'delay' => false,
		'use_db' => false,
		'use_keys' => false,
		'use_ssl' => false,
		'use_ssl_cookies' => false,
		'domain' => null,
		'path' => null,
		'lifetime' => 0,
		'refresh' => 300,
	),
	'file' => array(
		'folder_structure' => 2,
		'umask' => '0022',
	),
	'mail' => array(
		'type' => 'mailfunction',
	),
	'view' => array(
		'manager_layout' => 'module',
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
		'log_slow_queries' => 0.25,
		'log_slow_triggers' => 0.25,
		'log_slow_widgets' => 0.25,
		'log_slow_remote_requests' => 1.25,
		'log_filename' => null,
		'display_type' => array('comment'),
		'display_content' => array('request_info', 'entries', 'errors', 'queries'),
		'display_to' => 'admin',
		'query_comment' => false,
		'write_error_log' => 'fatal',
		'allow' => array(),
	),
	'seo' => array(
		'main_title' => '',
		'subpage_title' => '',
		'document_title' => '',
		'og_enabled' => false,
		'og_extract_description' => false,
		'og_extract_images' => false,
		'og_extract_hashtags' => false,
		'og_use_nick_name' => false,
		'og_use_timestamps' => false,
	),
	'mediafilter' => array(
		'whitelist' => array(),
		'classes' => array(),
	),
	'security' => array(
		'robot_user_agents' => array(),
		'check_csrf_token' => false,
		'nofollow' => false,
	),
	'mobile' => array(
		'enabled' => true,
		'tablets' => false,
		'viewport' => 'width=device-width, initial-scale=1.0, user-scalable=yes',
	),
	'use_rewrite' => true,
	'use_sso' => false,
	'other' => array(),
);
