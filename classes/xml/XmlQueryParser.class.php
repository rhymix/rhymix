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
         * @brief 쿼리 파일을 찾아서 파싱하고 캐싱한다
         **/
        function parse($query_id, $xml_file, $cache_file) {
            // query xml 파일을 찾아서 파싱, 결과가 없으면 return
            $buff = FileHandler::readFile($xml_file);
            $xml_obj = parent::parse($buff);
            if(!$xml_obj) return;
            unset($buff);

            $id_args = explode('.', $query_id);
            if(count($id_args)==2) {
                $target = 'modules';
                $module = $id_args[0];
                $id = $id_args[1];
            } elseif(count($id_args)==3) {
                $target = $id_args[0];
                if(!in_array($target, array('modules','addons','widgets'))) return;
                $module = $id_args[1];
                $id = $id_args[2];
            }

            // insert, update, delete, select등의 action
            $action = strtolower($xml_obj->query->attrs->action);
            if(!$action) return;

            // 테이블 정리 (배열코드로 변환)
            $tables = $xml_obj->query->tables->table;
            $output->left_tables = array();

            $left_conditions = array();

            if(!$tables) return;
            if(!is_array($tables)) $tables = array($tables);
            foreach($tables as $key => $val) {

                // 테이블과 alias의 이름을 구함
                $table_name = $val->attrs->name;
                $alias = $val->attrs->alias;
                if(!$alias) $alias = $table_name;

                $output->tables[$alias] = $table_name;

                if(in_array($val->attrs->type,array('left join','left outer join','right join','right outer join')) && count($val->conditions)){
                    $output->left_tables[$alias] =  $val->attrs->type;
                    $left_conditions[$alias] = $val->conditions;
                }

                // 테이블을 찾아서 컬럼의 속성을 구함
                $table_file = sprintf('%s%s/%s/schemas/%s.xml', _XE_PATH_, 'modules', $module, $table_name);
                if(!file_exists($table_file)) {
                    $searched_list = FileHandler::readDir(_XE_PATH_.'modules');
                    $searched_count = count($searched_list);
                    for($i=0;$i<$searched_count;$i++) {
                        $table_file = sprintf('%s%s/%s/schemas/%s.xml', _XE_PATH_, 'modules', $searched_list[$i], $table_name);
                        if(file_exists($table_file)) break;
                    }
                }

                if(file_exists($table_file)) {
                    $table_xml = FileHandler::readFile($table_file);
                    $table_obj = parent::parse($table_xml);
                    if($table_obj->table) {
                        if(isset($table_obj->table->column) && !is_array($table_obj->table->column))
                        {
                            $table_obj->table->column = array($table_obj->table->column);
                        }

                        foreach($table_obj->table->column as $k => $v) {
                            $buff .= sprintf('$output->column_type["%s"] = "%s";%s', $v->attrs->name, $v->attrs->type, "\n");
                        }
                    }
                }
            }


            // 컬럼 정리
            $columns = $xml_obj->query->columns->column;
            $out = $this->_setColumn($columns);
            $output->columns = $out->columns;

            $conditions = $xml_obj->query->conditions;
            $out = $this->_setConditions($conditions);
            $output->conditions = $out->conditions;

            foreach($output->left_tables as $key => $val){
                if($left_conditions[$key]){
                    $out = $this->_setConditions($left_conditions[$key]);
                    $output->left_conditions[$key] = $out->conditions;
                }
            }

            $group_list = $xml_obj->query->groups->group;
            $out = $this->_setGroup($group_list);
            $output->groups = $out->groups;

            // 네비게이션 정리
            $out = $this->_setNavigation($xml_obj);
            $output->order = $out->order;
            $output->list_count = $out->list_count;
            $output->page_count = $out->page_count;
            $output->page = $out->page;


            $column_count = count($output->columns);
            $condition_count = count($output->conditions);

            $buff .= '$output->tables = array( ';
            foreach($output->tables as $key => $val) {
                if(!array_key_exists($key,$output->left_tables)){
                    $buff .= sprintf('"%s"=>"%s",', $key, $val);
                }
            }
            $buff .= ' );'."\n";


            // php script 생성
            $buff .= '$output->_tables = array( ';
            foreach($output->tables as $key => $val) {
                $buff .= sprintf('"%s"=>"%s",', $key, $val);
            }
            $buff .= ' );'."\n";



            if(count($output->left_tables)){
                $buff .= '$output->left_tables = array( ';
                foreach($output->left_tables as $key => $val) {
                    $buff .= sprintf('"%s"=>"%s",', $key, $val);
                }
                $buff .= ' );'."\n";
            }


            // column 정리
            if($column_count) {
                $buff .= '$output->columns = array ( ';
                $buff .= $this->_getColumn($output->columns);
                $buff .= ' );'."\n";
            }

            // conditions 정리
            if($condition_count) {
                $buff .= '$output->conditions = array ( ';
                $buff .= $this->_getConditions($output->conditions);
                $buff .= ' );'."\n";
            }

            // conditions 정리
            if(count($output->left_conditions)) {
                $buff .= '$output->left_conditions = array ( ';
                foreach($output->left_conditions as $key => $val){
                    $buff .= "'{$key}' => array ( ";
                    $buff .= $this->_getConditions($val);
                    $buff .= "),\n";
                }
                $buff .= ' );'."\n";
            }



            // order 정리
            if($output->order) {
                $buff .= '$output->order = array(';
                foreach($output->order as $key => $val) {
                    $buff .= sprintf('array($args->%s?$args->%s:"%s",in_array($args->%s,array("asc","desc"))?$args->%s:("%s"?"%s":"asc")),', $val->var, $val->var, $val->default, $val->order, $val->order, $val->order, $val->order);
                }
                $buff .= ');'."\n";
            }

            // list_count 정리
            if($output->list_count) {
                $buff .= sprintf('$output->list_count = array("var"=>"%s", "value"=>$args->%s?$args->%s:"%s");%s', $output->list_count->var, $output->list_count->var, $output->list_count->var, $output->list_count->default,"\n");
            }

            // page_count 정리
            if($output->page_count) {
                $buff .= sprintf('$output->page_count = array("var"=>"%s", "value"=>$args->%s?$args->%s:"%s");%s', $output->page_count->var, $output->page_count->var, $output->page_count->var, $output->list_count->default,"\n");
            }

            // page 정리
            if($output->page) {
                $buff .= sprintf('$output->page = array("var"=>"%s", "value"=>$args->%s?$args->%s:"%s");%s', $output->page->var, $output->page->var, $output->page->var, $output->list->default,"\n");
            }

            // group by 정리
            if($output->groups) {
                $buff .= sprintf('$output->groups = array("%s");%s', implode('","',$output->groups),"\n");
            }

            // default check
            if(count($default_list)) {
                foreach($default_list as $key => $val) {
                    $pre_buff .= 'if(!isset($args->'.$key.')) $args->'.$key.' = '.$val.';'."\n";
                }
            }

            // not null check
            if(count($notnull_list)) {
                foreach($notnull_list as $key => $val) {
                    $pre_buff .= 'if(!isset($args->'.$val.')) return new Object(-1, sprintf($lang->filter->isnull, $lang->'.$val.'?$lang->'.$val.':\''.$val.'\'));'."\n";
                }
            }

            // minlength check
            if(count($minlength_list)) {
                foreach($minlength_list as $key => $val) {
                    $pre_buff .= 'if($args->'.$key.'&&strlen($args->'.$key.')<'.$val.') return new Object(-1, sprintf($lang->filter->outofrange, $lang->'.$key.'?$lang->'.$key.':\''.$key.'\'));'."\n";
                }
            }

            // maxlength check
            if(count($maxlength_list)) {
                foreach($maxlength_list as $key => $val) {
                    $pre_buff .= 'if($args->'.$key.'&&strlen($args->'.$key.')>'.$val.') return new Object(-1, sprintf($lang->filter->outofrange, $lang->'.$key.'?$lang->'.$key.':\''.$key.'\'));'."\n";
                }
            }

            // filter check
            if(count($filter_list)) {
                foreach($filter_list as $key => $val) {
                    if(!$notnull_list[$key]) continue;
                    $pre_buff .= sprintf('unset($_output); $_output = $this->checkFilter("%s",$args->%s,"%s"); if(!$_output->toBool()) return $_output;%s',$val->var,$val->var,$val->filter,"\n");
                }
            }

            $buff = "<?php if(!defined('__ZBXE__')) exit();\n"
                  . sprintf('$output->query_id = "%s";%s', $query_id, "\n")
                  . sprintf('$output->action = "%s";%s', $action, "\n")
                  . $pre_buff
                  . $buff
                  . 'return $output; ?>';

            // 저장
            FileHandler::writeFile($cache_file, $buff);
        }




        function _setColumn($columns){

            if(!$columns) {
                $output->column[] = array("*" => "*");
            } else {
                if(!is_array($columns)) $columns = array($columns);
                foreach($columns as $key => $val) {
                    $name = $val->attrs->name;
                    /*
                    if(strpos('.',$name)===false && count($output->tables)==1) {
                        $tmp = array_values($output->tables);
                        $name = sprintf('%s.%s', $tmp[0], $val->attrs->name);
                    }
                    */

                    $output->columns[] = array(
                        "name" => $name,
                        "var" => $val->attrs->var,
                        "default" => $val->attrs->default,
                        "notnull" => $val->attrs->notnull,
                        "filter" => $val->attrs->filter,
                        "minlength" => $val->attrs->minlength,
                        "maxlength" => $val->attrs->maxlength,
                        "alias" => $val->attrs->alias,
                    );
                }
            }
            return $output;
        }


        function _setConditions($conditions){
            // 조건절 정리

            $condition = $conditions->condition;
            if($condition) {
                $obj->condition = $condition;
                unset($condition);
                $condition = array($obj);
            }
            $condition_group = $conditions->group;
            if($condition_group && !is_array($condition_group)) $condition_group = array($condition_group);

            if($condition && $condition_group) $cond = array_merge($condition, $condition_group);
            elseif($condition_group) $cond = $condition_group;
            else $cond = $condition;

            if($cond) {
                foreach($cond as $key => $val) {
                    unset($cond_output);

                    if($val->attrs->pipe) $cond_output->pipe = $val->attrs->pipe;
                    else $cond_output->pipe = null;

                    if(!$val->condition) continue;
                    if(!is_array($val->condition)) $val->condition = array($val->condition);

                    foreach($val->condition as $k => $v) {
                        $obj = $v->attrs;
                        if(!$obj->alias) $obj->alias = $obj->column;
                        $cond_output->condition[] = $obj;
                    }

                    $output->conditions[] = $cond_output;
                }
            }
            return $output;
        }

        function _setGroup($group_list){
            // group 정리

            if($group_list) {
                if(!is_array($group_list)) $group_list = array($group_list);
                for($i=0;$i<count($group_list);$i++) {
                    $group = $group_list[$i];
                    $column = trim($group->attrs->column);
                    if(!$column) continue;
                    $group_column_list[] = $column;
                }
                if(count($group_column_list)) $output->groups = $group_column_list;
            }
            return $output;
        }


        function _setNavigation($xml_obj){
            $navigation = $xml_obj->query->navigation;
            if($navigation) {
                $order = $navigation->index;
                if($order) {
                    if(!is_array($order)) $order = array($order);
                    foreach($order as $order_info) {
                        $output->order[] = $order_info->attrs;
                    }
                }

                $list_count = $navigation->list_count->attrs;
                $output->list_count = $list_count;

                $page_count = $navigation->page_count->attrs;
                $output->page_count = $page_count;

                $page = $navigation->page->attrs;
                $output->page = $page ;
            }
            return $output;
        }





        function _getColumn($columns){
            $buff = '';
            foreach($columns as $key => $val) {
                $val['default'] = $this->getDefault($val['name'], $val['default']);
                if($val['var'] && strpos($val['var'],'.')===false) {

                    if($val['default']) $buff .= sprintf('array("name"=>"%s", "alias"=>"%s", "value"=>$args->%s?$args->%s:%s),%s', $val['name'], $val['alias'], $val['var'], $val['var'], $val['default'] ,"\n");
                    else $buff .= sprintf('array("name"=>"%s", "alias"=>"%s", "value"=>$args->%s),%s', $val['name'], $val['alias'], $val['var'], "\n");

                    if($val['default']) $default_list[$val['var']] = $val['default'];
                    if($val['notnull']) $notnull_list[] = $val['var'];
                    if($val['minlength']) $minlength_list[$val['var']] = $val['minlength'];
                    if($val['maxlength']) $maxlength_list[$val['var']] = $val['maxlength'];
                } else {
                    if($val['default']) $buff .= sprintf('array("name"=>"%s", "alias"=>"%s", "value"=>%s),%s', $val['name'], $val['alias'], $val['default'] ,"\n");
                    else $buff .= sprintf('array("name"=>"%s", "alias"=>"%s",),%s', $val['name'], $val['alias'], "\n");
                }
            }
            return $buff;
        }





        function _getConditions($conditions){
            $buff = '';
            foreach($conditions as $key => $val) {
                $buff .= sprintf('array("pipe"=>"%s",%s"condition"=>array(', $val->pipe,"\n");
                foreach($val->condition as $k => $v) {
                    $v->default = $this->getDefault($v->column, $v->default);
                    if($v->var) {
                        if(strpos($v->var,".")===false) {
                            if($v->default) $default_list[$v->var] = $v->default;
                            if($v->filter) $filter_list[] = $v;
                            if($v->notnull) $notnull_list[] = $v->var;
                            if($v->default) $buff .= sprintf('array("column"=>"%s", "value"=>$args->%s?$args->%s:%s,"pipe"=>"%s","operation"=>"%s",),%s', $v->column, $v->var, $v->var, $v->default, $v->pipe, $v->operation, "\n");
                            else $buff .= sprintf('array("column"=>"%s", "value"=>$args->%s,"pipe"=>"%s","operation"=>"%s",),%s', $v->column, $v->var, $v->pipe, $v->operation, "\n");
                        } else {
                            $buff .= sprintf('array("column"=>"%s", "value"=>"%s","pipe"=>"%s","operation"=>"%s",),%s', $v->column, $v->var, $v->pipe, $v->operation, "\n");
                        }
                    } else {
                        if($v->default) $buff .= sprintf('array("column"=>"%s", "value"=>%s,"pipe"=>"%s","operation"=>"%s",),%s', $v->column, $v->default ,$v->pipe, $v->operation,"\n");
                        else $buff .= sprintf('array("column"=>"%s", "pipe"=>"%s","operation"=>"%s",),%s', $v->column, $v->pipe, $v->operation,"\n");
                    }
                }
                $buff .= ')),'."\n";
            }
            return $buff;
        }







        /**
         * @brief column, condition등의 key에 default 값을 세팅
         **/
        function getDefault($name, $value) {
            if(!$value) return;
            $str_pos = strpos($value, '(');
            if($str_pos===false) return '"'.$value.'"';

            $func_name = substr($value, 0, $str_pos);
            $args = substr($value, $str_pos+1, strlen($value)-1);

            switch($func_name) {
                case 'ipaddress' :
                        $val = '$_SERVER[\'REMOTE_ADDR\']';
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
                        $val = sprintf('"%s+%d"', $name, $args);
                    break;
                case 'minus' :
                        $args = abs($args);
                        $val = sprintf('"%s-%d"', $name, $args);
                    break;
            }

            return $val;
        }

    }
?>
