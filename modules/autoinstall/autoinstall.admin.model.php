<?php
    /**
     * Model class of the autoinstall module
     * @author NHN (developers@xpressengine.com)
     **/
    class autoinstallAdminModel extends autoinstall {

		var $layout_category_srl = 18322954;
		var $mobile_layout_category_srl = 18994172;
		var $module_skin_category_srl = 18322943;
		var $module_mobile_skin_category_srl = 18994170;

		/**
		 * Pre process parameters
		 */
		function preProcParam(&$order_target, &$order_type, &$page)
		{
			$order_target_array = array('newest' => 1, 'download' => 1, 'popular' => 1);
			if(!isset($order_target_array[$order_target]))
			{
				$order_target = 'newest';
			}

			$order_type_array = array('asc' => 1, 'desc' => 1);
			if(!isset($order_type_array[$order_type]))
			{
				$order_type = 'desc';
			}

			$page = (int)$page;
			if($page < 1)
			{
				$page = 1;
			}
		}

		/**
		 * Return list of package that can have instance
		 */
		function getAutoinstallAdminMenuPackageList()
		{
			$search_keyword = Context::get('search_keyword');
			$order_target = Context::get('order_target');
			$order_type = Context::get('order_type');
			$page = Context::get('page');

			$this->preProcParam($order_target, $order_type, $page);
			$this->getPackageList('menu', $order_target, $order_type, $page, $search_keyword);
		}

		/**
		 * Return list of layout package
		 */
		function getAutoinstallAdminLayoutPackageList()
		{
			$search_keyword = Context::get('search_keyword');
			$order_target = Context::get('order_target');
			$order_type = Context::get('order_type');
			$page = Context::get('page');

			$type_array = array('M' => 1, 'P' => 1);
			$type = Context::get('type');
			if(!isset($type_array[$type]))
			{
				$type = 'P';
			}

			if($type == 'P')
			{
				$category_srl = $this->layout_category_srl;
			}
			else
			{
				$category_srl = $this->mobile_layout_category_srl;
			}

			$this->preProcParam($order_target, $order_type, $page);
			$this->getPackageList('layout', $order_target, $order_type, $page, $search_keyword, $category_srl);
		}

		/**
		 * Return list of module skin package
		 */
		function getAutoinstallAdminSkinPackageList()
		{
			Context::setRequestMethod('JSON');
			$search_keyword = Context::get('search_keyword');
			$order_target = Context::get('order_target');
			$order_type = Context::get('order_type');
			$page = Context::get('page');
			$parent_program = Context::get('parent_program');

			$type_array = array('M' => 1, 'P' => 1);
			$type = Context::get('type');
			if(!isset($type_array[$type]))
			{
				$type = 'P';
			}

			if($type == 'P')
			{
				$category_srl = $this->module_skin_category_srl;
			}
			else
			{
				$category_srl = $this->module_mobile_skin_category_srl;
			}

			$this->preProcParam($order_target, $order_type, $page);
			$this->getPackageList('skin', $order_target, $order_type, $page, $search_keyword, $category_srl, $parent_program);
		}

		/**
		 * Get Package List
		 */
		function getPackageList($type, $order_target = 'newest', $order_type = 'desc', $page = '1', $search_keyword = NULL, $category_srl = NULL, $parent_program = NULL)
		{
			if($type == 'menu')
			{
				$params["act"] = "getResourceapiMenuPackageList";
			}
			elseif($type == 'skin')
			{
				$params["act"] = "getResourceapiSkinPackageList";
				$params['parent_program'] = $parent_program;
			}
			else
			{
				$params["act"] = "getResourceapiPackagelist";
			}

			$oAdminView = getAdminView('autoinstall');
			$params["order_target"] = $order_target;
			$params["order_type"] = $order_type;
			$params["page"] = $page;

			if($category_srl)
			{
				$params["category_srl"] = $category_srl;
			}

			if($search_keyword)
			{
				$params["search_keyword"] = $search_keyword;
			}

			$xmlDoc = XmlGenerater::getXmlDoc($params);
			if($xmlDoc && $xmlDoc->response->packagelist->item)
			{
				$item_list = $oAdminView->rearranges($xmlDoc->response->packagelist->item);
				$this->add('item_list', $item_list);
				$array = array('total_count', 'total_page', 'cur_page', 'page_count', 'first_page', 'last_page');
				$page_nav = $oAdminView->rearrange($xmlDoc->response->page_navigation, $array);
				$page_navigation = new PageHandler($page_nav->total_count, $page_nav->total_page, $page_nav->cur_page, 5);
				$this->add('page_navigation', $page_navigation);
			}
		}
   }
?>
