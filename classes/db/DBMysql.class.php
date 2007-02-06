<?php
  /**
   * @file   : classes/db/DB.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : db(mysql, cubrid, sqlite..)를 이용하기 위한 db class의 abstract class
   **/

  class DBMysql extends DB {

    // db info
    var $hostname = '127.0.0.1';
    var $userid   = NULL;
    var $password   = NULL;
    var $database = NULL;
    var $prefix   = 'zb';

    // mysql에서 사용될 column type
    var $column_type = array(
          'number' => 'int',
          'varchar' => 'varchar',
          'char' => 'char',
          'text' => 'text',
          'date' => 'varchar(14)',
        );

    // public void DBMysql()/*{{{*/
    function DBMysql() {
      $this->_setDBInfo();
      $this->_connect();
    }/*}}}*/

    /**
     * DB정보 설정 및 connect/ close
     **/
    // public boolean _setDBInfo()/*{{{*/
    function _setDBInfo() {
      $db_info = Context::getDBInfo();
      $this->hostname = $db_info->db_hostname;
      $this->userid   = $db_info->db_userid;
      $this->password   = $db_info->db_password;
      $this->database = $db_info->db_database;
      $this->prefix = $db_info->db_table_prefix;
      if(!substr($this->prefix,-1)!='_') $this->prefix .= '_';
    }/*}}}*/

    // public boolean _connect()/*{{{*/
    function _connect() {
      // db 정보가 없으면 무시
      if(!$this->hostname || !$this->userid || !$this->password || !$this->database) return;
   
      // 접속시도  
      $this->fd = @mysql_connect($this->hostname, $this->userid, $this->password);
      if(mysql_error()) {
        $this->setError(mysql_errno(), mysql_error());
        return;
      }

      // db 선택
      @mysql_select_db($this->database, $this->fd);
      if(mysql_error()) {
        $this->setError(mysql_errno(), mysql_error());
        return;
      }

      // 접속체크
      $this->is_connected = true;

      // mysql의 경우 utf8임을 지정
      $this->_query("SET NAMES 'utf8'");
    }/*}}}*/

    // public boolean close()/*{{{*/
    function close() {
      if(!$this->isConnected()) return;
      @mysql_close($this->fd);
    }/*}}}*/

    /**
     * add quotation
     **/
    // public string addQuotes(string $string)/*{{{*/
    function addQuotes($string) {
      if(get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
      if(!is_numeric($string)) $string = @mysql_escape_string($string);
      return $string;
    }/*}}}*/

    /**
     * query : query문 실행하고 result return
     * fetch : reutrn 된 값이 없으면 NULL
     *         rows이면 array object
     *         row이면 object
     *         return
     *  getNextSequence : 1씩 증가되는 sequence값을 return (mysql의 auto_increment는 sequence테이블에서만 사용)
     */
    // private object _query(string $query) /*{{{*/
    function _query($query) {
      if(!$this->isConnected()) return;
      $this->query = $query;

      $this->setError(0,'success');

      $result = @mysql_query($query, $this->fd);

      if(mysql_error()) {
        $this->setError(mysql_errno(), mysql_error());
        return;
      }

      return $result;
    }/*}}}*/

    // private object _fetch($result) /*{{{*/
    function _fetch($result) {
      if($this->errno!=0 || !$result) return;
      while($tmp = mysql_fetch_object($result)) {
        $output[] = $tmp;
      }
      if(count($output)==1) return $output[0];
      return $output;
    }/*}}}*/

    // public int getNextSequence() /*{{{*/
    // sequence값을 받음
    function getNextSequence() {
      $query = sprintf("insert into `%ssequence` (seq) values ('')", $this->prefix);
      $this->_query($query);
      return mysql_insert_id();
    }/*}}}*/

    /**
     * Table의 Create/Drop/Alter/Rename/Truncate/Dump...
     **/
    // public boolean isTableExists(string $table_name)/*{{{*/
    function isTableExists($target_name) {
      $query = sprintf("show tables like '%s%s'", $this->prefix, $this->addQuotes($target_name));
      $result = $this->_query($query);
      $tmp = $this->_fetch($result);
      if(!$tmp) return false;
      return true;
    }/*}}}*/

    // public boolean createTableByXml($xml) /*{{{*/
    // xml 을 받아서 테이블을 생성
    function createTableByXml($xml_doc) {
      return $this->_createTable($xml_doc);
    }/*}}}*/

    // public boolean createTableByXmlFile($file_name) /*{{{*/
    // xml 을 받아서 테이블을 생성
    function createTableByXmlFile($file_name) {
      if(!file_exists($file_name)) return;
      // xml 파일을 읽음
      $buff = FileHandler::readFile($file_name);
      return $this->_createTable($buff);
    }/*}}}*/

    // private boolean _createTable($xml)/*{{{*/
    // type : number, varchar, text, char, date, 
    // opt : notnull, default, size
    // index : primary key, index, unique
    function _createTable($xml_doc) {
      // xml parsing
      $oXml = new XmlParser();
      $xml_obj = $oXml->parse($xml_doc);

      // 테이블 생성 schema 작성
      $table_name = $xml_obj->table->attrs->name;
      if($this->isTableExists($table_name)) return;
      $table_name = $this->prefix.$table_name;

      if(!is_array($xml_obj->table->column)) $columns[] = $xml_obj->table->column;
      else $columns = $xml_obj->table->column;
      foreach($columns as $column) {
        $name = $column->attrs->name;
        $type = $column->attrs->type;
        $size = $column->attrs->size;
        $notnull = $column->attrs->notnull;
        $primary_key = $column->attrs->primary_key;
        $index = $column->attrs->index;
        $unique = $column->attrs->unique;
        $default = $column->attrs->default;
        $auto_increment = $column->attrs->auto_increment;

        $column_schema[] = sprintf('`%s` %s%s %s %s %s',
          $name,
          $this->column_type[$type],
          $size?'('.$size.')':'',
          $default?"default '".$default."'":'',
          $notnull?'not null':'',
          $auto_increment?'auto_increment':''
        );

        if($primary_key) $primary_list[] = $name;
        else if($unique) $unique_list[$unique][] = $name;
        else if($index) $index_list[$index][] = $name;
      }

      if(count($primary_list)) {
        $column_schema[] = sprintf("primary key (%s)", '`'.implode($primary_list,'`,`').'`');
      }

      if(count($unique_list)) {
        foreach($unique_list as $key => $val) {
          $column_schema[] = sprintf("unique %s (%s)", $key, '`'.implode($val,'`,`').'`');
        }
      }

      if(count($index_list)) {
        foreach($index_list as $key => $val) {
          $column_schema[] = sprintf("index %s (%s)", $key, '`'.implode($val,'`,`').'`');
        }
      }

      $schema = sprintf('create table `%s` (%s%s);', $this->addQuotes($table_name), "\n", implode($column_schema,",\n"));
      $output = $this->_query($schema);
      if(!$output) return false;
    }/*}}}*/

    // public boolean dropTable($target_name) /*{{{*/
    // 테이블 삭제
    function dropTable($target_name) {
      $query = sprintf('drop table `%s%s`;', $this->prefix, $this->addQuotes($target_name));
      $this->_query($query);
    }/*}}}*/

    // public boolean renameTable($source_name, $targe_name) /*{{{*/
    // 테이블의 이름 변경
    function renameTable($source_name, $targe_name) {
      $query = sprintf("alter table `%s%s` rename `%s%s`;", $this->prefix, $this->addQuotes($source_name), $this->prefix, $this->addQuotes($targe_name));
      $this->_query($query);
    }/*}}}*/

    // public boolean truncateTable($target_name) /*{{{*/
    // 테이블을 비움
    function truncateTable($target_name) {
      $query = sprintf("truncate table `%s%s`;", $this->prefix, $this->addQuotes($target_name));
      $this->_query($query);
    }/*}}}*/

    // public boolean dumpTable($target_name) 미완성 /*{{{*/ 
    // 테이블 데이터 Dump
    function dumpTable($target_name) {
    }/*}}}*/

    /**
     * insert/update/delete/select 구현
     **/
    // private string _executeInsertAct($tables, $column, $pass_quotes)/*{{{*/
    function _executeInsertAct($tables, $column, $pass_quotes) {
      $table = array_pop($tables);

      foreach($column as $key => $val) {
        $key_list[] = $key;
        if(in_array($key, $pass_quotes)) $val_list[] = $this->addQuotes($val);
        else $val_list[] = '\''.$this->addQuotes($val).'\'';
      }

      $query = sprintf("insert into `%s%s` (%s) values (%s);", $this->prefix, $table, '`'.implode('`,`',$key_list).'`', implode(',', $val_list));
      return $this->_query($query);
    }/*}}}*/

    // private string _executeUpdateAct($tables, $column, $condition, $pass_quotes)/*{{{*/
    function _executeUpdateAct($tables, $column, $condition, $pass_quotes) {
      $table = array_pop($tables);

      foreach($column as $key => $val) {
        if(in_array($key, $pass_quotes)) $update_list[] = sprintf('`%s` = %s', $key, $this->addQuotes($val));
        else $update_list[] = sprintf('`%s` = \'%s\'', $key, $this->addQuotes($val));
      }
      if(!count($update_list)) return;
      $update_query = implode(',',$update_list);

      if($condition) $condition = ' where '.$condition;

      $query = sprintf("update `%s%s` set %s %s;", $this->prefix, $table, $update_query, $condition);
      return $this->_query($query);
    }/*}}}*/

    // private string _executeDeleteAct($tables, $condition, $pass_quotes)/*{{{*/
    function _executeDeleteAct($tables, $condition, $pass_quotes) {
      $table = array_pop($tables);

      if($condition) $condition = ' where '.$condition;

      $query = sprintf("delete from `%s%s` %s;", $this->prefix, $table, $condition);
      return $this->_query($query);
    }/*}}}*/

    // private string _executeSelectAct($tables, $column, $invert_columns, $condition, $navigation, $pass_quotes)/*{{{*/
    // 네비게이션 변수 정리를 해야함
    function _executeSelectAct($tables, $column, $invert_columns, $condition, $navigation, $group_script, $pass_quotes) {
      if(!count($tables)) $table = $this->prefix.array_pop($tables);
      else { 
        foreach($tables as $key => $val) $table_list[] = sprintf('%s%s as %s', $this->prefix, $key, $val);
      }
      $table = implode(',',$table_list);

      if(!$column) $columns = '*';
      else {
        foreach($invert_columns as $key => $val) {
          $column_list[] = sprintf('%s as %s',$val, $key);
        }
        $columns = implode(',', $column_list);
      }

      if($condition) $condition = ' where '.$condition;

      if($navigation->list_count) return $this->_getNavigationData($table, $columns, $condition, $navigation);

      $query = sprintf("select %s from %s %s", $columns, $table, $condition);

      $query .= ' '.$group_script;

      if($navigation->index) {
        foreach($navigation->index as $index_obj) {
          $index_list[] = sprintf('%s %s', $index_obj[0], $index_obj[1]);
        }
        if(count($index_list)) $query .= ' order by '.implode(',',$index_list);
      }


      $result = $this->_query($query);
      if($this->errno!=0) return;
      $data = $this->_fetch($result);

      $buff = new Output();
      $buff->data = $data;
      return $buff;
    }/*}}}*/

    // private function _getNavigationData($query)/*{{{*/
    // query xml에 navigation 정보가 있을 경우 페이징 관련 작업을 처리한다
    // 그닥 좋지는 않은 구조이지만 편리하다.. -_-;
    function _getNavigationData($table, $columns, $condition, $navigation) {
      // 전체 개수를 구함
      $count_query = sprintf("select count(*) as count from %s %s", $table, $condition);
      $result = $this->_query($count_query);
      $count_output = $this->_fetch($result);
      $total_count = (int)$count_output->count;

      // 전체 페이지를 구함
      $total_page = (int)(($total_count-1)/$navigation->list_count) +1;

      // 페이지 변수를 체크
      if($navigation->page > $total_page) $page = $navigation->page;
      else $page = $navigation->page;
      $start_count = ($page-1)*$navigation->list_count;

      foreach($navigation->index as $index_obj) {
        $index_list[] = sprintf('%s %s', $index_obj[0], $index_obj[1]);
      }

      $index = implode(',',$index_list);
      $query = sprintf('select %s from %s %s order by %s limit %d, %d', $columns, $table, $condition, $index, $start_count, $navigation->list_count);
      $result = $this->_query($query);
      if($this->errno!=0) return;

      $virtual_no = $total_count - ($page-1)*$navigation->list_count;
      while($tmp = mysql_fetch_object($result)) {
        $data[$virtual_no--] = $tmp;
      }

      $buff = new Output();
      $buff->total_count = $total_count;
      $buff->total_page = $total_page;
      $buff->page = $page;
      $buff->data = $data;

      require_once('./classes/page/PageHandler.class.php');
      $buff->page_navigation = new PageHandler($total_count, $total_page, $page, $navigation->page_count);
      return $buff;
    }/*}}}*/
  }
?>
