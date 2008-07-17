<?php
    /**
     * @class extract 
     * @author zero (zero@nzeo.com)
     * @brief  대용량의 xml파일을 특정 태그를 중심으로 개별 파일로 저장하는 클래스
     **/
    class extract {
        var $key = '';
        var $cache_path = './files/cache/tmp';
        var $cache_index_file = './files/cache/tmp';
        
        var $filename = null;
        var $startTag = '';
        var $endTag = '';
        var $itemStartTag = '';
        var $itemEndTag = '';

        var $fd = null;
        var $index_fd = null;

        var $isStarted = false;
        var $isFinished = true;

        var $buff = 0;

        var $index = 0;

        /**
         * @brief 생성자, 대상 파일이름과 시작-끝 태그명, 그리고 각 개별 아이템 태그명을 인자로 받음
         **/
        function set($filename, $startTag, $endTag, $itemTag, $itemEndTag) {
            $this->filename = $filename;

            $this->startTag = $startTag;
            if($endTag) $this->endTag = $endTag;
            $this->itemStartTag = $itemTag;
            $this->itemEndTag = $itemEndTag;

            $this->key = md5($filename);

            $this->cache_path = './files/cache/tmp/'.$this->key;
            $this->cache_index_file = $this->cache_path.'/index';

            if(!is_dir($this->cache_path)) FileHandler::makeDir($this->cache_path);

            return $this->openFile();
        }

        /**
         * @brief 지정된 파일의 지시자를 염
         **/
        function openFile() {
            FileHandler::removeFile($this->cache_index_file);
            $this->index_fd = fopen($this->cache_index_file,"a");

            // local 파일일 경우 
            if(!preg_match('/^http:/i',$this->filename)) {
                if(!file_exists($this->filename)) return new Object(-1,'msg_no_xml_file');
                $this->fd = fopen($this->filename,"r");

            // remote 파일일 경우
            } else {
                $url_info = parse_url($this->filename);
                if(!$url_info['port']) $url_info['port'] = 80;
                if(!$url_info['path']) $url_info['path'] = '/';

                $this->fd = @fsockopen($url_info['host'], $url_info['port']);
                if(!$this->fd) return new Object(-1,'msg_no_xml_file');

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

                $header = sprintf("GET %s?%s HTTP/1.0\r\nHost: %s\r\nReferer: %s://%s\r\nConnection: Close\r\n\r\n", $url_info['path'], $url_info['query'], $url_info['host'], $url_info['scheme'], $url_info['host']);
                @fwrite($this->fd, $header);
                $buff = '';
                while(!feof($this->fd)) {
                    $buff .= $str = fgets($this->fd, 1024);
                    if(!trim($str)) break;
                }
                if(preg_match('/404 Not Found/i',$buff)) return new Object(-1,'msg_no_xml_file');
            }

            if($this->startTag) {
                while(!feof($this->fd)) {
                    $str = fgets($this->fd, 1024);
                    $pos = strpos($str, $this->startTag);
                    if($pos !== false) {
                        $this->buff = substr($this->buff, $pos+strlen($this->startTag));
                        $this->isStarted = true;
                        $this->isFinished = false;
                        break;
                    }
                }
            } else {
                $this->isStarted = true;
                $this->isFinished = false;
            }

            return new Object();
        }
        
        function closeFile() {
            $this->isFinished = true;
            fclose($this->fd);
            fclose($this->index_fd);
        }

        function isFinished() {
            return $this->isFinished || !$this->fd || feof($this->fd);
        }

        function saveItems() {
            $this->index = 0;
            while(!$this->isFinished()) {
                $this->getItem();
            }
        }

        function mergeItems($filename) {
            $this->saveItems();

            $filename = sprintf('%s/%s', $this->cache_path, $filename);

            $index_fd = fopen($this->cache_index_file,"r");
            $fd = fopen($filename,'w');

            fwrite($fd, '<items>');
            while(!feof($index_fd)) {
                $target_file = trim(fgets($index_fd,1024));
                if(!file_exists($target_file)) continue;
                $buff = FileHandler::readFile($target_file);
                fwrite($fd, FileHandler::readFile($target_file));

                FileHandler::removeFile($target_file);
            }
            fwrite($fd, '</items>');
            fclose($fd);
        }

        function getItem() {
            if($this->isFinished()) return;

            while(!feof($this->fd)) {
                $startPos = strpos($this->buff, $this->itemStartTag);
                if($startPos !== false) {
                    $this->buff = substr($this->buff, $startPos);
                    break;
                } elseif($this->endTag) {
                    $endPos = strpos($this->buff, $this->endTag);
                    if($endPos !== false) {
                        $this->closeFile();
                        return;
                    }
                }
                $this->buff .= fgets($this->fd, 1024); 
            }

            $startPos = strpos($this->buff, $this->itemStartTag);
            if($startPos === false) {
                $this->closeFile();
                return;
            }

            $filename = sprintf('%s/%s.xml',$this->cache_path, $this->index++);
            fwrite($this->index_fd, $filename."\r\n");

            $fd = fopen($filename,'w');

            while(!feof($this->fd)) {
                $endPos = strpos($this->buff, $this->itemEndTag);
                if($endPos !== false) {
                    $endPos += strlen($this->itemEndTag);
                    $buff = substr($this->buff, 0, $endPos);
                    fwrite($fd, $this->_addTagCRTail($buff));
                    fclose($fd);
                    $this->buff = substr($this->buff, $endPos);
                    break;
                }

                fwrite($fd, $this->_addTagCRTail($this->buff));
                $this->buff = fgets($this->fd, 1024);
            }
        }

        function getTotalCount() {
            return $this->index;
        }

        function getKey() {
            return $this->key;
        }

        function _addTagCRTail($str) {
            $str = preg_replace('/<\/([^>]*)></i', "</$1>\r\n<", $str);
            return $str;
        }
    }
?>
