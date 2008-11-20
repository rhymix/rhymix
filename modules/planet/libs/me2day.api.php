<?php
    class me2api {
        var $user_id = null;
        var $user_key = null;

        var $application_key = '537a368d9049d9e86b2b169d75a2a4c3';

        var $api_host = 'me2day.net';
        var $api_port = 80;

        function me2api($user_id, $user_key) {
            $this->user_id = $user_id;
            $this->user_key = $user_key;
        }

        function _getNonce() {
            for($i=0;$i<8;$i++) $nonce .= dechex(rand(0, 15));
            return $nonce;
        }

        function _getAuthKey() {
            $nonce = $this->_getNonce();
            return $nonce.md5($nonce.$this->user_key);
        }

        function _getPath($method, $user_id = null) {
            if(!$user_id) return sprintf('/api/%s.xml', $method);
            return sprintf('/api/%s/%s.xml',$method, $user_id);
        }

        function _getContent($method, $user_id = null, $params = null) {
            $host = $this->api_host;
            $path = $this->_getPath($method, $user_id);
            $port = $this->api_port;
            $auth = base64_encode($this->user_id.':'.$this->_getAuthKey());

            $arr_content = array();
            if(is_array($params) && count($params)) {
                foreach($params as $key => $val) {
                    $arr_content[] = sprintf('%s=%s', $key, urlencode($val)); 
                }
                $content = implode('&',$arr_content);
            }
            $header = sprintf(
                    "POST %s HTTP/1.0\r\n".
                    "Host: %s\r\n".
                    "me2_application_key: %s\r\n".
                    "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Authorization: Basic %s\r\n".
                    "Content-Length: %d\r\n\r\n",
                    $path,
                    $host,
                    $this->application_key,
                    $auth,
                    strlen($content)
            );
            if($content) $header.=$content."\r\n\r\n";

            $fp = fsockopen($host, $port);
            if(!$fp) return null;

            fwrite($fp, $header);
            $started = false;
            while(!feof($fp)) {
                $str = fgets($fp, 1024);
                if(!trim($str)) $started = true;
                if($started) $buff .= $str;
            }
            fclose($fp);
            return trim($buff);
        }

        function chkNoop() {
            $buff = $this->_getContent('noop');
            if(strpos($buff, '<code>0</code>')!==false) return new Object();
            return new Object(-1, $buff);
        }

        function doPost($body, $tags, $content_type = 'document') {
            $params = array('post[body]'=>$body, 'post[tags]'=>str_replace(',',' ',$tags), 'content_type'=>$content_type);
            $buff = $this->_getContent('create_post',$this->user_id,$params);
            if(strpos($buff, '<code>0</code>')!==false) return new Object();
            return new Object(-1,$buff);
        }
    }
?>
