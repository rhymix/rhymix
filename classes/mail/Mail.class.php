<?php
    /**
     * @brief 메일 발송
     * @author zero (zero@zeroboard.com)
     **/

    class Mail {

        var $sender_name = '';
        var $sender_email = '';
        var $receiptor_name = '';
        var $receiptor_email = '';
        var $title = '';
        var $content = '';
        var $content_type = 'html';

        function Mail() { }

        function setSender($name, $email) {
            $this->sender_name = $name;
            $this->sender_email = $email;
        }

        function getSender() {
            if($this->sender_name) return sprintf("%s <%s>", '=?utf-8?b?'.base64_encode($this->sender_name).'?=', $this->sender_email);
            return $this->sender_email;
        }

        function setReceiptor($name, $email) {
            $this->receiptor_name = $name;
            $this->receiptor_email = $email;
        }

        function getReceiptor() {
            if($this->receiptor_name) return sprintf("%s <%s>", '=?utf-8?b?'.base64_encode($this->receiptor_name).'?=', $this->receiptor_email);
            return $this->receiptor_email;
        }

        function setTitle($title) {
            $this->title = $title;
        }

        function getTitle() {
            return '=?utf-8?b?'.base64_encode($this->title).'?=';
        }

        function setContent($content) {
            $this->content = $content;
        }

        function getPlainContent() {
            return chunk_split(base64_encode(str_replace(array("<",">","&"), array("&lt;","&gt;","&amp;"), $this->content)));
        }

        function getHTMLContent() {
            return chunk_split(base64_encode($this->content_type=='html'?nl2br($this->content):$this->content));
        }

        function setContentType($mode = 'html') {
            $this->content_type = $mode=='html'?'html':'';
        }

        function send() {
            $boundary = '----=='.uniqid(rand(),true);

            $headers = sprintf(
                "From: %s\r\n".
                "MIME-Version: 1.0\r\n".
                "Content-Type: multipart/alternative;\r\n\tboundary=\"%s\"\r\n\r\n".
                "",
                $this->getSender(),
                $boundary
            );

            $body = sprintf(
                "--%s\r\n".
                "Content-Type: text/plain; charset=utf-8; format=flowed\r\n".
                "Content-Transfer-Encoding: base64\r\n".
                "Content-Disposition: inline\r\n\r\n".
                "%s".
                "--%s\r\n".
                "Content-Type: text/html; charset=utf-8\r\n".
                "Content-Transfer-Encoding: base64\r\n".
                "Content-Disposition: inline\r\n\r\n".
                "%s".
                "--%s--".
                "",
                $boundary,
                $this->getPlainContent(),
                $boundary,
                $this->getHTMLContent(),
                $boundary
            );

            return mail($this->getReceiptor(), $this->getTitle(), $body, $headers);
        }

        function checkMailMX($email_address) {
            if(!Mail::isVaildMailAddress($email_address)) return false;
            list($user, $host) = explode("@", $email_address);
            if(function_exists('checkdnsrr')) {
                if (checkdnsrr($host, "MX") or checkdnsrr($host, "A")) return true;     
                else return false;
            }
            return true;
        }

        function isVaildMailAddress($email_address) {
            if( eregi("([a-z0-9\_\-\.]+)@([a-z0-9\_\-\.]+)", $email_address) ) return $email_address;
            else return ''; 
        }
    }
?>
