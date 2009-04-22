<?php
    /**
     * @class  boardWAP
     * @author haneul0318 (haneul0318@gmail.com)
     * @brief  board 모듈의 IPhone class
     **/

    class boardIPhone extends board {
        function procIPhone(&$oIPhone)
        {
            if(!$this->grant->list || $this->module_info->consultation == 'Y') return $oIPhone->setContent(Context::getLang('msg_not_permitted'));
            $act = Context::get('act');
            if(method_exists($this, $act))
            {
                $this->{$act}();
            }
            else
            {
                $document_srl = Context::get('document_srl');
                if($document_srl)
                    return $this->dispContent($document_srl);
                else
                    return $this->dispList();
            }
        }

        function dispContent($document_srl)
        {
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if($oDocument->isExists()) {
                // 권한 확인
                if(!$this->grant->view) return Context::getLang('msg_not_permitted');

                Context::setBrowserTitle($oDocument->getTitleText());
                Context::set('oDocument', $oDocument);
                $oTemplate = new TemplateHandler();
                $content = $oTemplate->compile($this->module_path.'tpl/iphone', "view_document");
                return $content;
            }
            else
            {
                return $this->dispList();
            }

        }

        function dispList()
        {
            if(!$this->grant->list || $this->module_info->consultation == 'Y') return Context::getLang('msg_not_permitted');
            $oDocumentModel = &getModel('document');
            $args->module_srl = $this->module_srl; 
            $args->page = Context::get('page');; 
            $args->list_count = 8;
            $args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
            $args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';
            $output = $oDocumentModel->getDocumentList($args, $this->except_notice);
            $document_list = $output->data;
            Context::set('document_list', $document_list);
            $page_navigation = $output->page_navigation;
            Context::set('page_navigation',$page_navigation);
            $oTemplate = new TemplateHandler();
            $content = $oTemplate->compile($this->module_path.'tpl/iphone', "list");
            return $content;
        }
    }
?>
