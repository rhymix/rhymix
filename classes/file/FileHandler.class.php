<?php
    /**
    * @class FileHandler
    * @author zero (zero@nzeo.com)
    * @brief 파일시스템을 쉽게 사용하기 위한 method를 모은 클래스
    *
    * 굳이 class로 만들필요는 없는데.. 소스 코드의 관리를 위하여..
    **/

    class FileHandler extends Handler {

        /**
         * @brief 파일의 내용을 읽어서 return
         **/
        function readFile($file_name) {
            if(!file_exists($file_name)) return;

            $filesize = filesize($file_name);

            if($filesize<1) return;

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
         * @brief $buff의 내용을 파일에 쓰기
         **/
        function writeFile($file_name, $buff, $mode = "w") {
            $mode = strtolower($mode);
            if($mode != "a") $mode = "w";
            if(@!$fp = fopen($file_name,$mode)) return false;
            fwrite($fp, $buff);
            fclose($fp);
        }

        /**
         * @brief 특정 디렉토리를 이동
         **/
        function moveDir($source_dir, $target_dir) {
            if(!is_dir($source_dir)) return;

            if(!is_dir($target_dir)) {
                FileHandler::makeDir($target_dir);
                @unlink($target_dir);
            }

            @rename($source_dir, $target_dir); 
        }

        /**
         * @brief $path내의 파일들을 return ('.', '..', '.로 시작하는' 파일들은 제외)
         **/
        function readDir($path, $filter = '', $to_lower = false, $concat_prefix = false) {
            if(substr($path,-1)!='/') $path .= '/';
            if(!is_dir($path)) return array();
            $oDir = dir($path);
            while($file = $oDir->read()) {
                if(substr($file,0,1)=='.') continue;
                if($filter && !preg_match($filter, $file)) continue;
                if($to_lower) $file = strtolower($file);
                if($filter) $file = preg_replace($filter, '$1', $file);
                else $file = $file;

                if($concat_prefix) $file = $path.$file;
                $output[] = $file;
            }
            if(!$output) return array();
            return $output;
        }

        /**
         * @brief 디렉토리 생성
         *
         * 주어진 경로를 단계별로 접근하여 recursive하게 디렉토리 생성
         **/
        function makeDir($path_string) {
            $path_list = explode('/', $path_string);

            for($i=0;$i<count($path_list);$i++) {
                $path .= $path_list[$i].'/';
                if(!is_dir($path)) {
                    @mkdir($path, 0707);
                    @chmod($path, 0707);
                }
            }

            return is_dir($path_string);
        }

        /**
         * @brief 지정된 디렉토리 이하 모두 파일을 삭제
         **/
        function removeDir($path) {
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
         * @brief byte단위의 파일크기를 적절하게 변환해서 return
         **/
        function filesize($size) {
            if(!$size) return "0Byte";
            if($size<1024) return ($size."Byte");
            if($size >1024 && $size< 1024 *1024) return sprintf("%0.1fKB",$size / 1024);
            return sprintf("%0.2fMB",$size / (1024*1024));
        }

        /**
         * @brief 원격파일을 다운받아서 특정 위치에 저장
         **/
        function getRemoteFile($url, $target_filename) {
            $url_info = parse_url($url);

            if(!$url_info['port']) $url_info['port'] = 80;

            $fp = fsockopen($url_info['host'], $url_info['port']);
            if(!$fp) return;

            $header = sprintf("GET %s HTTP/1.0\r\nHost: %s\r\nReferer: %s://%s\r\n\r\n", $url_info['path'], $url_info['host'], $url_info['scheme'], $url_info['host']); 
            fwrite($fp, $header);

            $ft = fopen($target_filename, 'w');
            if(!$ft) return;

            $begin = false;
            while(!feof($fp)) {
                $str = fgets($fp, 1024);
                if($begin) fwrite($ft, $str);
                if(!trim($str)) $begin = true;
            }
            fclose($ft);
            fclose($fp);

            return true;
        }

        /**
         * @brief 특정 이미지 파일을 특정 위치로 옮김 (옮길때 이미지의 크기를 리사이징할 수 있음..)
         **/
        function createImageFile($source_file, $target_file, $resize_width = 0, $resize_height = 0, $target_type = '') {
            if(!file_exists($source_file)) return;

            // 이미지 정보를 구함
            list($width, $height, $type, $attrs) = @getimagesize($source_file);
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

            if(!$target_type) $target_type = $type;
            $target_type = strtolower($target_type);

            // 이미지 정보가 정해진 크기보다 크면 크기를 바꿈
            $new_width = $width;
            if($resize_width>0 && $new_width > $resize_width) $new_width = $resize_width;
            $new_height = $height;
            if($resize_height>0 && $new_height > $resize_height) $new_height = $resize_height;

            // 업로드한 파일을 옮기지 않고 gd를 이용해서 gif 이미지를 만듬 (gif, jpg, png, bmp가 아니면 역시 무시) 
            if(function_exists('imagecreatetruecolor')) $thumb = @imagecreatetruecolor($new_width, $new_height);
            else $thumb = @imagecreate($new_width, $new_height);

            switch($type) {
                case 'gif' : 
                        $source = @imagecreatefromgif($source_file);
                    break;
                // jpg
                case 'jpeg' : 
                case 'jpg' : 
                        $source = @imagecreatefromjpeg($source_file);
                    break;
                // png
                case 'png' : 
                        $source = @imagecreatefrompng($source_file);
                    break;
                // bmp
                case 'wbmp' : 
                case 'bmp' : 
                        $source = @imagecreatefromwbmp($source_file);
                    break;
                default :
                    return;
            }

            if(!$source) return;

            // 디렉토리 생성
            $path = preg_replace('/\/([^\.^\/]*)\.(gif|png|jpeg|bmp|wbmp)$/i','',$target_file);
            FileHandler::makeDir($path);

            if($new_width != $width || $new_height != $height) {
                if(function_exists('imagecopyresampled')) imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                else imagecopyresized($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            } else $thumb = $source;

            // 파일을 쓰고 끝냄
            switch($target_type) {
                case 'gif' :
                        imagegif($thumb, $target_file, 100);
                    break;
                case 'jpeg' :
                case 'jpg' :
                        imagejpeg($thumb, $target_file, 100);
                    break;
                case 'png' :
                        imagepng($thumb, $target_file, 100);
                    break;
                case 'wbmp' :
                case 'bmp' :
                        imagewbmp($thumb, $target_file, 100);
                    break;
            }
            @unlink($source_file);
        }
    }
?>
