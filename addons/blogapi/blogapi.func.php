<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file ./addons/blogapi/blogapi.func.php
     * @author zero (zero@nzeo.com)
     * @brief blogapi구현을 위한 함수 모음집
     **/
    
    function getXmlRpcFailure($error, $message) {
        return 
            sprintf(
                "<methodResponse>\n<fault><value><struct>\n<member>\n<name>faultCode</name>\n<value><int>%d</int></value>\n</member>\n<member>\n<name>faultString</name>\n<value><string>%s</string></value>\n</member>\n</struct></value></fault>\n</methodResponse>\n",
                $error,
                htmlspecialchars($message)
            );
    }

    function getXmlRpcResponse($params) {
        $buff = '<?xml version="1.0" encoding="utf-8"?>'."\n<methodResponse><params>";
        $buff .= _getEncodedVal($params);
        $buff .= "</params>\n</methodResponse>\n";

        return $buff;
    }

    function _getEncodedVal($val) {
        if(is_int($val)) $buff = sprintf("<i4>%d</i4>\n", $val);
        elseif(is_double($val)) $buff = sprintf("<double>%f</double>\n", $val);
        elseif(is_bool($val)) $buff = sprintf("<boolean>%d</boolean>\n", $val?1:0);
        elseif(is_object($val)) {
            $values = get_object_vars($val);
            $val_count = count($values);
            $buff = "<struct>";
            foreach($values as $k => $v) {
               $buff .= sprintf("<member>\n<name>%s</name>\n<value>%s</value>\n</member>\n", htmlspecialchars($k), _getEncodedVal($v));
            }
            $buff .= "</struct>\n";
        } elseif(is_array($val)) {
            $val_count = count($val);
            $buff = "<array>\n<data>";
            for($i=0;$i<$val_count;$i++) {
                $buff .= sprintf("<value>%s</value>\n", _getEncodedVal($val[$i]));
            }
            $buff .= "</data>\n</array>";
        } else {
            $buff = sprintf("<string>%s</string>\n", $val);
        }
        return sprintf("<param>\n<value>%s</value>\n</param>", $buff);
    }
?>
