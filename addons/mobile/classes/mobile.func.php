<?php
    function getListedItems($menu, &$listed_items, &$mid_list) {
        if(!count($menu)) return;
        foreach($menu as $node_srl => $item) {
            if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $item['url'])) {
                $mid = $item['mid'] = $item['url'];
                $mid_list[$node_srl] = $mid;
            } else {
                $mid = $item['mid'] = null;
            }

            $listed_items[$mid] = $item;
            getListedItems($item['list'], $listed_items, $mid_list);
        }
    }

?>
