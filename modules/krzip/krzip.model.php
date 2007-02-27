<?php
    /**
     * @class  krzipModel
     * @author zero (zero@nzeo.com)
     * @brief  krzip 모듈의 model 클래스
     **/

    class krzipModel extends krzip {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 우편 번호 검색
         * 동이름을 입력받아서 지정된 서버에 우편번호 목록을 요청한다
         **/
        function getZipCodeList() {
            // 동네 이름을 받음
            $addr = trim(Context::get('addr'));
            if(!$addr) return new Object(-1,'msg_not_exists_addr');

            // 지정된 서버에 요청을 시도한다
            $query_string = sprintf($this->query,urlencode($addr));

            $fp = fsockopen($this->hostname, $this->port, $errno, $errstr);
            if(!$fp) return new Object(-1, 'msg_fail_to_socket_open');

            fputs($fp, "GET {$query_string} HTTP/1.0\r\n");
            fputs($fp, "Host: {$this->hostname}\r\n\r\n");

            $buff = '';
            while(!feof($fp)) {
                $str = fgets($fp, 1024);
                if(trim($str)=='') $start = true;
                if($start) $buff .= $str;
            }

            fclose($fp);

            $address_list = unserialize(base64_decode($buff));
            if(!$address_list) return new Object(-1, 'msg_no_result');

            $this->add('address_list', implode("\n",$address_list));
        }
    }
?>
