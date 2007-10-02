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
         * @brief optimize 대상 파일을 받아서 처리 후 optimize 된 파일이름을 return
         **/
        function getOptimizedFiles($source_files, $type = "js") {
            $file_count = count($source_files);
            for($i=0;$i<$file_count;$i++) {
                $file = trim($source_files[$i]);
                if(!$file) continue;
                if(eregi("^http:\/\/",$file) || $file == './common/css/button.css') $files[] = $file;
                else $targets[] = $file;
            }

            if(!count($targets)) return $files;

            $hashed_filename = $this->getHashFilename($targets);

            $filename = sprintf("%s%s.%s.php", $this->cache_path, $hashed_filename, $type);

            $this->doOptimizedFile($filename, $targets, $type);

            $files[] = $filename;

            return $files;

        }

        /**
         * @brief optimize는 대상 파일을 \n로 연결후 md5 hashing하여 파일이름의 중복을 피함
         **/
        function getHashFilename($files) {
            $buff = implode("\n", $files);
            return md5($buff);
        }

        /**
         * @brief 이미 저장된 캐시 파일과의 시간등을 검사하여 새로 캐싱해야 할지를 체크
         **/
        function doOptimizedFile($filename, $targets, $type) {
            if(!file_exists($filename)) return $this->makeOptimizedFile($filename, $targets, $type);

            $file_count = count($targets);

            $mtime = filemtime($filename);
            for($i=0;$i<$file_count;$i++) {
                if($mtime < filemtime($targets[$i])) return $this->makeOptimizedFile($filename, $targets, $type);
            }
        }

        /**
         * @brief css나 js파일을 묶어서 하나의 파일로 만들고 gzip 압축이나 헤더등을 통제하기 위해서 php파일을 별도로 만들어서 진행함
         **/
        function makeOptimizedFile($filename, $targets, $type) {
            /**
             * 실제 css나 js의 내용을 합친 것을 구함
             **/
            // 대상 파일의 내용을 구해오고 css 파일일 경우 url()내의 경로를 변경
            $file_count = count($targets);
            for($i=0;$i<$file_count;$i++) {
                $file = $targets[$i];
                $str = FileHandler::readFile($file);

                // css 일경우 background:url() 변경
                if($type == "css") $str = $this->replaceCssPath($file, $str);

                $content_buff .= $str."\r\n";
            }
            if(Context::isGzEnabled()) $content_buff = ob_gzhandler($content_buff, 5);

            $content_file = eregi_replace("\.php$","",$filename);
            $content_filename = str_replace($this->cache_path, '', $content_file);

            FileHandler::writeFile($content_file, $content_buff);

            /**
             * 압축을 지원하고 캐시 타임을 제대로 이용하기 위한 헤더 파일 구함
             **/
            // php의 헤더파일 생성
            $modified_time = gmdate("D, d M Y H:i:s");

            // gzip 압축 체크
            if(Context::isGzEnabled()) $gzip_header =  'header("Content-Encoding: gzip");';

            // 확장자별 content-type 체크
            if($type == 'css') $content_type = 'text/css';
            elseif($type == 'js') $content_type = 'text/javascript';

            $header_buff = <<<EndOfBuff
<?php
header("Content-Type: {$content_type}; charset=UTF-8");
header("Last-Modified: {$modified_time} GMT");
{$gzip_header}
if(@file_exists("{$content_filename}")) {
    @fpassthru(fopen("{$content_filename}", "rb"));
}
exit();
?>
EndOfBuff;

            FileHandler::writeFile($filename, $header_buff);
        }

        /**
         * @brief css의 경우 import/ background 등의 속성에서 사용되는 url내의 경로를 변경시켜줌
         **/
        function replaceCssPath($file, $str) {
            $this->tmp_css_path = Context::getRequestUri().ereg_replace("^\.\/","",dirname($file))."/";
            $str = preg_replace_callback('!url\(("|\'){0,1}([^\)]+)("|\'){0,1}\)!is', array($this, '_replaceCssPath'), $str);

            $str = preg_replace('!\/([^\/]*)\/\.\.\/!is','/', $str);

            return $str;
        }

        function _replaceCssPath($matches) {
            if(eregi("^http",$matches[2])) return $matches[0];
            if(eregi("^\.\/common\/",$matches[2])) return $matches[0];
            return sprintf('url(%s)', $this->tmp_css_path.$matches[2]);
        }

    }
?>
