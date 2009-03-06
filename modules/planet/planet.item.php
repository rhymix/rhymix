<?php

    require_once("./modules/document/document.item.php");

    class planetItem extends documentItem {

        function planetItem($document_srl = 0) {
            parent::documentItem($document_srl);
        }

        function setPlanet($document_srl) {
            $this->document_srl = $document_srl;
            $this->_loadFromDB();
        }

        function _loadFromDB() {
            if(!$this->document_srl) return;
            parent::_loadFromDB();
        }

        function setAttribute($attribute) {
            parent::setAttribute($attribute);
        }


        function getPlanetPhotoSrc($width=96,$height=96) {
            $oPlanetModel = &getModel('planet');
            return $oPlanetModel->getPlanetPhotoSrc($this->get('module_srl'), $width, $height);
        }

        function getPlanetMid() {
            return $this->get('mid');
        }

        function getPlanetTitle() {
            return $this->get('planet_title');
        }

        function getUserID() {
            return parent::getUserID();
        }
        
        function getUserName() {
            return parent::getUserName();
        }
        
        function getNickName() {
            return parent::getNickName();
        }

        
        function getPostScript() {
            return $this->getExtraValue(20);
        }
        
		function getContent() {
            if(!$this->document_srl) return;
            return parent::getContent(false,true);
        }
        
        function getArrTags() {
            return $this->get('tag_list');
        }
		
		function getTextTags() {
            return $this->get('tags');
        }
        
        function getRegdate(){
        	return $this->get('regdate');
        }
        
        function getVotedCount(){
        	return $this->get('voted_count');
        	
        }

        function PopularTags($list_count = 100, $shuffle = false) {
            if(!$this->isHome()) $args->mid = $this->getMid();
            $args->list_count = $list_count;

            // 24시간 이내의 태그중에서 인기 태그를 추출
            $args->date = date("YmdHis", time()-60*60*24);

            $output = executeQueryArray('planet.getPlanetPopularTags',$args);
            if(!$output->toBool() || !$output->data) return array();

            $tags = array();
            $max = 0;
            $min = 99999999;
            foreach($output->data as $key => $val) {
                $tag = $val->tag;
                $count = $val->count;
                if($max < $count) $max = $count;
                if($min > $count) $min = $count;
                $tags[] = $val;
            }

            if($shuffle) {
                $mid2 = $min+(int)(($max-$min)/2);
                $mid1 = $mid2+(int)(($max-$mid2)/2);
                $mid3 = $min+(int)(($mid2-$min)/2);

                $output = null;

                foreach($tags as $key => $item) {
                    if($item->count > $mid1) $rank = 1;
                    elseif($item->count > $mid2) $rank = 2;
                    elseif($item->count > $mid3) $rank = 3;
                    else $rank= 4;

                    $tags[$key]->rank = $rank;
                }
                shuffle($tags);
            }

            return $tags;
        }
    }
?>
