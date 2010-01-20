<?php
    /**
    * @class Optimizer 
    * @author zero (zero@nzeo.com)
    * @brief  class designed to be used to merge mutiple JS/CSS files into one file to shorten time taken for transmission.
    *
    **/

    class Optimizer {

        var $cache_path = "./files/cache/optimized/";

        /**
         * @brief Constructor which check if a directory, 'optimized' exists in designated path. If not create a new one
         **/
        function Optimizer() {
            if(!is_dir($this->cache_path)) {
                FileHandler::makeDir($this->cache_path);
            }
        }

        /**
         * @brief file that removes 'optimized' in a given array
         * @param[in] $files an array to be modified
        **/
        function _getOptimizedRemoved($files) {
            foreach($files as $key => $val) unset($files[$key]['optimized']);
            return $files;
        }

        /**
         * @brief method that optimizes a given file and returns a resultant file
         * @param[in] source_files an array of source files to be optimized
         * @param[in] type a type of source file, either js or css.
         * @return Returns a optimized file
         **/
        function getOptimizedFiles($source_files, $type = "js") {
            if(!is_array($source_files) || !count($source_files)) return;

            // $source_files의 역슬래쉬 경로를 슬래쉬로 변경 (윈도우즈 대비)
            foreach($source_files as $key => $file){
                $source_files[$key]['file'] = str_replace("\\","/",$file['file']);
            }

            // 관리자 설정시 설정이 되어 있지 않으면 패스
            $db_info = Context::getDBInfo();
            if($db_info->use_optimizer == 'N') return $this->_getOptimizedRemoved($source_files);

            // 캐시 디렉토리가 없으면 실행하지 않음
            if(!is_dir($this->cache_path)) return $this->_getOptimizedRemoved($source_files);

            $files = array();

            if(!count($source_files)) return;
            foreach($source_files as $file) {
                if(!$file || !$file['file']) continue;
                if(empty($file['optimized']) || preg_match('/^https?:\/\//i', $file['file']) ) $files[] = $file;
                else $targets[] = $file;
            }

            if(!count($targets)) return $this->_getOptimizedRemoved($files);

            $optimized_info = $this->getOptimizedInfo($targets);

            $path = sprintf("%s%s", $this->cache_path, $optimized_info[0]);
            $filename = sprintf("%s.%s.%s.php", $optimized_info[0], $optimized_info[1], $type);

            $this->doOptimizedFile($path, $filename, $targets, $type);

            array_unshift($files, array('file' => $path.'/'.$filename, 'media' => 'all'));

            $files = $this->_getOptimizedRemoved($files);
            if(!count($files)) return $files;

            $url_info = parse_url(Context::getRequestUri());
            $abpath = $url_info['path'];

            foreach($files as $key => $val) {
                $file = $val['file'];

                if(substr($file,0,1)=='/' || strpos($file,'://')!==false) continue;
                if(substr($file,0,2)=='./') $file = substr($file,2);
                $file = $abpath.$file;
                while(strpos($file,'/../')!==false) {
                    $file = preg_replace('/\/([^\/]+)\/\.\.\//','/',$file);
                }
                $files[$key]['file'] = $file;
            }
            return $files;
        }

        /**
         * @brief retrive a list of files from a given parameter
         * @param[in] files a list containing files
        **/
        function _getOnlyFileList($files) {
            foreach($files as $key => $val) $files[$key] = $val['file'];
            return $files;
        }

        /**
         * @brief method to generate a unique key from list of files along with a last modified date
         * @param[in] files an array containing file names
         **/
        function getOptimizedInfo($files) {
            // 개별 요소 파일이 갱신되었으면 새로 옵티마이징
            $count = count($files);
            $last_modified = 0;
            for($i=0;$i<$count;$i++) {
                $mtime = filemtime($files[$i]['file']);
                if($last_modified < $mtime) $last_modified = $mtime;
            }

            $buff = implode("\n", $this->_getOnlyFileList($files));

            return array(md5($buff), $last_modified);
        }

        /**
         * @brief method that check if a valid cache file exits for a given filename. If not create new one.   
         * @param[in] path directory path of the cache files
         * @param[in] filename a filename for cache files
         * @param[in] targets 
         * @param[in] type a type of cache file
         **/
        function doOptimizedFile($path, $filename, $targets, $type) {
            // 대상 파일이 있으면 그냥 패스~
            if(file_exists($path.'/'.$filename)) return;

            // 대상 파일이 없으면 hashed_filename으로 생성된 파일들을 모두 삭제
            FileHandler::removeFilesInDir($path);

            // 새로 캐시 파일을 생성
            $this->makeOptimizedFile($path, $filename, $targets, $type);
        }

        /**
         * @brief method produce a php code to merge css/js files, compress the resultant files and modified the HTML header accordingly.
         * @param[in] path
         * @param[in] filename a name of a resultant file
         * @param[in] targets list of files to be used for the operation
         * @param[in] type a type of file such as css or js
         * @return NONE    
         **/
        function makeOptimizedFile($path, $filename, $targets, $type) {
            /**
             * 실제 css나 js의 내용을 합친 것을 구함
             **/
            // 대상 파일의 내용을 구해오고 css 파일일 경우 url()내의 경로를 변경
            $content_filename = substr($filename, 0, -4);
            $file_object = FileHandler::openFile($path."/".$content_filename, "w");

            if($type == 'css') $file_object->write('@charset "UTF-8";'."\n");
            foreach($targets as $file) {
                $str = FileHandler::readFile($file['file']);

                $str = trim(Context::convertEncodingStr($str));

                // css 일경우 background:url() 변경 / media 적용
                if($type == 'css') {
                    $str = $this->replaceCssPath($file['file'], $str);
                    if($file['media'] != 'all') $str = '@media '.$file['media'].' {'."\n".$str."\n".'}';
                }
                $file_object->write($str);
                $file_object->write("\n");
                unset($str);
            }

            $file_object->close();

            /**
             * 캐시 타임을 제대로 이용하기 위한 헤더 파일 구함
             **/
            // 확장자별 content-type 체크
            if($type == 'css') $content_type = 'text/css';
            elseif($type == 'js') $content_type = 'text/javascript';

            // 캐시를 위한 처리 
            $unique = crc32($content_filename);
            $size = filesize($path.'/'.$content_file);
            $mtime = filemtime($path.'/'.$content_file);

            // js, css 파일을 php를 통해서 출력하고 이 출력시에 헤더값을 조작하여 캐싱과 압축전송이 되도록 함 (IE6는 CSS파일일 경우 gzip압축하지 않음)
            $header_buff = '<?php
$content_filename = "'.$content_filename.'";
$mtime = '.$mtime.';
$cached = false;
$type = "'.$type.'";

if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
    $time = strtotime(preg_replace("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"])); 
    if($mtime == $time) {
        header("HTTP/1.1 304"); 
        $cached = true;
    } 
}

if( preg_match("/MSIE 6.0/i",$_SERVER["HTTP_USER_AGENT"]) || strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")===false || !function_exists("ob_gzhandler") ) {
    $size = filesize($content_filename);
} else {
    $f = fopen($content_filename,"r");
    $buff = fread($f, filesize($content_filename));
    fclose($f);
    $buff = ob_gzhandler($buff, 5);
    $size = strlen($buff);
    header("Content-Encoding: gzip");
}

header("Content-Type: '.$content_type.'; charset=UTF-8");
header("Date: '.substr(gmdate('r'), 0, -5).'GMT");
header("Expires: '.substr(gmdate('r', strtotime('+1 MONTH')), 0, -5).'GMT");
header("Cache-Control: private, max-age=2592000"); 
header("Pragma: cache"); 
header("Last-Modified: '.substr(gmdate('r', $mtime), 0, -5).'GMT");
header("ETag: \"'.dechex($unique).'-".dechex($size)."-'.dechex($mtime).'\""); 

if(!$cached) {
    if(empty($buff)) {
        $f = fopen($content_filename,"r");
        fpassthru($f);
    } else print $buff;
}
?>';
            FileHandler::writeFile($path.'/'.$filename, $header_buff);
        }

        /**
         * @brief method that modify a path for import or background element in a given css file
         * @param[in] file a file to be modified
         * @param[in] str a buffer to store resultant content
         * @return Returns resultant content
         **/
        function replaceCssPath($file, $str) {
            // css 파일의 위치를 구함
            $this->tmp_css_path = preg_replace("/^\.\//is","",dirname($file))."/";

            // url() 로 되어 있는 css 파일의 경로를 변경
            $str = preg_replace_callback('/url\(([^\)]*)\)/is', array($this, '_replaceCssPath'), $str);

            // charset 지정 문구를 제거
            $str = preg_replace('!@charset([^;]*?);!is','',$str);

            return $str;
        }

        
        /**
         * @brief callback method that is responsible for replacing strings in css file with predefined ones.
         * @param[in] matches a list of strings to be examined and modified if necessary
         * @return Returns resultant content
         **/
        function _replaceCssPath($matches) {
            static $abpath = null;
            if(is_null($abpath)) {
                $url_info = parse_url(Context::getRequestUri());
                $abpath = $url_info['path'];
            }
            $path = str_replace(array('"',"'"),'',$matches[1]);
            if(substr($path,0,1)=='/' || strpos($path,'://')!==false || strpos($path,'.htc')!==false) return 'url("'.$path.'")';
            if(substr($path,0,2)=='./') $path = substr($path,2);
            $target = $abpath.$this->tmp_css_path.$path;
            while(strpos($target,'/../')!==false) {
                $target = preg_replace('/\/([^\/]+)\/\.\.\//','/',$target);
            }

            return 'url("'.$target.'")';
        }
    }
?>
