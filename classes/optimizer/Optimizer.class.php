<?php
    /**
    * @class Optimizer 
    * @author zero (zero@nzeo.com)
    * @brief  JS/CSS파일등을 특정한 규칙에 맞게 하나의 파일로 만들어서 client에서 가져갈 수 있도록 성능향상을 지원하는 클래스
    *
    * 일단 내부적인 코드가 아무리 튜닝이 되어도 모듈/애드온/위젯/에디터컴포넌트등 각 요소들의 JS/CSs파일들을 잘라서 호출하는 구조이기에
    * 사용자의 브라우저에서는 최소 10이상의 파일을 별도로 서버에 요청을 하게 된다.
    * 이를 방지하기 위해서 서버내의 로컬 파일일 경우 하나로 묶어서 클라이언트에서 가져갈 수 있도록 하여 그 효과를 증대시킴.
    **/

    class Optimizer {

        var $cache_path = "./files/cache/optimized/";

        /**
         * @brief optimizer에서 캐싱파일을 저장할 곳을 찾아서 없으면 만듬
        **/
        function Optimizer() {
            if(!is_dir($this->cache_path)) {
                FileHandler::makeDir($this->cache_path);
            }
        }

        /**
         * @brief 파일 목록 배열에서 optimized 첨자를 제거한 후 return
        **/
        function _getOptimizedRemoved($files) {
            foreach($files as $key => $val) unset($files[$key]['optimized']);
            return $files;
        }

        /**
         * @brief optimize 대상 파일을 받아서 처리 후 optimize 된 파일이름을 return
         **/
        function getOptimizedFiles($source_files, $type = "js") {
            if(!is_array($source_files) || !count($source_files)) return;

            // $source_files의 역슬래쉬 경로를 슬래쉬로 변경 (윈도우즈 대비)
            foreach($source_files as $key => $file) $source_files[$key]['file'] = str_replace("\\","/",$file['file']);

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

            return $this->_getOptimizedRemoved($files);
        }

        /**
         * @brief 파일 목록 배열에서 file을 제외한 나머지 첨자를 제거하여 return
        **/
        function _getOnlyFileList($files) {
            foreach($files as $key => $val) $files[$key] = $val['file'];
            return $files;
        }

        /**
         * @brief optimize는 대상 파일을 \n로 연결후 md5 hashing하여 파일이름의 중복을 피함
         * 개별 파일과 optimizer 클래스 파일의 변경을 적용하기 위해 각 파일들과 Optimizer.class.php의 filemtime을 비교, 파일이름에 반영
         **/
        function getOptimizedInfo($files) {
            // 개별 요소들 또는 Optimizer.class.php파일이 갱신되었으면 새로 옵티마이징
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
         * @brief 이미 저장된 캐시 파일과의 시간등을 검사하여 새로 캐싱해야 할지를 체크
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
         * @brief css나 js파일을 묶어서 하나의 파일로 만들고 gzip 압축이나 헤더등을 통제하기 위해서 php파일을 별도로 만들어서 진행함
         **/
        function makeOptimizedFile($path, $filename, $targets, $type) {
            /**
             * 실제 css나 js의 내용을 합친 것을 구함
             **/
            // 대상 파일의 내용을 구해오고 css 파일일 경우 url()내의 경로를 변경
            foreach($targets as $file) {
                $str = FileHandler::readFile($file['file']);

                $str = Context::convertEncodingStr($str);

                // css 일경우 background:url() 변경 / media 적용
                if($type == 'css') {
                    $str = $this->replaceCssPath($file['file'], $str);
                    if($file['media'] != 'all') $str = '@media '.$file['media'].' {'."\n".$str."\n".'}';
                }

                $content_buff .= $str."\n";
            }
            if($type == 'css') $content_buff = '@charset "UTF-8";'."\n".$content_buff;

            $content_filename = substr($filename, 0, -4);
            FileHandler::writeFile($path.'/'.$content_filename, $content_buff);

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
         * @brief css의 경우 import/ background 등의 속성에서 사용되는 url내의 경로를 변경시켜줌
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

        function _replaceCssPath($matches) {
            $path = str_replace(array('"',"'"),'',$matches[1]);
            if(preg_match('/^http|^\//i', $path) || preg_match('/\.htc$/i',$path) ) return $matches[0];

            return 'url("../../../../'.$this->tmp_css_path.$path.'")';
        }

    }
?>
