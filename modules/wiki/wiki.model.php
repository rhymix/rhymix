<?php
    /**
     * @class  wikiModel
     * @author haneul (haneul0318@gmail.com) 
     * @brief  wiki 모듈의 Model class
     **/

    class wikiModel extends module {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 계층구조 추출
         * document_category테이블을 이용해서 위키 문서의 계층 구조도를 그림
         * document_category테이블에 등록되어 있지 않은 경우 depth = 0 으로 하여 신규 생성
         **/
        function getWikiTreeList() {
            header("Content-Type: text/xml; charset=UTF-8");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            $oModuleModel = &getModel('module');

            $mid = Context::get('mid');

            $cache_file = sprintf('%sfiles/cache/wiki/%d.xml', _XE_PATH_,$this->module_srl);
            if($this->grant->write_document || !file_exists($cache_file)) {
                 FileHandler::writeFile($cache_file, $this->loadWikiTreeList($this->module_srl));
            }

            print FileHandler::readFile($cache_file);
            Context::close();
            exit();
        }

        function loadWikiTreeList($module_srl) {
            // 문서 목록
            $list = array();

            // 목록을 구함
            $args->module_srl = $module_srl;
            $output = executeQueryArray('wiki.getTreeList', $args);

            // 구해온 데이터가 없다면 빈 XML파일 return
            if($output->data) {
                // 데이트를 이용하여 XML 문서로 생성
                foreach($output->data as $node) {
                    $tree[(int)$node->parent_srl][$node->document_srl] = $node;
                }

                // XML 데이터를 생성
                $xml_doc = '<root>'.$this->getXmlTree($tree[0], $tree).'</root>';
            } else {
                $xml_doc = '<root></root>';
            }
            return $xml_doc;
        }

        function getXmlTree($source_node, $tree) {
            if(!$source_node) return;
            
            foreach($source_node as $document_srl => $node) {
                $child_buff = "";

                // 자식 노드의 데이터 가져옴
                if($document_srl && $tree[$document_srl]) $child_buff = $this->getXmlTree($tree[$document_srl], $tree);

                // 변수 정리
                $parent_srl = $node->parent_srl;

                $title = $node->title;
                $attribute = sprintf(
                        'node_srl="%d" parent_srl="%d" title="%s" ',
                        $document_srl,
                        $parent_srl,
                        $title
                );

                if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
                else $buff .=  sprintf('<node %s />', $attribute);
            }
            return $buff;
        }


        function getContributors($document_srl) {
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return array();

            $args->document_srl = $document_srl;
            $output = executeQueryArray("wiki.getContributors", $args);
            if($output->data) $list = $output->data;
            else $list = array();

            $item->member_srl = $oDocument->getMemberSrl();
            $item->nick_name = $oDocument->getNickName();
            $contributors[] = $item;
            for($i=0,$c=count($list);$i<$c;$i++) {
                unset($item);
                $item->member_srl = $list[$i]->member_srl;
                $item->nick_name = $list[$i]->nick_name;
                if($item->member_srl == $oDocument->getMemberSrl()) continue;
                $contributors[] = $item;
            }
            return $contributors;
        }
    }
?>
