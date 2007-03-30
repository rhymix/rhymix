<?php
    /**
     * @class XmlParser 
     * @author zero (zero@nzeo.com)
     * @brief xmlrpc를 해석하여 object로 return 하는 simple xml parser
     * @version 0.1
     *
     * xml 데이터의 attribute중에 xml:lang="ko,en,ch,jp,..." 이 있을 경우 지정된 lang 값에 해당하는 것만 남기는 트릭이 적용됨.
     * 무슨 문제를 일으킬지는 현재 모르나 잘 동작하고 있음
     **/

    class XmlParser {

        var $oParser = NULL; ///< xml parser

        var $input = NULL; ///< input xml
        var $output = array(); ///< output object

        var $lang = "en"; ///< 기본 언어타입

        /**
         * @brief xml 파일을 로딩하여 parsing 처리 후 return
         **/
        function loadXmlFile($filename) {
            if(!file_exists($filename)) return;

            $buff = FileHandler::readFile($filename);

            $oXmlParser = new XmlParser();
            return $oXmlParser->parse($buff);
        }

        /**
         * @brief xml 파싱
         **/
        function parse($input = '') {
            // 디버그를 위한 컴파일 시작 시간 저장
            if(__DEBUG__) $start = getMicroTime();

            $this->lang = Context::getLangType();

            $this->input = $input?$input:$GLOBALS['HTTP_RAW_POST_DATA'];

            // 지원언어 종류를 뽑음
            preg_match_all("/xml:lang=\"([^\"].+)\"/i", $this->input, $matches);

            // xml:lang이 쓰였을 경우 지원하는 언어종류를 뽑음
            if(count($matches[1]) && $supported_lang = array_unique($matches[1])) {
                // supported_lang에 현재 접속자의 lang이 없으면 en이 있는지 확인하여 en이 있으면 en을 기본, 아니면 첫번째것을..
                if(!in_array($this->lang, $supported_lang)) {
                    if(in_array('en', $supported_lang)) {
                        $this->lang = 'en';
                    } else {
                        $this->lang = array_shift($supported_lang);
                    }
                }
            // 특별한 언어가 지정되지 않았다면 언어체크를 하지 않음
            } else {
                unset($this->lang);
            }


            $this->oParser = xml_parser_create();

            xml_set_object($this->oParser, $this);
            xml_set_element_handler($this->oParser, "_tagOpen", "_tagClosed");
            xml_set_character_data_handler($this->oParser, "_tagBody");

            xml_parse($this->oParser, $this->input);
            xml_parser_free($this->oParser);

            if(!count($this->output)) return;

            $output = array_shift($this->output);

            // 디버그를 위한 컴파일 시작 시간 저장
            if(__DEBUG__) {
                $parsing_elapsed = getMicroTime() - $start;
                $GLOBALS['__xmlparse_elapsed__'] += $parsing_elapsed;
            }

            return $output;
        }

        /**
         * @brief 태그 오픈
         **/
        function _tagOpen($parser, $node_name, $attrs) {
            $obj->node_name = strtolower($node_name);
            $obj->attrs = $this->_arrToObj($attrs);

            array_push($this->output, $obj);
        }

        /**
         * @brief body 내용
         **/
        function _tagBody($parser, $body) {
            if(!trim($body)) return;
            $this->output[count($this->output)-1]->body .= $body;
        }

        /**
         * @brief 태그 닫음
         **/
        function _tagClosed($parser, $node_name) {
            $node_name = strtolower($node_name);
            $cur_obj = array_pop($this->output);
            $parent_obj = &$this->output[count($this->output)-1];
            if($this->lang&&$cur_obj->attrs->{'xml:lang'}&&$cur_obj->attrs->{'xml:lang'}!=$this->lang) return;
            if($this->lang&&$parent_obj->{$node_name}->attrs->{'xml:lang'}&&$parent_obj->{$node_name}->attrs->{'xml:lang'}!=$this->lang) return;

            if($parent_obj->{$node_name}) {
                $tmp_obj = $parent_obj->{$node_name};
                if(is_array($tmp_obj)) {
                    array_push($parent_obj->{$node_name}, $cur_obj);
                } else {
                    $parent_obj->{$node_name} = array();
                    array_push($parent_obj->{$node_name}, $tmp_obj);
                    array_push($parent_obj->{$node_name}, $cur_obj);
                }
            } else {
                $parent_obj->{$node_name} = $cur_obj;
            }
        }

        /**
         * @brief 파싱한 결과를 object vars에 담기 위한 method
         **/
        function _arrToObj($arr) {
            if(!count($arr)) return;
            foreach($arr as $key => $val) {
                $key = strtolower($key);
                $output->{$key} = $val;
            }
            return $output;
        }
    }
?>
