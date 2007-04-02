<?php
    /**
     * @class SessionHandler
     * @author zero (zero@nzeo.com)
     * @brief 세션 핸들링 클래스
     **/

    class SessionHandler extends Handler {

        var $lifetime = 3600;
        var $ipaddress = '';
        var $session_path = './files/session/';

        function open($path, $session_name) {
            return true;
        }

        function close() {
            return true;
        }

        function getSessionFile($session_key) {
            $session_file = sprintf('%szbxe_session_%s.php', $this->session_path, $session_key);
            return $session_file;
        }

        function read($session_key) {
            $filename = $this->getSessionFile($session_key);
            if(!file_exists($filename)) return '';
            $buff = FileHandler::readFile($filename);
            return substr($buff, strlen('<?php if(!__ZBXE__) exit(); ?>'));
        }

        function write($session_key, $val) {
            if(!$val) return true;

            $filename = $this->getSessionFile($session_key);

            $buff = '<?php if(!__ZBXE__) exit(); ?>'.$val;
            return FileHandler::writeFile($filename, $buff);
        }

        function destroy($session_key) {
            $filename = $this->getSessionFile($session_key);
            @unlink( $filename);
            return true;
        }

        function gc($maxlifetime) {
            foreach (glob($this->session_path.'zbxe_session_*.php') as $filename) {
                if (filemtime($filename) + $this->lifetime < time()) @unlink($filename);
            }
            return true;
        }
    }
?>
