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
