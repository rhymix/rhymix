<?php
  /**
   * @file   : classes/xml/XmlParser.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : xmlrpc를 해석하여 object로 return 하는 simple xml parser
   **/

  class XmlParser {

    var $oParser = NULL;

    var $input = NULL;
    var $output = array();

    var $lang = "en";

    // public object loadXmlFile($filename)/*{{{*/
    function loadXmlFile($filename) {
      if(!file_exists($filename)) return;

      $buff = FileHandler::readFile($filename);

      $oXmlParser = new XmlParser();
      return $oXmlParser->parse($buff);
    }/*}}}*/

    // public void parse($input)/*{{{*/
    function parse($input = '') {
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
      return array_shift($this->output);
    }/*}}}*/

    // private void _tagOpen($parser, $node_name, $attrs)/*{{{*/
    function _tagOpen($parser, $node_name, $attrs) {
      $obj->node_name = strtolower($node_name);
      $obj->attrs = $this->_arrToObj($attrs);

      array_push($this->output, $obj);
    }/*}}}*/

    // private void _tagBody($parser, $body)/*{{{*/
    function _tagBody($parser, $body) {
      if(!trim($body)) return;
      $this->output[count($this->output)-1]->body .= $body;
    }/*}}}*/

    // private void _tagClosed($parser, $node_name)/*{{{*/
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
    }/*}}}*/

    // private void _arrToObj($arr)/*{{{*/
    function _arrToObj($arr) {
      if(!count($arr)) return;
      foreach($arr as $key => $val) {
        $key = strtolower($key);
        $output->{$key} = $val;
      }
      return $output;
    }/*}}}*/
  }
?>
