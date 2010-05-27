<?php
    /**
    * @class Optimizer 
    * @author zero (zero@nzeo.com)
    * @brief  class designed to be used to merge mutiple JS/CSS files into one file to shorten time taken for transmission.
    *
    **/

    class Optimizer {

        var $cache_path = "./files/cache/optimized/";
		var $script_file = "./common/script.php?l=%s&amp;t=.%s";

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

            // 관리자 설정시 설정이 되어 있지 않으면 패스
            // 캐시 디렉토리가 없으면 실행하지 않음
            $db_info = Context::getDBInfo();
            if($db_info->use_optimizer == 'N' || !is_dir($this->cache_path)) return $this->_getOptimizedRemoved($source_files);

            if(!count($source_files)) return;
			
            $files = array();
			$hash = "";
            foreach($source_files as $key => $file) {
				if($file['file'][0] == '/'){
					if(!file_exists($file['file'])){
						if(file_exists($_SERVER['DOCUMENT_ROOT'] . $file['file'])){
                            if($file['optimized']) $source_files[$key]['file'] = $file['file'] = $_SERVER['DOCUMENT_ROOT'].$file['file'];
						}else{
							continue;
						}
					}
				} else if(!$file || !$file['file'] || !file_exists($file['file'])) continue;
				$file['file'] = $source_files[$key]['file'] = str_replace("\\","/",$file['file']);
				if(empty($file['optimized']) || preg_match('/^https?:\/\//i', $file['file']) ) $files[] = $file;
				else{
					$targets[] = $file;
					$hash .= $file['file']; 
				}
			}
            if(!count($targets)) return $this->_getOptimizedRemoved($files);
			$list_file_hash = md5($hash);
			$oCacheHandler = &CacheHandler::getInstance('template');
			if($oCacheHandler->isSupport()){
				if(!$oCacheHandler->isValid($list_file_hash)){
					$buff = array();
					foreach($targets as $file) $buff[] = $file['file'];
					$oCacheHandler->put($list_file_hash, $buff);
				}
			}else{
				$list_file = FileHandler::getRealPath($this->cache_path . $list_file_hash . '.info.php');

				if(!file_exists($list_file)){
					$str = '<?php $f=array();';
					foreach($targets as $file) $str .= '$f[]="'. $file['file'] . '";';
					$str .= ' return $f; ?>';

					FileHandler::writeFile($list_file, $str);
				}
			}

            array_unshift($files, array('file' => sprintf($this->script_file, $list_file_hash, $type) , 'media' => 'all'));
            $files = $this->_getOptimizedRemoved($files);
            if(!count($files)) return $files;

            $url_info = parse_url(Context::getRequestUri());
            $abpath = $url_info['path'];
            foreach($files as $key => $val) {
                $file = $val['file'];

                if($file{0} == '/' || strpos($file,'://')!==false) continue;
                if(substr($file,0,2)=='./') $file = substr($file,2);
                $file = $abpath.$file;
                while(strpos($file,'/../')!==false) {
                    $file = preg_replace('/\/([^\/]+)\/\.\.\//','/',$file);
                }
                $files[$key]['file'] = $file;
            }

            return $files;
        }
    }
?>
