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
                if(!$path_list[$i]) continue;
                $path .= $path_list[$i].'/';
                if(!is_dir($path)) {
                    @mkdir($path, 0755);
                    @chmod($path, 0755);
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
            if(!$url_info['path']) $url_info['path'] = '/';

            $fp = @fsockopen($url_info['host'], $url_info['port']);
            if(!$fp) return;

            // 한글 파일이 있으면 한글파일 부분만 urlencode하여 처리 (iconv 필수)
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

            $header = sprintf("GET %s?%s HTTP/1.0\r\nHost: %s\r\nReferer: %s://%s\r\nRequestUrl: %s\r\nConnection: Close\r\n\r\n", $url_info['path'], $url_info['query'], $url_info['host'], $url_info['scheme'], $url_info['host'], Context::getRequestUri()); 

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
        function createImageFile($source_file, $target_file, $resize_width = 0, $resize_height = 0, $target_type = '') {
            if(!file_exists($source_file)) return;

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

            // 타겟 파일의 type을 구함
            if(!$target_type) $target_type = $type;
            $target_type = strtolower($target_type);

            // 리사이즈를 원하는 크기의 임시 이미지를 만듬
            if(function_exists('imagecreatetruecolor')) $thumb = @imagecreatetruecolor($resize_width, $resize_height);
            else $thumb = @imagecreate($resize_width, $resize_height);

            $white = @imagecolorallocate($thumb, 255,255,255);
            @imagefilledrectangle($thumb,0,0,$resize_width-1,$resize_height-1,$white);

            // 이미지 정보가 정해진 크기보다 크면 크기를 바꿈 (%를 구해서 처리)
            if($resize_width>0 && $width >= $resize_width) $width_per = $resize_width / $width;
            if($resize_height>0 && $height >= $resize_height) $height_per = $resize_height / $height;
            if($width_per < $height_per) $per = $height_per;
            else $per = $width_per;
            if(!$per) $per = 1;

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

            // 디렉토리 생성
            $path = preg_replace('/\/([^\.^\/]*)\.(gif|png|jpeg|bmp|wbmp)$/i','',$target_file);
            FileHandler::makeDir($path);

            // 원본 이미지의 크기를 조절해서 임시 이미지에 넣음
            $new_width = (int)($width * $per);
            $new_height = (int)($height * $per);

            $x = (int)($resize_width/2 - $new_width/2);
            $y = (int)($resize_height/2 - $new_height/2);

            if($source) {
                if(function_exists('imagecopyresampled')) @imagecopyresampled($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
                else @imagecopyresized($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
            }

            // 파일을 쓰고 끝냄
            switch($target_type) {
                case 'gif' :
                        @imagegif($thumb, $target_file, 100);
                    break;
                case 'jpeg' :
                case 'jpg' :
                        @imagejpeg($thumb, $target_file, 100);
                    break;
                case 'png' :
                        @imagepng($thumb, $target_file, 9);
                    break;
                case 'wbmp' :
                case 'bmp' :
                        @imagewbmp($thumb, $target_file, 100);
                    break;
            }
            @chmod($target_file, 0644);
        }
    }
?>
