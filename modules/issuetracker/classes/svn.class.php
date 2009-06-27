<?php
    /**
     * @class   svn
     * @author  zero <zero@zeroboard.com>
     * @brief   svn source browser class
     **/
    class Svn {

        var $url = null;

        var $svn_cmd = null;

        var $tmp_dir = '/tmp';

        var $oXml = null;
        var $userid = null;
        var $passwd = null;

        function Svn($url, $svn_cmd='/usr/bin/svn', $userid=null, $passwd=null) {
            if(substr($url,-1)!='/') $url .= '/';
            $this->url = $url;

            if(strstr($svn_cmd, " ") != FALSE) $this->svn_cmd = '"'.$svn_cmd.'"' ;
            else $this->svn_cmd = $svn_cmd;

            $this->tmp_dir = _XE_PATH_.'files/cache/tmp';
            if(!is_dir($this->tmp_dir)) FileHandler::makeDir($this->tmp_dir);

            $this->userid = $userid;
            $this->passwd = $passwd;

            $this->oXml = new XmlParser();
        }

        function _getAuthInfo()
        {
            if($this->userid && $this->passwd)
            {
                return sprintf("--username %s --password %s", $this->userid, $this->passwd);
            }
            else 
            {
                return '';
            }
        }

        function getStatus($path = '/') {
            if(substr($path,0,1)=='/') $path = substr($path,1);
            if(strpos($path,'..')!==false) return;

            $command = sprintf("%s --non-interactive --config-dir %s log --xml --limit 1 %s %s%s", $this->svn_cmd, $this->tmp_dir, $this->_getAuthInfo(), $this->url, $path);
            $buff = $this->execCmd($command, $error);
            $xmlDoc = $this->oXml->parse($buff);

            $date = $xmlDoc->log->logentry->date->body;

            $output->revision = $xmlDoc->log->logentry->attrs->revision;
            $output->author = $xmlDoc->log->logentry->author->body;
            $output->msg = $this->linkXE($xmlDoc->log->logentry->msg->body);
            $output->date = $this->getDateStr('Y-m-d H:i:s', $date);
            $output->gap = $this->getTimeGap($date);

            return $output;
        }

        function getList($path, $revs = null) {
            if(substr($path,0,1)=='/') $path = substr($path,1);
            if(strpos($path,'..')!==false) return;

            $command = sprintf(
                '%s --non-interactive %s --config-dir %s list %s%s%s',
                $this->svn_cmd,
                $this->_getAuthInfo(),
                $this->tmp_dir,
                $this->url,
                $path,
                $revs?'@'.(int)$revs:null
            );

            $buff = $this->execCmd($command, $error);
            $list = explode(PHP_EOL,$buff);

            if(!count($list)) return null;

            $file_list = $directory_list = $output = array();

            foreach($list as $name) {
                if(!$name) continue;
                $obj = null;
                $obj->name = $name;
                $obj->path = $path.$name;

                $logs = $this->getLog($obj->path, $revs, null, false, 1);
                $obj->revision = $logs[0]->revision;
                $obj->author = $logs[0]->author;
                $obj->date = $this->getDateStr("Y-m-d H:i",$logs[0]->date);
                $obj->gap = $this->getTimeGap($logs[0]->date);
                $obj->msg = $this->linkXE($logs[0]->msg);

                if(substr($obj->path,-1)=='/') $obj->type = 'directory';
                else $obj->type = 'file';

                if($obj->type == 'file') $file_list[] = $obj;
                else $directory_list[] = $obj;
            }
            return array_merge($directory_list, $file_list);
        }

        function getFileContent($path, $revs = null) {
            if(strpos($path,'..')!==false) return;

            $command = sprintf(
                '%s --non-interactive %s --config-dir %s cat %s%s%s',
                $this->svn_cmd,
                $this->_getAuthInfo(),
                $this->tmp_dir,
                $this->url,
                $path,
                $revs?'@'.$revs:null
            );

            $content = $this->execCmd($command, $error);

            $log = $this->getLog($path, $revs, null, false, 1);

            $output->revision = $log[0]->revision;
            $output->author = $log[0]->author;
            $output->date = $log[0]->date;
            $output->msg = $this->linkXE($log[0]->msg);
            $output->content = $content;

            return $output;
        }

        function parseComp(&$list)
        {
            $cnt = count($list);
            $output = array();
            $obj = null;
            $idx = 0;
            for($i=0;$i<$cnt;$i++) {
                $str = $list[$i];
                $str = rtrim($str);
                if(preg_match('/^Index: (.*)$/', $str, $m)) {
                    if($blockobj != null) 
                    {
                        $obj->blocks[$blockobj->before_line_start] = $blockobj;
                        ksort($obj->blocks);
                    }
                    if($obj!==null) $output[] = $obj;
                    $obj = null;
                    $obj->filename = $m[1];
                    $idx = 0;
                    $obj->blocks = array();
                    continue;
                }
                if(preg_match('/^(\=+)$/',$str)) continue;
                if(preg_match('/^--- ([^\(]+)\(revision ([0-9]+)\)$/i',$str,$m)) {
                    $obj->before_revision = $m[2];
                    continue;
                }
                if(preg_match('/^\+\+\+ ([^\(]+)\(revision ([0-9]+)\)$/i',$str,$m)) {
                    $obj->after_revision = $m[2];
                    continue;
                }
                if(preg_match('/^@@ \-([0-9]+),([0-9]+) \+([0-9]+),([0-9]+) @@$/', $str, $m)) {
                    if($blockobj != null) $obj->blocks[$blockobj->before_line_start] = $blockobj;
                    $blockobj = null;
                    $blockobj->before_line_start = (int) $m[1];
                    $blockobj->after_line_start = (int) $m[3];
                    $cur_before_line = $blockobj->before_line_start;
                    $cur_after_line = $blockobj->after_line_start;
                    $blockobj->lines = array();
                    continue;
                }
                $line = null; 
                if(preg_match('/^\-(.*)$/i',$str)) {
                    $line->data = ' '.substr($str,1);
                    $line->type = "deleted";
                    $line->before_line_number = $cur_before_line ++;
                }
                else if(preg_match('/^\+(.*)$/i',$str)) {
                    $line->data = ' '.substr($str,1);
                    $line->type = "added";
                    $line->after_line_number = $cur_after_line ++;
                }
                else
                {
                    $line->data = $str;
                    $line->before_line_number = $cur_before_line ++;
                    $line->after_line_number = $cur_after_line ++;
                }
                $blockobj->lines[] = $line;
            }
            if($obj!==null) 
            {
                if($blockobj != null) $obj->blocks[$blockobj->before_line_start] = $blockobj;
                ksort($obj->blocks);
                $output[] = $obj;
            }
            return $output;
            
        }

        function getComp($path, $brev, $erev) {
            if(!$brev) {
                $command = sprintf('%s --non-interactive %s --config-dir %s log --xml --limit 2 %s%s@%d', $this->svn_cmd, $this->_getAuthInfo(), $this->tmp_dir, $this->url, $path, $erev);
                $buff = $this->execCmd($command, $error);
                $xmlDoc = $this->oXml->parse($buff);
                if(is_array($xmlDoc->log->logentry)) $brev = $xmlDoc->log->logentry[1]->attrs->revision;
                if(!$brev) return;
            }

            $command = sprintf('%s --non-interactive %s --config-dir %s diff %s%s@%d %s%s@%d',
                    $this->svn_cmd,
                    $this->_getAuthInfo(),
                    $this->tmp_dir,
                    $this->url,
                    $path,
                    $brev,
                    $this->url,
                    $path,
                    $erev
            );
            $output = $this->execCmd($command, $error);
            $list = explode("\n",$output);
            $output = $this->parseComp($list);

            return $output;
        }

        function getLog($path, $erev=null, $brev=null, $quiet = false, $limit = 2, $link = true) {
            if(strpos($path,'..')!==false) return;

            $command = sprintf(
                '%s --non-interactive %s --config-dir %s log --xml %s %s %s %s%s',
                $this->svn_cmd,
                $this->_getAuthInfo(),
                $this->tmp_dir,
                $quiet?'--quiet':'--verbose',
                $limit?'--limit '.$limit:'',
                $erev>0?(sprintf('-r%d:%d',(int)$erev, (int)$brev)):'',
                $this->url,
                $path
            );

            $output = $this->execCmd($command, $error);
            
            $xmlDoc = $this->oXml->parse($output);
            $items = $xmlDoc->log->logentry;
            if(!$items) return null;

            $output = null;
            if(!is_array($items)) $items = array($items);
            foreach($items as $tmp) {
                $obj = null;
                $date = $tmp->date->body;

                $obj->revision = $tmp->attrs->revision;
                $obj->author = $tmp->author->body;
                $obj->date = $this->getDateStr("Y-m-d H:i",$date);
                $obj->gap = $this->getTimeGap($date);

                $paths = $tmp->paths->path;
                if(!is_array($paths)) $paths = array($paths);
                foreach($paths as $key => $val) {
                    $tmp_obj = null;
                    $tmp_obj->action = $val->attrs->action;
                    $tmp_obj->copyfrom_path = $val->attrs->{"copyfrom-path"};
                    $tmp_obj->copyfrom_rev = $val->attrs->{"copyfrom-rev"};
                    $tmp_obj->path = $val->body;
                    $obj->paths[] = $tmp_obj;
                }

                $obj->msg = $link?$this->linkXE($tmp->msg->body):$tmp->msg->body;
                $output[] = $obj;
            }
            return $output;
        }


        function getPath($path) {
            $buff = pathinfo($path);
            return $buff['dirname'];
        }

        function execCmd($command, &$error) {
            $err = false;

            $descriptorspec = array ( 
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            );

            $fp = proc_open($command, $descriptorspec, $pipes);

            if (!is_resource($fp)) return;

            $handle = $pipes[1];
            $output = '';
            while (!feof($handle)) {
                $buff = fgets($handle,1024);
                $output .= $buff;
            }       

            $error = '';
            while (!feof($pipes[2])) {                       
                $error .= fgets($pipes[2], 1024);
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($fp);

            return $output;
        }

        function getParentPath($path) {
            $parent_path = null;
            if($path) {
                $pathinfo = pathinfo($path);
                $parent_path = $pathinfo['dirname'].'/';
            }
        }

        function explodePath($source_path, $is_file = false) {
            if(!$source_path) return;
            $source_path = rtrim($source_path);
            $arr_path = explode('/', $source_path);
            if(substr($source_path,-1)!='/') $file = array_pop($arr_path);

            $output = array('/'=>'');

            $path = null;
            foreach($arr_path as $p) {
                if(!trim($p)) continue;
                $path .= $p.'/';
                $output[$p] = $path;
            }

            if($file) $output[$file] = $source_path;
            return $output;
        }

        function getDateStr($format, $str) {
            if(strtotime($str)===-1 || strtotime($str)===false) {
                $type = substr($GLOBALS['_time_zone'],0,1);
                $hour = substr($GLOBALS['_time_zone'],1,2);
                $min = substr($GLOBALS['_time_zone'],3,2);
                $g = $hour*60*60 + $min * 60;
                $t = ztime(str_replace(array('-','T',':','.',' '),'',trim($str)))+$g;
                return date($format, $t);
            } else {
                return date($format, strtotime(trim($str)));
            }
        }

        function getTimeGap($str, $dayStr = 'day', $hourStr = 'hour', $minStr = 'minute') {
            $time = strtotime($str);

            $time_gap = time()-$time;

            if($time_gap < 60) return '1 '.$minStr;
            else if($time_gap < 60*60) return (int)($time_gap / 60).' '.$minStr;
            else if($time_gap < 60*60*24) {
                $hour = (int)($time_gap/(60*60));
                $time_gap -= $hour*60*60;
                $min = (int)($time_gap/60);
                return sprintf("%02d",$hour)." ".$hourStr." ".($mid?sprintf("%02d",$min)." ".$minStr:'');
            } else {
                $day = (int)($time_gap/(60*60*24));
                $time_gap -= $day*60*60*24;
                $hour = (int)($time_gap/(60*60));
                return $day." ".$dayStr." ".($hour?sprintf("%02d",$hour)." ".$hourStr:'');
            }
        }

        function linkXE($msg) {
            $msg = preg_replace_callback('/(.[0-9]+)/s',array($this, '_linkDocument'),$msg);
            return $msg;
        }

        function _linkDocument($matches) {
            $document_srl = $matches[1];
            if(in_array(substr($document_srl,0,1),array('r','#','/'))) return $matches[0];
            if(!$document_srl || !preg_match('/^([0-9]+)$/',$document_srl)) return $matches[0];

            return sprintf('<a href="%s" onclick="window.open(this.href);return false;">%d</a>',getUrl('','document_srl',$document_srl), $document_srl);
        }

    }

?>
