<?php

	class IndexHint {
		var $index_name;
		var $index_hint_type;

		function IndexHint($index_name, $index_hint_type){
                    $this->index_name = $index_name;
                    $this->index_hint_type = $index_hint_type;
		}

                function getIndexName(){
                    return $this->index_name;
                }

                function getIndexHintType() {
                    return $this->index_hint_type;
                }
	}

?>