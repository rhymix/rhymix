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
         * @brief 대상 파일, 디렉토리의 절대경로를 반환
         **/
        function getRealPath($source) {
            $temp = explode('/', $source);
            if($temp[0] == '.') $source = _XE_PATH_.substr($source, 2);
            return $source;
        }



        /**
         * @brief 특정 디렉토리를 복사
         **/
        function copyDir($source_dir, $target_dir, $filter=null,$type=null){
            $source_dir = FileHandler::getRealPath($source_dir);
            $target_dir = FileHandler::getRealPath($target_dir);
            if(!is_dir($source_dir)) return false;

            // target이 없을땐 생성
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
         * @brief 파일의 내용을 읽어서 return
         **/
        function readFile($file_name) {
            $file_name = FileHandler::getRealPath($file_name);

            if(!file_exists($file_name)) return;
            $filesize = filesize($file_name);
            if($filesize<1) return;

            if(function_exists('file_get_contents')) return file_get_contents($file_name);

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
         * @brief 파일 삭제
         **/
        function removeFile($file_name) {
            $file_name = FileHandler::getRealPath($file_name);
            if(file_exists($file_name)) @unlink($file_name);
        }

        /**
         * @brief 파일이름이나 디렉토리명이나 위치 변경
         **/
        function rename($source, $target) {
            $source = FileHandler::getRealPath($source);
            $target = FileHandler::getRealPath($target);
            @rename($source, $target);
        }

        /**
         * @brief 특정 디렉토리를 이동
         **/
        function moveDir($source_dir, $target_dir) {
            FileHandler::rename($source_dir, $target_dir);
        }

        /**
         * @brief $path내의 파일들을 return ('.', '..', '.로 시작하는' 파일들은 제외)
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
         * @brief 디렉토리 생성
         *
         * 주어진 경로를 단계별로 접근하여 recursive하게 디렉토리 생성
         **/
        function makeDir($path_string) {
            static $oFtp = null;

            // safe_mode 일 경우 ftp 정보를 이용해서 디렉토리 생성
            if(ini_get('safe_mode') && $oFtp == null) {
                if(!Context::isFTPRegisted()) return;

                require_once(_XE_PATH_.'libs/ftp.class.php');
                $ftp_info = Context::getFTPInfo();
                $oFtp = new ftp();
                if(!$oFtp->ftp_connect('localhost')) return;
                if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                    $oFtp->ftp_quit();
                    return;
                }
            }

            $path_string = str_replace(_XE_PATH_,'',$path_string);
            $path_list = explode('/', $path_string);

            $path = _XE_PATH_;
            for($i=0;$i<count($path_list);$i++) {
                if(!$path_list[$i]) continue;
                $path .= $path_list[$i].'/';
                if(!is_dir($path)) {
                    if(ini_get('safe_mode')) {
                        $oFtp->ftp_mkdir($path);
                        $oFtp->ftp_site("CHMOD 777 ".$path);
                    } else {
                        @mkdir($path, 0755);
                        @chmod($path, 0755);
                    }
                }
            }

            return is_dir($path_string);
        }

        /**
         * @brief 지정된 디렉토리 이하 모두 파일을 삭제
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
         * @brief 지정된 디렉토리에 내용이 없으면 삭제
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
         * @biref 지정된 디렉토리를 제외한 모든 파일을 삭제
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
            $target_filename = FileHandler::getRealPath($target_filename);

            $url_info = parse_url($url);

            if(!$url_info['port']) $url_info['port'] = 80;
            if(!$url_info['path']) $url_info['path'] = '/';

            $fp = @fsockopen($url_info['host'], $url_info['port']);
            if(!$fp) return;

            // 한글 파일이 있으면 한글파일 부분만 urlencode하여 처리 (iconv 필수)
            /*
            $path = $url_info['path'];
            if(preg_match('/[\xEA-\xED][\x80-\xFF]{2}/', $path)&&function_exists('iconv')) {
                $path_list = explode('/',$path);
                $cnt = count($path_list);
                $filename = $path_list[$cnt-1];
                $filename = urlencode(iconv("UTF-8","EUC-KR",$filename));
                $path_list[$cnt-1] = $filename;
                $path = implode('/',$path_list);
                $url_info['path'] = $path;
            }
            */

            $header = sprintf("GET %s%s HTTP/1.0\r\nHost: %s\r\nAccept-Charset: utf-8;q=0.7,*;q=0.7\r\nReferer: %s://%s\r\nRequestUrl: %s\r\nConnection: Close\r\n\r\n", $url_info['path'], $url_info['query']?'?'.$url_info['query']:'', $url_info['host'], $url_info['scheme'], $url_info['host'], Context::getRequestUri());

            @fwrite($fp, $header);

            $ft = @fopen($target_filename, 'w');
            if(!$ft) return;

            $begin = false;
            while(!feof($fp)) {
                $str = fgets($fp, 1024);
                if($begin) @fwrite($ft, $str);
                if(!trim($str)) $begin = true;
            }
            @fclose($ft);
            @fclose($fp);
            @chmod($target_filename, 0644);

            return true;
        }

        /**
         * @brief 특정 이미지 파일을 특정 위치로 옮김 (옮길때 이미지의 크기를 리사이징할 수 있음..)
         **/
        function createImageFile($source_file, $target_file, $resize_width = 0, $resize_height = 0, $target_type = '', $thumbnail_type = 'crop') {
            $source_file = FileHandler::getRealPath($source_file);
            $target_file = FileHandler::getRealPath($target_file);

            if(!file_exists($source_file)) return;
            if(!$resize_width) $resize_width = 100;
            if(!$resize_height) $resize_height = $resize_width;

            // 이미지 정보를 구함
            list($width, $height, $type, $attrs) = @getimagesize($source_file);
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

            // 이미지 정보가 정해진 크기보다 크면 크기를 바꿈 (%를 구해서 처리)
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

            // 타겟 파일의 type을 구함
            if(!$target_type) $target_type = $type;
            $target_type = strtolower($target_type);

            // 리사이즈를 원하는 크기의 임시 이미지를 만듬
            if(function_exists('imagecreatetruecolor')) $thumb = @imagecreatetruecolor($resize_width, $resize_height);
            else $thumb = @imagecreate($resize_width, $resize_height);

            $white = @imagecolorallocate($thumb, 255,255,255);
            @imagefilledrectangle($thumb,0,0,$resize_width-1,$resize_height-1,$white);

            // 원본 이미지의 타입으로 임시 이미지 생성
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

            // 원본 이미지의 크기를 조절해서 임시 이미지에 넣음
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
                if(function_exists('imagecopyresampled')) @imagecopyresampled($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
                else @imagecopyresized($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
            } else return false;

            // 디렉토리 생성
            $path = dirname($target_file);
            if(!is_dir($path)) FileHandler::makeDir($path);

            // 파일을 쓰고 끝냄
            switch($target_type) {
                case 'gif' :
                        $output = @imagegif($thumb, $target_file);
                    break;
                case 'jpeg' :
                case 'jpg' :
                        $output = @imagejpeg($thumb, $target_file, 100);
                    break;
                case 'png' :
                        $output = @imagepng($thumb, $target_file, 9);
                    break;
                case 'wbmp' :
                case 'bmp' :
                        $output = @imagewbmp($thumb, $target_file, 100);
                    break;
            }

            @imagedestroy($thumb);
            @imagedestroy($source);

            if(!$output) return false;
            @chmod($target_file, 0644);

            return true;
        }
    }
?>
