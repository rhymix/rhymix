<?php
/**
 * Contains methods for accessing file system
 *
 * @author NHN (developers@xpressengine.com)
 **/
class FileHandler {
	/**
	 * Changes path of target file, directory into absolute path
	 *
	 * @param string $source path to change into absolute path
	 * @return string Absolute path
	 **/
	function getRealPath($source) {
		$temp = explode('/', $source);
		if($temp[0] == '.') $source = _XE_PATH_.substr($source, 2);
		return $source;
	}

	/**
	 * Copy a directory to target 
	 *
	 * If target directory does not exist, this function creates it 
	 *
	 * @param string $source_dir Path of source directory
	 * @param string $target_dir Path of target dir
	 * @param string $filter Regex to filter files. If file matches this regex, the file is not copied.
	 * @param string $type If set as 'force'. Even if the file exists in target, the file is copied.
	 * @return void
	 **/
	function copyDir($source_dir, $target_dir, $filter=null,$type=null){
		$source_dir = FileHandler::getRealPath($source_dir);
		$target_dir = FileHandler::getRealPath($target_dir);
		if(!is_dir($source_dir)) return false;
		// generate when no target exists
		if(!file_exists($target_dir)) FileHandler::makeDir($target_dir);

		if(substr($source_dir, -1) != '/') $source_dir .= '/';
		if(substr($target_dir, -1) != '/') $target_dir .= '/';

		$oDir = dir($source_dir);
		while($file = $oDir->read()) {
			if(substr($file,0,1)=='.') continue;
			if($filter && preg_match($filter, $file)) continue;
			if(is_dir($source_dir.$file)){
				FileHandler::copyDir($source_dir.$file,$target_dir.$file,$type);
			}else{
				if($type == 'force'){
					@unlink($target_dir.$file);
				}else{
					if(!file_exists($target_dir.$file)) @copy($source_dir.$file,$target_dir.$file);
				}
			}
		}
	}

	/**
	 * Copy a file to target 
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @param string $force Y: overwrite
	 * @return void
	 **/
	function copyFile($source, $target, $force='Y'){
		setlocale(LC_CTYPE, 'en_US.UTF8', 'ko_KR.UTF8'); 
		$source = FileHandler::getRealPath($source);
		$target_dir = FileHandler::getRealPath(dirname($target));
		$target = basename($target);
		if(!file_exists($target_dir)) FileHandler::makeDir($target_dir);
		if($force=='Y') @unlink($target_dir.'/'.$target);
		@copy($source, $target_dir.'/'.$target);
	}

	/**
	 * Returns the content of the file 
	 *
	 * @param string $file_name Path of target file
	 * @return string The content of the file. If target file does not exist, this function returns nothing.
	 **/
	function readFile($file_name) {
		$file_name = FileHandler::getRealPath($file_name);

		if(!file_exists($file_name)) return;
		$filesize = filesize($file_name);
		if($filesize<1) return;

		if(function_exists('file_get_contents')) return @file_get_contents($file_name);

		$fp = fopen($file_name, "r");
		$buff = '';
		if($fp) {
			while(!feof($fp) && strlen($buff)<=$filesize) {
				$str = fgets($fp, 1024);
				$buff .= $str;
			}
			fclose($fp);
		}
		return $buff;
	}

	/**
	 * Write $buff into the specified file
	 *
	 * @param string $file_name Path of target file
	 * @param string $buff Content to be writeen
	 * @param string $mode a(append) / w(write)
	 * @return void
	 **/
	function writeFile($file_name, $buff, $mode = "w") {
		$file_name = FileHandler::getRealPath($file_name);

		$pathinfo = pathinfo($file_name);
		$path = $pathinfo['dirname'];
		if(!is_dir($path)) FileHandler::makeDir($path);

		$mode = strtolower($mode);
		if($mode != "a") $mode = "w";
		if(@!$fp = fopen($file_name,$mode)) return false;
		fwrite($fp, $buff);
		fclose($fp);
		@chmod($file_name, 0644);
	}

	/**
	 * Remove a file
	 *
	 * @param string $file_name path of target file
	 * @return bool Returns true on success or false on failure.
	 **/
	function removeFile($file_name) {
		$file_name = FileHandler::getRealPath($file_name);
		return (file_exists($file_name) && @unlink($file_name));
	}

	/**
	 * Rename a file
	 *
	 * In order to move a file, use this function.
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @return bool Returns true on success or false on failure.
	 **/
	function rename($source, $target) {
		$source = FileHandler::getRealPath($source);
		$target = FileHandler::getRealPath($target);
		return @rename($source, $target);
	}

	/**
	 * Move a file
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @return bool Returns true on success or false on failure.
	 */
	function moveFile($source, $target) {
		$source = FileHandler::getRealPath($source);
		if(!file_exists($source))
		{
			return FALSE;
		}
		FileHandler::removeFile($target);
		return FileHandler::rename($source, $target);
	}

	/**
	 * Move a directory 
	 *
	 * This function just wraps rename function.
	 *
	 * @param string $source_dir Path of source directory
	 * @param string $target_dir Path of target directory
	 * @return void
	 **/
	function moveDir($source_dir, $target_dir) {
		FileHandler::rename($source_dir, $target_dir);
	}

	/**
	 * Return list of the files in the path
	 *
	 * The array does not contain files, such as '.', '..', and files starting with '.'
	 *
	 * @param string $path Path of target directory
	 * @param string $filter If specified, return only files matching with the filter
	 * @param bool $to_lower If true, file names will be changed into lower case.
	 * @param bool $concat_prefix If true, return file name as absolute path
	 * @return string[] Array of the filenames in the path 
	 **/
	function readDir($path, $filter = '', $to_lower = false, $concat_prefix = false) {
		$path = FileHandler::getRealPath($path);

		if(substr($path,-1)!='/') $path .= '/';
		if(!is_dir($path)) return array();

		$oDir = dir($path);
		while($file = $oDir->read()) {
			if(substr($file,0,1)=='.') continue;

			if($filter && !preg_match($filter, $file)) continue;

			if($to_lower) $file = strtolower($file);

			if($filter) $file = preg_replace($filter, '$1', $file);
			else $file = $file;

			if($concat_prefix) {
				$file = sprintf('%s%s', str_replace(_XE_PATH_, '', $path), $file);
			}

			$output[] = $file;
		}
		if(!$output) return array();

		return $output;
	}

	/**
	 * Creates a directory
	 *
	 * This function creates directories recursively, which means that if ancestors of the target directory does not exist, they will be created too.
	 *
	 * @param string $path_string Path of target directory
	 * @return bool true if success. It might return nothing when ftp is used and connection to the ftp address failed.
	 **/
	function makeDir($path_string) {
		static $oFtp = null;

		// if safe_mode is on, use FTP 
		if(ini_get('safe_mode')) {
			$ftp_info = Context::getFTPInfo();
			if($oFtp == null) {
				if(!Context::isFTPRegisted()) return;

				require_once(_XE_PATH_.'libs/ftp.class.php');
				$oFtp = new ftp();
				if(!$ftp_info->ftp_host) $ftp_info->ftp_host = "127.0.0.1";
				if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
				if(!$oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port)) return;
				if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
					$oFtp->ftp_quit();
					return;
				}
			}
			$ftp_path = $ftp_info->ftp_root_path;
			if(!$ftp_path) $ftp_path = "/";
		}

		$path_string = str_replace(_XE_PATH_,'',$path_string);
		$path_list = explode('/', $path_string);

		$path = _XE_PATH_;
		for($i=0;$i<count($path_list);$i++) {
			if(!$path_list[$i]) continue;
			$path .= $path_list[$i].'/';
			$ftp_path .= $path_list[$i].'/';
			if(!is_dir($path)) {
				if(ini_get('safe_mode')) {
					$oFtp->ftp_mkdir($ftp_path);
					$oFtp->ftp_site("CHMOD 777 ".$ftp_path);
				} else {
					@mkdir($path, 0755);
					@chmod($path, 0755);
				}
			}
		}

		return is_dir($path_string);
	}

	/**
	 * Remove all files under the path
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 **/
	function removeDir($path) {
		$path = FileHandler::getRealPath($path);
		if(!is_dir($path)) return;
		$directory = dir($path);
		while($entry = $directory->read()) {
			if ($entry != "." && $entry != "..") {
				if (is_dir($path."/".$entry)) {
					FileHandler::removeDir($path."/".$entry);
				} else {
					@unlink($path."/".$entry);
				}
			}
		}
		$directory->close();
		@rmdir($path);
	}

	/**
	 * Remove a directory only if it is empty 
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 **/
	function removeBlankDir($path) {
		$item_cnt = 0;

		$path = FileHandler::getRealPath($path);
		if(!is_dir($path)) return;
		$directory = dir($path);
		while($entry = $directory->read()) {
			if ($entry == "." || $entry == "..") continue;
			if (is_dir($path."/".$entry)) $item_cnt = FileHandler::removeBlankDir($path.'/'.$entry);
		}
		$directory->close();

		if($item_cnt < 1) @rmdir($path);
	}


	/**
	 * Remove files in the target directory
	 *
	 * This function keeps the directory structure. 
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 **/
	function removeFilesInDir($path) {
		$path = FileHandler::getRealPath($path);
		if(!is_dir($path)) return;
		$directory = dir($path);
		while($entry = $directory->read()) {
			if ($entry != "." && $entry != "..") {
				if (is_dir($path."/".$entry)) {
					FileHandler::removeFilesInDir($path."/".$entry);
				} else {
					@unlink($path."/".$entry);
				}
			}
		}
		$directory->close();
	}

	/**
	 * Makes file size byte into KB, MB according to the size
	 *
	 * @see FileHandler::returnBytes()
	 * @param int $size Number of the size
	 * @return string File size string
	 **/
	function filesize($size) {
		if(!$size) return '0Byte';
		if($size === 1) return '1Byte';
		if($size < 1024) return $size.'Bytes';
		if($size >= 1024 && $size < 1024*1024) return sprintf("%0.1fKB",$size / 1024);
		return sprintf("%0.2fMB",$size / (1024*1024));
	}

	/**
	 * Return remote file's content via HTTP
	 *
	 * If the target is moved (when return code is 300~399), this function follows the location specified response header.
	 *
	 * @param string $url The address of the target file 
	 * @param string $body HTTP request body
	 * @param int $timeout Connection timeout
	 * @param string $method GET/POST
	 * @param string $content_type Content type header of HTTP request
	 * @param string[] $headers Headers key vaule array.
	 * @param string[] $cookies Cookies key value array.
	 * @param string $post_data Request arguments array for POST method
	 * @return string If success, the content of the target file. Otherwise: none
	 **/
	function getRemoteResource($url, $body = null, $timeout = 3, $method = 'GET', $content_type = null, $headers = array(), $cookies = array(), $post_data = array()) {
		requirePear();
		require_once('HTTP/Request.php');

		$parsed_url = parse_url(__PROXY_SERVER__);
		if($parsed_url["host"]) {
			$oRequest = new HTTP_Request(__PROXY_SERVER__);
			$oRequest->setMethod('POST');
			$oRequest->_timeout = $timeout;
			$oRequest->addPostData('arg', serialize(array('Destination'=>$url, 'method'=>$method, 'body'=>$body, 'content_type'=>$content_type, "headers"=>$headers, "post_data"=>$post_data)));
		} else {
			$oRequest = new HTTP_Request($url);
			if(count($headers)) {
				foreach($headers as $key => $val) {
					$oRequest->addHeader($key, $val);
				}
			}
			if($cookies[$host]) {
				foreach($cookies[$host] as $key => $val) {
					$oRequest->addCookie($key, $val);
				}
			}
			if(count($post_data)) {
				foreach($post_data as $key => $val) {
					$oRequest->addPostData($key, $val);
				}
			}
			if(!$content_type) $oRequest->addHeader('Content-Type', 'text/html');
			else $oRequest->addHeader('Content-Type', $content_type);
			$oRequest->setMethod($method);
			if($body) $oRequest->setBody($body);

			$oRequest->_timeout = $timeout;
		}

		$oResponse = $oRequest->sendRequest();

		$code = $oRequest->getResponseCode();
		$header = $oRequest->getResponseHeader();
		$response = $oRequest->getResponseBody();
		if($c = $oRequest->getResponseCookies()) {
			foreach($c as $k => $v) {
				$cookies[$host][$v['name']] = $v['value'];
			}
		}

		if($code > 300 && $code < 399 && $header['location']) {
			return FileHandler::getRemoteResource($header['location'], $body, $timeout, $method, $content_type, $headers, $cookies, $post_data);
		} 

		if($code != 200) return;

		return $response;
	}

	/**
	 * Retrieves remote file, then stores it into target path.
	 *
	 * @param string $url The address of the target file
	 * @param string $target_filename The location to store
	 * @param string $body HTTP request body
	 * @param string $timeout Connection timeout
	 * @param string $method GET/POST
	 * @param string $content_type Content type header of HTTP request
	 * @param string[] $headers Headers key vaule array.
	 * @return bool true: success, false: failed 
	 **/
	function getRemoteFile($url, $target_filename, $body = null, $timeout = 3, $method = 'GET', $content_type = null, $headers = array()) {
		$body = FileHandler::getRemoteResource($url, $body, $timeout, $method, $content_type, $headers);
		if(!$body) return false;
		$target_filename = FileHandler::getRealPath($target_filename);
		FileHandler::writeFile($target_filename, $body);
		return true;
	}

	/**
	 * Convert size in string into numeric value 
	 *
	 * @see FileHandler::filesize()
	 * @param $val Size in string (ex., 10, 10K, 10M, 10G )
	 * @return int converted size
	 */
	function returnBytes($val)
	{
		$val = trim($val);
		$last = strtolower(substr($val, -1));
		if($last == 'g') $val *= 1024*1024*1024;
		else if($last == 'm') $val *= 1024*1024;
		else if($last == 'k') $val *= 1024;
		else $val *= 1;

		return $val;
	}

	/**
	 * Check available memory to load image file 
	 *
	 * @param array $imageInfo Image info retrieved by getimagesize function 
	 * @return bool true: it's ok, false: otherwise 
	 */
	function checkMemoryLoadImage(&$imageInfo)
	{
		if(!function_exists('memory_get_usage')) return true;
		$K64 = 65536;
		$TWEAKFACTOR = 2.0;
		$channels = $imageInfo['channels'];
		if(!$channels) $channels = 6; //for png
		$memoryNeeded = round( ($imageInfo[0] * $imageInfo[1] * $imageInfo['bits'] * $channels / 8 + $K64 ) * $TWEAKFACTOR );
		$availableMemory = FileHandler::returnBytes(ini_get('memory_limit')) - memory_get_usage();
		if($availableMemory < $memoryNeeded) return false;
		return true;
	}

	/**
	 * Moves an image file (resizing is possible)
	 *
	 * @param string $source_file Path of the source file
	 * @param string $target_file Path of the target file
	 * @param int $resize_width Width to resize 
	 * @param int $resize_height Height to resize
	 * @param string $target_type If $target_type is set (gif, jpg, png, bmp), result image will be saved as target type
	 * @param string $thumbnail_type Thumbnail type(crop, ratio)
	 * @return bool true: success, false: failed 
	 **/
	function createImageFile($source_file, $target_file, $resize_width = 0, $resize_height = 0, $target_type = '', $thumbnail_type = 'crop') {
		$source_file = FileHandler::getRealPath($source_file);
		$target_file = FileHandler::getRealPath($target_file);

		if(!file_exists($source_file)) return;
		if(!$resize_width) $resize_width = 100;
		if(!$resize_height) $resize_height = $resize_width;

		// retrieve source image's information
		$imageInfo = getimagesize($source_file);
		if(!FileHandler::checkMemoryLoadImage($imageInfo)) return false;
		list($width, $height, $type, $attrs) = $imageInfo;

		if($width<1 || $height<1) return;

		switch($type) {
			case '1' :
					$type = 'gif';
				break;
			case '2' :
					$type = 'jpg';
				break;
			case '3' :
					$type = 'png';
				break;
			case '6' :
					$type = 'bmp';
				break;
			default :
					return;
				break;
		}

		// if original image is larger than specified size to resize, calculate the ratio 
		if($resize_width > 0 && $width >= $resize_width) $width_per = $resize_width / $width;
		else $width_per = 1;

		if($resize_height>0 && $height >= $resize_height) $height_per = $resize_height / $height;
		else $height_per = 1;

		if($thumbnail_type == 'ratio') {
			if($width_per>$height_per) $per = $height_per;
			else $per = $width_per;
			$resize_width = $width * $per;
			$resize_height = $height * $per;
		} else {
			if($width_per < $height_per) $per = $height_per;
			else $per = $width_per;
		}

		if(!$per) $per = 1;

		// get type of target file
		if(!$target_type) $target_type = $type;
		$target_type = strtolower($target_type);

		// create temporary image with target size
		if(function_exists('imagecreatetruecolor')) $thumb = imagecreatetruecolor($resize_width, $resize_height);
		else if(function_exists('imagecreate')) $thumb = imagecreate($resize_width, $resize_height);
		else return false;
		if(!$thumb) return false;

		$white = imagecolorallocate($thumb, 255,255,255);
		imagefilledrectangle($thumb,0,0,$resize_width-1,$resize_height-1,$white);

		// create temporary image having original type
		switch($type) {
			case 'gif' :
					if(!function_exists('imagecreatefromgif')) return false;
					$source = @imagecreatefromgif($source_file);
				break;
			// jpg
			case 'jpeg' :
			case 'jpg' :
					if(!function_exists('imagecreatefromjpeg')) return false;
					$source = @imagecreatefromjpeg($source_file);
				break;
			// png
			case 'png' :
					if(!function_exists('imagecreatefrompng')) return false;
					$source = @imagecreatefrompng($source_file);
				break;
			// bmp
			case 'wbmp' :
			case 'bmp' :
					if(!function_exists('imagecreatefromwbmp')) return false;
					$source = @imagecreatefromwbmp($source_file);
				break;
			default :
				return;
		}

		// resize original image and put it into temporary image
		$new_width = (int)($width * $per);
		$new_height = (int)($height * $per);

		if($thumbnail_type == 'crop') {
			$x = (int)($resize_width/2 - $new_width/2);
			$y = (int)($resize_height/2 - $new_height/2);
		} else {
			$x = 0;
			$y = 0;
		}

		if($source) {
			if(function_exists('imagecopyresampled')) imagecopyresampled($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
			else imagecopyresized($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
		} else return false;

		// create directory 
		$path = dirname($target_file);
		if(!is_dir($path)) FileHandler::makeDir($path);

		// write into the file
		switch($target_type) {
			case 'gif' :
					if(!function_exists('imagegif')) return false;
					$output = imagegif($thumb, $target_file);
				break;
			case 'jpeg' :
			case 'jpg' :
					if(!function_exists('imagejpeg')) return false;
					$output = imagejpeg($thumb, $target_file, 100);
				break;
			case 'png' :
					if(!function_exists('imagepng')) return false;
					$output = imagepng($thumb, $target_file, 9);
				break;
			case 'wbmp' :
			case 'bmp' :
					if(!function_exists('imagewbmp')) return false;
					$output = imagewbmp($thumb, $target_file, 100);
				break;
		}

		imagedestroy($thumb);
		imagedestroy($source);

		if(!$output) return false;
		@chmod($target_file, 0644);

		return true;
	}

	/**
	 * Reads ini file, and puts result into array
	 *
	 * @see FileHandler::writeIniFile()
	 * @param string $filename Path of the ini file
	 * @return array ini array (if the target file does not exist, it returns false)
	 **/
	function readIniFile($filename){
		$filename = FileHandler::getRealPath($filename);
		if(!file_exists($filename)) return false;
		$arr = parse_ini_file($filename, true);
		if(is_array($arr) && count($arr)>0) return $arr;
		else return array();
	}


	/**
	 * Write array into ini file
	 *
	 *	$ini['key1'] = 'value1';<br/>
	 *	$ini['key2'] = 'value2';<br/>
	 *	$ini['section']['key1_in_section'] = 'value1_in_section';<br/>
	 *	$ini['section']['key2_in_section'] = 'value2_in_section';<br/>
	 *	FileHandler::writeIniFile('exmple.ini', $ini);
	 *
	 * @see FileHandler::readIniFile()
	 * @param string $filename Target ini file name
	 * @param array $arr Array
	 * @return bool if array contains nothing it returns false, otherwise true
	 **/
	function writeIniFile($filename, $arr){
		if(count($arr)==0) return false;
		FileHandler::writeFile($filename, FileHandler::_makeIniBuff($arr));
		return true;
	}

	/**
	 * Make array to ini string
	 *
	 * @param array $arr Array
	 * @return string
	 */
	function _makeIniBuff($arr){
		$return = '';
		foreach($arr as $key => $val){
			// section
			if(is_array($val)){
				$return .= sprintf("[%s]\n",$key);
				foreach($val as $k => $v){
					$return .= sprintf("%s=\"%s\"\n",$k,$v);
				}
			// value
			}else if(is_string($val) || is_int($val)){
				$return .= sprintf("%s=\"%s\"\n",$key,$val);
			}
		}
		return $return;
	}

	/**
	 * Returns a file object 
	 *
	 * If the directory of the file does not exist, create it.
	 *
	 * @param string $filename Target file name
	 * @param string $mode File mode for fopen
	 * @return FileObject File object 
	 **/
	function openFile($filename, $mode)
	{
		$pathinfo = pathinfo($filename);
		$path = $pathinfo['dirname'];
		if(!is_dir($path)) FileHandler::makeDir($path);

		require_once("FileObject.class.php");
		$file_object = new FileObject($file_name, $mode);
		return $file_object;
	}

	/**
	 * Check whether the given file has the content.
	 *
	 * @param string $filename Target file name
	 * @return bool Returns true if the file exists and contains something.
	 */
	function hasContent($filename)
	{
		return (is_readable($filename) && !!filesize($filename));
	}
}

/* End of file FileHandler.class.php */
/* Location: ./classes/file/FileHandler.class.php */
