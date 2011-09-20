<?php
/**
 * Minify
 * This script comnbines multiple JavaScript or CSS files with minifying.
 * PHP 5.3.0 or above version is required.
 *
 * Usage : php minify.php [TARGET_DIR ...]
 * TARGET_DIR use the current working directory as a default path.
 *
 * @author NHN(developer@xpressengine.com)
 */

if(version_compare(PHP_VERSION, '5.3.0', '<')) {
	echo "PHP 5.3.0 or above version is required.";
	exit(1);
}

function main() {
	$argv = $_SERVER['argv'];
	$argc = $_SERVER['argc'];

	// get target directories
	if($argc < 1) exit;
	elseif($argc < 2) $dirs = array($_SERVER['PWD']);
	else $dirs = array_slice($argv, 1);

	$dirs = array_map('realpath', $dirs);
	$dirs = array_map('add_dirsep', $dirs);

	array_walk($dirs, 'execute');
}

// add directory separator
function add_dirsep($path) {
	if(substr($path,-1) != DIRECTORY_SEPARATOR) $path .= DIRECTORY_SEPARATOR;
	return $path;
}

function execute($dir) {
	echo "Processing : {$dir}\n";

	// parse config file if it exists
	echo "  Finding predefined configuration file...";
	$config = read_config($dir);
	echo " Done\n";

	// merge
	foreach($config['merge'] as $target=>$files) {
		merge($files, $target, $dir);
	}

	// files to skip
	$files_to_skip = $config['skip'];
	foreach($files_to_skip as $idx=>$file) {
		if($file) $files_to_skip[$idx] = realpath($dir.trim($file));
	}

	echo "  Minifying JavaScript files...";
	$js_files = get_target_files('js', $dir, $files_to_skip);

	if(count($js_files) && !class_exists('JSMinPlus')) {
		require dirname(__FILE__).'/jsminplus/jsminplus.php';
	}
	foreach($js_files as $file) {
		if(!is_readable($file)) continue;

		$target  = preg_replace('@\.js$@', '.min.js', $file);
		$content = file_get_contents($file);

		// save copyright to preserve it
		if(preg_match('@^[ \t]*(/\*\*.+?\*/)@s', $content, $matches)) {
			$copyright = $matches[1]."\n";
		} else {
			$copyright = '';
		}

		if($config['use_closure_compiler']) {
			$content = closure_compile($content);
			if(!$content) {
				echo "   CANNOT compile the js file with closure compiler.\n";
				echo "   Trying again with JSMinPlus.\n";
				$content = JSMinPlus::minify($content);
			}
		} else {
			$content = JSMinPlus::minify($content);
		}

		file_put_contents($target, $copyright.$content);

		echo '.';
	}
	echo " Done\n";

	echo "  Minifying CSS files...";
	$css_files = get_target_files('css', $dir, $files_to_skip);

	if(count($css_files) && !class_exists('CssMin')) {
		require dirname(__FILE__).'/cssmin/cssmin.php';
	}

	foreach($css_files as $file) {
		if(!is_readable($file)) continue;

		$target  = preg_replace('@\.css$@', '.min.css', $file);
		$content = file_get_contents($file);

		file_put_contents($target, $copyright.CssMin::minify($content, $option));
		echo '.';
	}
	echo " Done\n";
}

function read_config($dir) {
	$default = array('option'=>array(), 'skip'=>array(), 'merge'=>array());
	$file    = $dir.'minify.ini';

	if(!is_readable($file)) return $default;

	$config_str = file_get_contents($file);
	$config_str = preg_replace_callback('/(\[(?:skip|merge *>> *.+?)\])([\s\S]+?)(?=\[|$)/', 'transform_config_str', $config_str);

	$config = parse_ini_string($config_str, 1);
	if($config === false) return $default;

	if(is_array($config['skip'])) $config['skip'] = array_keys($config['skip']);

	foreach($config as $section=>$value) {
		if(preg_match('/merge *>> *(.+)/', $section, $match)) {
			if(!is_array($config['merge'])) $config['merge'] = array();
			$config['merge'][trim($match[1])] = array_keys($value);

			unset($config[$section]);
		}
	}

	if(is_array($config['option'])) $config = array_merge($config['option'], $config);
	$config = array_merge($default, $config);

	return $config;
}

function transform_config_str($matches) {
	if(!$matches[2]) return $matches[0];
	$values = preg_replace('/$/m', '=', trim($matches[2]));

	return "{$matches[1]}\n{$values}\n\n";
}

function merge($files, $target, $base_dir) {
	if(!is_array($files)) return false;

	$body   = '';
	$is_css = !!preg_match('/\.css$/', $target);

	foreach($files as $file) {
		$file = $base_dir.trim($file);
		if(!is_readable($file)) continue;

		$content = trim(file_get_contents($file));
		if($is_css && $body) $content = preg_replace('/^@.+?;/m', '', $content);
		if($content) $body .= $content."\n";
	}

	if ($body) {
		$file_count = count($files);
		echo "  Merging {$file_count} files to create {$target} file...";
		file_put_contents($base_dir.$target, $body);
		echo " Done\n";
	}
}

function get_target_files($ext, $dir, $files_to_skip) {
	$files = glob("{$dir}*.{$ext}");
	$skips = glob("{$dir}*.min.{$ext}");
	$skips = array_merge($skips, $files_to_skip);
	$files = array_diff($files, $skips);

	return $files;
}

function closure_compile($content) {
	require_once dirname(__FILE__).'/../classes/httprequest/XEHttpRequest.class.php';

	$req = new XEHttpRequest('closure-compiler.appspot.com', 80);
	$ret = $req->send('/compile', 'POST', 5, array(
		'output_info'   => 'compiled_code',
		'output_format' => 'text',
		'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
		'js_code' => $content
	));

	return $ret->body;
}

// run main function
error_reporting(E_ALL & ~E_NOTICE);
main();
