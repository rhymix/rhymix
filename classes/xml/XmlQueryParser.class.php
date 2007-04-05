<?php
    /**
     * @class XmlQueryParser
     * @author zero (zero@nzeo.com)
     * @brief query xml을 파싱하여 캐싱을 한 후 결과를 return
     * @version 0.1
     *
     * @todo subquery나 union등의 확장 쿼리에 대한 지원이 필요
     **/

    class XmlQueryParser extends XmlParser {

        /**
         * @brief 조건문에서 조건을 등호로 표시하는 변수
         **/
        var $cond_operation = array(
            'equal' => '=',
            'more' => '>=',
            'excess' => '>',
            'less' => '<=',
            'below' => '<',
            'notequal' => '!=',
            'notnull' => 'is not null',
            'null' => 'is null',
        );

        /**
         * @brief 쿼리 파일을 찾아서 파싱하고 캐싱한다
         **/
        function parse($query_id, $xml_file, $cache_file) {
            // query xml 파일을 찾아서 파싱, 결과가 없으면 return
            $buff = FileHandler::readFile($xml_file);
            $xml_obj = parent::parse($buff);
            if(!$xml_obj) return;

            // 쿼리 스크립트를 만들때 필요한 변수들
            $filter_script = $notnull_script = $column_script = $default_script = '';

            // insert, update, delete, select등의 action
            $action = strtolower($xml_obj->query->attrs->action);
            if(!$action) return;

            // 테이블 정리 (배열코드로 변환)
            $tables = $this->_getTablesScript($xml_obj);

            // 컬럼 정리
            $column_script = $this->_getColumnsScript($xml_obj, $default_script, $notnull_script, $filter_script, $action);

            // 조건절 정리
            $condition_script = $this->_getConditionScript($xml_obj, $default_script, $notnull_script, $filter_script);

            // group 정리
            $group_script = $this->_getGroupScript($xml_obj);

            // 네비게이션 정리
            $navigation_script = $this->_getNavigationScript($xml_obj);

            // 캐쉬 내용 작성
            $buff = 
            sprintf(
            '<?php if(!defined("__ZBXE__")) exit();'."\n".
            '$pass_quotes = array();'."\n".
            '$id = \'%s\';'."\n".
            '$action = \'%s\';'."\n".
            '$tables = array(%s);'."\n".
            '%s'."\n".
            '%s'."\n".
            '%s'."\n".
            '%s'."\n".
            '%s'."\n".
            '%s'."\n".
            '$group_script = \'%s\''."\n".
            '?>',
            $query_id,
            $action,
            $tables,
            $default_script,
            $column_script,
            $notnull_script,
            $filter_script,
            $condition_script,
            $navigation_script,
            $group_script
            );

            // 저장
            FileHandler::writeFile($cache_file, $buff);
        }

        /**
         * @brief column, condition등의 key에 default 값을 세팅
         **/
        function _getDefaultCode($name, $value) {
            if($value == NULL) return;
            if(substr($value, -1)!=')') return sprintf('if(!$args->%s) $args->%s = \'%s\';'."\n", $name, $name, $value);

            $str_pos = strpos($value, '(');
            $func_name = substr($value, 0, $str_pos);
            $args = substr($value, $str_pos+1, strlen($value)-1);

            switch($func_name) {
                case 'ipaddress' :
                        $val = '\''.$_SERVER['REMOTE_ADDR'].'\'';
                    break;
                case 'unixtime' :
                        $val = 'time()';
                    break;
                case 'curdate' :
                        $val = 'date("YmdHis")'; 
                    break;
                case 'sequence' :
                        $val = '$this->getNextSequence()';
                    break;
                case 'plus' :
                        $args = abs($args);
                        $val = sprintf('\'%s+%d\'', $name, $args);
                        $pass_quotes = true;
                    break;
                case 'minus' :
                        $args = abs($args);
                        $val = sprintf('\'%s-%d\'', $name, $args);
                        $pass_quotes = true;
                    break;
            }

            $output = sprintf('if(!$args->%s) $args->%s = %s;'."\n", $name, $name, $val);
            if($pass_quotes) $output .= sprintf('$pass_quotes[] = \'%s\';'."\n",$name);
                return $output;
            }

        /**
         * @brief 필터 체크
         **/
        function _getFilterCode($key, $type, $minlength, $maxlength, $var='column', $notnull='') {
            if(!$type||!$minlength||!$maxlength) return;
            if(!$notnull) $notnull_code = sprintf('if($%s->%s) ', $var, $key);

            return 
                sprintf(
                    'unset($output); %s$output = $this->_checkFilter(\'%s\', $%s->%s, \'%s\', \'%d\', \'%d\'); if(!$output->toBool()) return $output;'."\n",
                    $notnull_code,
                    $key,
                    $var,
                    $key,
                    $type,
                    (int)$minlength,
                    (int)$maxlength
                );
        }

        /**
         * @brief not null 체크된 항목에 대한 처리
         **/
        function _getNotNullCode($name, $var='column') {
            return 
                sprintf(
                    'if(!$%s->%s) return new Object(-1, sprintf($lang->filter->isnull, $lang->%s?$lang->%s:\'%s\'));'."\n",
                    $var,
                    $name,
                    $name,
                    $name,
                    $name
                );
        }

        /**
         * @brief 대상 테이블에 대한 처리
         **/
        function _getTablesScript($xml_obj) {
            $obj_tables = $xml_obj->query->tables->table;
            if(!is_array($obj_tables)) $obj_tables = array('', $obj_tables);

            foreach($obj_tables as $table_info) {
                $name = trim($table_info->attrs->name);
                if(!$name) continue;
                $alias = trim($table_info->attrs->alias);
                if(!$alias) $alias = $name;
                $table_list[] = sprintf('\'%s\'=>\'%s\'', $name, $alias);
            }
            return implode(",",$table_list);
        }

        /**
         * @brief 컬럼 처리
         **/
        function _getColumnsScript($xml_obj, &$default_script, &$notnull_script, &$filter_script, $action) {
            $obj_columns = $xml_obj->query->columns->column;
            if(!is_array($obj_columns)) $obj_columns = array('', $obj_columns);

            foreach($obj_columns as $column_info) {
                $name = trim($column_info->attrs->name);
                if(!$name || $name == '*') continue;

                $var = trim($column_info->attrs->var);
                $alias = trim($column_info->attrs->alias);
                if(!$alias) $alias = $name;
                $default = trim($column_info->attrs->default);
                $notnull = trim($column_info->attrs->notnull);

                $filter_type = trim($column_info->attrs->filter);
                $minlength = (int)trim($column_info->attrs->minlength);
                $maxlength = (int)trim($column_info->attrs->maxlength);

                $column_script .= sprintf('$column->%s = $args->%s;'."\n", $alias, $var?$var:$alias);
                $invert_columns[] = sprintf('\'%s\'=>\'%s\'', $alias, $name);

                $default_script .= $this->_getDefaultCode($alias, $default);
                if($action != 'select') {
                    if($filter_type) $filter_script .= $this->_getFilterCode($alias, $filter_type, $minlength, $maxlength, 'column', $notnull);
                    if($notnull) $notnull_script .= $this->_getNotNullCode($alias);
                }
            }
            if(is_array($invert_columns)) $column_script .= sprintf('$invert_columns = array(%s);'."\n", implode(',',$invert_columns));
            return $column_script;
        }

        /**
         * @brief 조건절 처리
         **/
        function _getConditionScript($xml_obj, &$default_script, &$notnull_script, &$filter_script) {
            $cond_idx = 0;

            $obj_conditions = $xml_obj->query->conditions->condition;

            $condition_script = $condition = $this->_getConditionQuery($cond_idx++, $obj_conditions, NULL, $notnull_script, $filter_script);

            $obj_groups = $xml_obj->query->conditions->group;
            if(!is_array($obj_groups)) $obj_groups = array('', $obj_groups);

            foreach($obj_groups as $obj_group) {
                $group_pipe = $obj_group->attrs->pipe;
                if(!$group_pipe) continue;
                $buff = $this->_getConditionQuery($cond_idx++, $obj_group->condition, $group_pipe, $notnull_script, $filter_script);
                $condition_script .= $buff;
            }

            $condition_script .= '$condition = $this->_combineCondition($cond_group, $group_pipe);'."\n";
            return $condition_script;
        }

        /**
         * @brief 조건문의 쿼리를 만들어 줌
         **/
        function _getConditionQuery($cond_idx, $obj, $group_pipe, &$notnull_script, &$filter_script) {
            if(!is_array($obj)) $obj = array('', $obj);

            $idx = 0;
            foreach($obj as $obj_cond) {
                $operation = $obj_cond->attrs->operation;
                if(!$operation) continue; 

                $column = $obj_cond->attrs->column;
                $var = $obj_cond->attrs->var;
                $filter = $obj_cond->attrs->filter;
                $notnull = $obj_cond->attrs->notnull;
                $pipe = $obj_cond->attrs->pipe;
                if(!$pipe) $pipe = 'and';
                $default = $obj_cond->attrs->default;

                // 비교 대상이 다른 혹은 같은 테이블의 column일 경우
                if(eregi("\.", $var)) {
                    switch($operation) {
                        case 'in' :
                                $buff = sprintf('%s in (%s)', $column, $var);
                            break;
                        default :
                                $operation = $this->cond_operation[$operation];
                                if(!$operation) $operation = 'and';
                                $buff = sprintf('%s %s %s', $column, $operation, $var);
                            break;
                    }
                    $condition_script .= sprintf('$cond_group[%d][][\'%s\'] = \'%s\';'."\n",$cond_idx, $pipe, $buff);

                // 입력받을 변수일 경우
                } else {
                    switch($operation) {
                    case 'like' :
                            $buff = sprintf('sprintf("%s like \'%%%%%%s%%%%\' ", $this->addQuotes($args->%s))', $column, $var);
                        break;
                    case 'like_prefix' :
                            $buff = sprintf('sprintf("%s like \'%%s%%%%\' ", $this->addQuotes($args->%s))', $column, $var);
                        break;
                    case 'in' :
                            //$buff = sprintf('sprintf("%s in (%%s) ", $this->addQuotes($args->%s))', $column, $var);
                            $buff = sprintf('sprintf("%s in (%%s) ", $args->%s)', $column, $var);
                        break;
                    case 'notnull' :
                    case 'null' :
                            $operation = $this->cond_operation[$operation];
                            unset($var);
                            $buff = sprintf('"%s %s "', $column, $operation);
                        break;
                    default :
                            $operation = $this->cond_operation[$operation];
                            if($default) $buff = sprintf('sprintf("%s %s \'%%s\' ", $args->%s?$this->addQuotes($args->%s):\'%s\')', $column, $operation, $var?$var:$column, $var?$var:$column, $default);
                            else $buff = sprintf('sprintf("%s %s \'%%s\' ", $this->addQuotes($args->%s))', $column, $operation, $var?$var:$column);
                        break;
                    }

                    $buff = sprintf('$cond_group[%d][][\'%s\'] = %s;'."\n",$cond_idx, $pipe, $buff);

                    if(!$notnull && $var) $buff = sprintf('if($args->%s) ', $var).$buff;
                    $condition_script .= $buff;
                    if($notnull) $notnull_script .= $this->_getNotNullCode($var?$var:$column, 'args');
                    if($filter) $filter_script .= $this->_getFilterCode($var, $filter, 0, 0, 'args', $notnull);
                }
            }
            $condition_script .= sprintf('$group_pipe[%d] = \'%s\';'."\n", $cond_idx, $group_pipe);
            return $condition_script;
        }

        /**
         * @brief group by 쿼리 처리
         **/
        function _getGroupScript($xml_obj) {
            $group_list = $xml_obj->query->groups->group;
            if(!$group_list) return;
            if(!is_array($group_list)) $group_list = array($group_list);
            for($i=0;$i<count($group_list);$i++) {
                $group = $group_list[$i];
                $column = trim($group->attrs->column);
                if(!$column) continue;
                $group_column_list[] = $column;
            }

            if(count($group_column_list)) {
                return ' GROUP BY '.implode(" , ", $group_column_list);
            }
        }

        /**
         * @brief page navigation 처리
         **/
        function _getNavigationScript($xml_obj) { 
            $obj_navigation = $xml_obj->query->navigation;
            if(!$obj_navigation) return;

            $obj_index = $obj_navigation->index->attrs;
            $index_list = array();
            if(!is_array($obj_index)) $obj_index = array('', $obj_index);
            foreach($obj_index as $index_info) {
                $var = trim($index_info->var);
                if(!$var) continue;
                $default = trim($index_info->default);
                $order = trim($index_info->order);
                if(!$order) $order = 'asc';

                $navigation_script .= sprintf('$navigation->index[] = array($args->%s?$args->%s:\'%s\', \'%s\');'."\n", $var, $var, $default, $order);
            }

            $obj_list_count = $obj_navigation->list_count->attrs;
            $count_var = $obj_list_count->var;
            $count_default = $obj_list_count->default;
            if($count_var) $navigation_script .= sprintf('$navigation->list_count = $args->%s?$args->%s%s;'."\n", $count_var, $count_var, $count_default?':'.$count_default:'');

            $obj_page_count = $obj_navigation->page_count->attrs;
            $count_var = $obj_page_count->var;
            $count_default = $obj_page_count->default;
            if($count_var) $navigation_script .= sprintf('$navigation->page_count = $args->%s?$args->%s%s;'."\n", $count_var, $count_var, $count_default?':'.$count_default:'');

            $obj_page = $obj_navigation->page->attrs;
            $page_var = $obj_page->var;
            $page_default = $obj_page->default;
            if($page_var) $navigation_script .= sprintf('$navigation->page = $args->%s?$args->%s%s;'."\n", $page_var, $page_var, $page_default?':'.$page_default:'');

            return $navigation_script;
        }
    }
?>
