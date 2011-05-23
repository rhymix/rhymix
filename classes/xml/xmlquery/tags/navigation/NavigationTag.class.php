<?php 
	require_once(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/IndexTag.class.php');
	
	class NavigationTag {
		var $dbParser;
		var $order;
		var $list_count;
		var $page_count;
		var $page;
		
		function NavigationTag($xml_navigation, $dbParser){
			$this->order = array();
            if($xml_navigation) {
                $order = $xml_navigation->index;
                if($order) {
                    if(!is_array($order)) $order = array($order);
                    foreach($order as $order_info) {
                        $this->order[] = new IndexTag($order_info, $dbParser);
                    }
                }

                $list_count = $xml_navigation->list_count->attrs;
                $this->list_count = $list_count;

                $page_count = $xml_navigation->page_count->attrs;
                $this->page_count = $page_count;

                $page = $xml_navigation->page->attrs;
                $this->page = $page ;
            }	
		}
		
		function getOrderByString(){
			$output = 'array(' . PHP_EOL;
			foreach($this->order as $order){
				$output .= $order->toString() . PHP_EOL . ',';
			}
			$output = substr($output, 0, -1);
			$output .= ')';	
			return $output;				
		}

		function getArguments(){
			$arguments = array();
			foreach($this->order as $order){
				$arguments = array_merge($order->getArguments(), $arguments);
			}
			return $arguments;
		}				
	}

?>