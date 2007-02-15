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
            if(filesize($file_name)<1) return;
            $fp = fopen($file_name, "r");
            $buff = fread($fp, filesize($file_name));
            fclose($fp);
            return trim($buff);
        }

        /**
         * @brief $buff의 내용을 파일에 쓰기
         **/
        function writeFile($file_name, $buff, $mode = "w") {
            $mode = strtolower($mode);
            if($mode != "a") $mode = "w";
            if(@!$fp = fopen($file_name,$mode)) return;
            fwrite($fp, $buff);
            fclose($fp);
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
    }
?>
