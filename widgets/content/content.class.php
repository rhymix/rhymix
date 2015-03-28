<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class content
 * @author NAVER (developers@xpressengine.com)
 * @brief widget to display content
 * @version 0.1
 */
class content extends WidgetHandler
{
	/**
	 * @brief Widget handler
	 *
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */

	function proc($args)
	{
		// Targets to sort
		if(!in_array($args->order_target, array('regdate','update_order'))) $args->order_target = 'regdate';
		// Sort order
		if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = 'asc';
		// Pages
		$args->page_count = (int)$args->page_count;
		if(!$args->page_count) $args->page_count = 1;
		// The number of displayed lists
		$args->list_count = (int)$args->list_count;
		if(!$args->list_count) $args->list_count = 5;
		// The number of thumbnail columns
		$args->cols_list_count = (int)$args->cols_list_count;
		if(!$args->cols_list_count) $args->cols_list_count = 5;
		// Cut the length of the title
		if(!$args->subject_cut_size) $args->subject_cut_size = 0;
		// Cut the length of contents
		if(!$args->content_cut_size) $args->content_cut_size = 100;
		// Cut the length of nickname
		if(!$args->nickname_cut_size) $args->nickname_cut_size = 0;
		// Display time of the latest post
		if(!$args->duration_new) $args->duration_new = 12;
		// How to create thumbnails
		if(!$args->thumbnail_type) $args->thumbnail_type = 'crop';
		// Horizontal size of thumbnails
		if(!$args->thumbnail_width) $args->thumbnail_width = 100;
		// Vertical size of thumbnails
		if(!$args->thumbnail_height) $args->thumbnail_height = 75;
		// Viewing options
		$args->option_view_arr = explode(',',$args->option_view);
		// markup options
		if(!$args->markup_type) $args->markup_type = 'table';
		// Set variables used internally
		$oModuleModel = getModel('module');
		$module_srls = $args->modules_info = $args->module_srls_info = $args->mid_lists = array();
		$site_module_info = Context::get('site_module_info');
		// List URLs if a type is RSS
		if($args->content_type == 'rss')
		{
			$args->rss_urls = array();
			$rss_urls = array_unique(array($args->rss_url0,$args->rss_url1,$args->rss_url2,$args->rss_url3,$args->rss_url4));
			for($i=0,$c=count($rss_urls);$i<$c;$i++)
			{
				if($rss_urls[$i]) $args->rss_urls[] = $rss_urls[$i];
			}
			// Get module information after listing module_srls if the module is not RSS
		}
		else
		{
			$obj = new stdClass();
			// Apply to all modules in the site if a target module is not specified
			if(!$args->module_srls)
			{
				$obj->site_srl = (int)$site_module_info->site_srl;
				$output = executeQueryArray('widgets.content.getMids', $obj);
				if($output->data)
				{
					foreach($output->data as $key => $val)
					{
						$args->modules_info[$val->mid] = $val;
						$args->module_srls_info[$val->module_srl] = $val;
						$args->mid_lists[$val->module_srl] = $val->mid;
						$module_srls[] = $val->module_srl;
					}
				}

				$args->modules_info = $oModuleModel->getMidList($obj);
				// Apply to the module only if a target module is specified
			}
			else
			{
				$obj->module_srls = $args->module_srls;
				$output = executeQueryArray('widgets.content.getMids', $obj);
				if($output->data)
				{
					foreach($output->data as $key => $val)
					{
						$args->modules_info[$val->mid] = $val;
						$args->module_srls_info[$val->module_srl] = $val;
						$module_srls[] = $val->module_srl;
					}
					$idx = explode(',',$args->module_srls);
					for($i=0,$c=count($idx);$i<$c;$i++)
					{
						$srl = $idx[$i];
						if(!$args->module_srls_info[$srl]) continue;
						$args->mid_lists[$srl] = $args->module_srls_info[$srl]->mid;
					}
				}
			}
			// Exit if no module is found
			if(!count($args->modules_info)) return Context::get('msg_not_founded');
			$args->module_srl = implode(',',$module_srls);
		}

		/**
		 * Method is separately made because content extraction, articles, comments, trackbacks, RSS and other elements exist
		 */
		// tab type
		if($args->tab_type == 'none' || $args->tab_type == '')
		{
			switch($args->content_type)
			{
				case 'comment':
					$content_items = $this->_getCommentItems($args);
					break;
				case 'image':
					$content_items = $this->_getImageItems($args);
					break;
				case 'rss':
					$content_items = $this->getRssItems($args);
					break;
				case 'trackback':
					$content_items = $this->_getTrackbackItems($args);
					break;
				default:
					$content_items = $this->_getDocumentItems($args);
					break;
			}
			// If not a tab type
		}
		else
		{
			$content_items = array();

			switch($args->content_type)
			{
				case 'comment':
					foreach($args->mid_lists as $module_srl => $mid)
					{
						$args->module_srl = $module_srl;
						$content_items[$module_srl] = $this->_getCommentItems($args);
					}
					break;
				case 'image':
					foreach($args->mid_lists as $module_srl => $mid)
					{
						$args->module_srl = $module_srl;
						$content_items[$module_srl] = $this->_getImageItems($args);
					}
					break;
				case 'rss':
					$content_items = $this->getRssItems($args);
					break;
				case 'trackback':
					foreach($args->mid_lists as $module_srl => $mid)
					{
						$args->module_srl = $module_srl;
						$content_items[$module_srl] = $this->_getTrackbackItems($args);
					}
					break;
				default:
					foreach($args->mid_lists as $module_srl => $mid)
					{
						$args->module_srl = $module_srl;
						$content_items[$module_srl] = $this->_getDocumentItems($args);
					}
					break;
			}
		}

		$output = $this->_compile($args,$content_items);
		return $output;
	}

	/**
	 * @brief Get a list of comments and return contentItem
	 */
	function _getCommentItems($args)
	{
		// List variables to use CommentModel::getCommentList()
		$obj = new stdClass();
		$obj->module_srl = $args->module_srl;
		$obj->sort_index = $args->order_target;
		$obj->list_count = $args->list_count * $args->page_count;
		// Get model object of the comment module and execute getCommentList() method
		$oCommentModel = getModel('comment');
		$output = $oCommentModel->getNewestCommentList($obj);

		$content_items = array();

		if(!count($output)) return;

		foreach($output as $key => $oComment)
		{
			$attribute = $oComment->getObjectVars();
			$title = $oComment->getSummary($args->content_cut_size);
			$thumbnail = $oComment->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);
			$url = sprintf("%s#comment_%s",getUrl('','document_srl',$oComment->get('document_srl')),$oComment->get('comment_srl'));

			$attribute->mid = $args->mid_lists[$attribute->module_srl];
			$browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
			$domain = $args->module_srls_info[$attribute->module_srl]->domain;

			$content_item = new contentItem($browser_title);
			$content_item->adds($attribute);
			$content_item->setTitle($title);
			$content_item->setThumbnail($thumbnail);
			$content_item->setLink($url);
			$content_item->setDomain($domain);
			$content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
			$content_items[] = $content_item;
		}
		return $content_items;
	}

	function _getDocumentItems($args)
	{
		// Get model object from the document module
		$oDocumentModel = getModel('document');
		// Get categories
		$obj = new stdClass();
		$obj->module_srl = $args->module_srl;
		$output = executeQueryArray('widgets.content.getCategories',$obj);
		if($output->toBool() && $output->data)
		{
			foreach($output->data as $key => $val)
			{
				$category_lists[$val->module_srl][$val->category_srl] = $val;
			}
		}
		// Get a list of documents
		$obj->module_srl = $args->module_srl;
		$obj->category_srl = $args->category_srl;
		$obj->sort_index = $args->order_target;
		if($args->order_target == 'list_order' || $args->order_target == 'update_order')
		{
			$obj->order_type = $args->order_type=="desc"?"asc":"desc";
		}
		else
		{
			$obj->order_type = $args->order_type=="desc"?"desc":"asc";
		}
		$obj->list_count = $args->list_count * $args->page_count;
		$obj->statusList = array('PUBLIC');
		$output = executeQueryArray('widgets.content.getNewestDocuments', $obj);
		if(!$output->toBool() || !$output->data) return;
		// If the result exists, make each document as an object
		$content_items = array();
		$first_thumbnail_idx = -1;
		if(count($output->data))
		{
			foreach($output->data as $key => $attribute)
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
				$GLOBALS['XE_DOCUMENT_LIST'][$oDocument->document_srl] = $oDocument;
				$document_srls[] = $oDocument->document_srl;
			}
			$oDocumentModel->setToAllDocumentExtraVars();

			for($i=0,$c=count($document_srls);$i<$c;$i++)
			{
				$oDocument = $GLOBALS['XE_DOCUMENT_LIST'][$document_srls[$i]];
				$document_srl = $oDocument->document_srl;
				$module_srl = $oDocument->get('module_srl');
				$category_srl = $oDocument->get('category_srl');
				$thumbnail = $oDocument->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);

				$content_item = new contentItem( $args->module_srls_info[$module_srl]->browser_title );
				$content_item->adds($oDocument->getObjectVars());
				$content_item->add('original_content', $oDocument->get('content'));
				$content_item->setTitle(htmlspecialchars($oDocument->getTitleText()));
				$content_item->setCategory( $category_lists[$module_srl][$category_srl]->title );
				$content_item->setDomain( $args->module_srls_info[$module_srl]->domain );
				$content_item->setContent($oDocument->getSummary($args->content_cut_size));
				$content_item->setLink( getSiteUrl($domain,'','document_srl',$document_srl) );
				$content_item->setThumbnail($thumbnail);
				$content_item->setExtraImages($oDocument->printExtraImages($args->duration_new * 60 * 60));
				$content_item->add('mid', $args->mid_lists[$module_srl]);
				if($first_thumbnail_idx==-1 && $thumbnail) $first_thumbnail_idx = $i;
				$content_items[] = $content_item;
			}

			$content_items[0]->setFirstThumbnailIdx($first_thumbnail_idx);
		}

		$oSecurity = new Security($content_items);
		$oSecurity->encodeHTML('..variables.content', '..variables.user_name', '..variables.nick_name');

		return $content_items;
	}

	function _getImageItems($args)
	{
		$oDocumentModel = getModel('document');

		$obj->module_srls = $obj->module_srl = $args->module_srl;
		$obj->direct_download = 'Y';
		$obj->isvalid = 'Y';
		// Get categories
		$output = executeQueryArray('widgets.content.getCategories',$obj);
		if($output->toBool() && $output->data)
		{
			foreach($output->data as $key => $val)
			{
				$category_lists[$val->module_srl][$val->category_srl] = $val;
			}
		}
		// Get a file list in each document on the module
		$obj->list_count = $args->list_count * $args->page_count;
		$files_output = executeQueryArray("file.getOneFileInDocument", $obj);
		$files_count = count($files_output->data);
		if(!$files_count) return;

		$content_items = array();

		for($i=0;$i<$files_count;$i++) $document_srl_list[] = $files_output->data[$i]->document_srl;

		$tmp_document_list = $oDocumentModel->getDocuments($document_srl_list);

		if(!count($tmp_document_list)) return;

		foreach($tmp_document_list as $oDocument)
		{
			$attribute = $oDocument->getObjectVars();
			$browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
			$domain = $args->module_srls_info[$attribute->module_srl]->domain;
			$category = $category_lists[$attribute->module_srl]->text;
			$content = $oDocument->getSummary($args->content_cut_size);
			$url = sprintf("%s#%s",$oDocument->getPermanentUrl() ,$oDocument->getCommentCount());
			$thumbnail = $oDocument->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);
			$extra_images = $oDocument->printExtraImages($args->duration_new);

			$content_item = new contentItem($browser_title);
			$content_item->adds($attribute);
			$content_item->setCategory($category);
			$content_item->setContent($content);
			$content_item->setLink($url);
			$content_item->setThumbnail($thumbnail);
			$content_item->setExtraImages($extra_images);
			$content_item->setDomain($domain);
			$content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
			$content_items[] = $content_item;
		}

		return $content_items;
	}

	function getRssItems($args)
	{
		$content_items = array();
		$args->mid_lists = array();

		foreach($args->rss_urls as $key => $rss)
		{
			$args->rss_url = $rss;
			$content_item = $this->_getRssItems($args);
			if(count($content_item) > 0)
			{
				$browser_title = $content_item[0]->getBrowserTitle();
				$args->mid_lists[] = $browser_title;
				$content_items[] = $content_item;
			}
		}
		// If it is not a tab type
		if($args->tab_type == 'none' || $args->tab_type == '')
		{
			$items = array();
			foreach($content_items as $key => $val)
			{
				foreach($val as $k => $v)
				{
					$date = $v->get('regdate');
					$i=0;
					while(array_key_exists(sprintf('%s%02d',$date,$i), $items)) $i++;
					$items[sprintf('%s%02d',$date,$i)] = $v;
				}
			}
			if($args->order_type =='asc') ksort($items);
			else krsort($items);
			$content_items = array_slice(array_values($items),0,$args->list_count*$args->page_count);
			// Tab Type
		}
		else
		{
			foreach($content_items as $key=> $content_item_list)
			{
				$items = array();
				foreach($content_item_list as $k => $content_item)
				{
					$date = $content_item->get('regdate');
					$i=0;
					while(array_key_exists(sprintf('%s%02d',$date,$i), $items)) $i++;
					$items[sprintf('%s%02d',$date,$i)] = $content_item;
				}
				if($args->order_type =='asc') ksort($items);
				else krsort($items);

				$content_items[$key] = array_values($items);
			}
		}
		return $content_items;
	}

	function _getRssBody($value)
	{
		if(!$value || is_string($value)) return $value;
		if(is_object($value)) $value = get_object_vars($value);
		$body = null;
		if(!count($value)) return;
		foreach($value as $key => $val)
		{
			if($key == 'body')
			{
				$body = $val;
				continue;
			}
			if(is_object($val)||is_array($val)) $body = $this->_getRssBody($val);
			if($body !== null) return $body;
		}
		return $body;
	}

	function _getSummary($content, $str_size = 50)
	{
		$content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);
		// Replace tags such as </p> , </div> , </li> and others to a whitespace
		$content = str_replace(array('</p>', '</div>', '</li>'), ' ', $content);
		// Remove Tag
		$content = preg_replace('!<([^>]*?)>!is','', $content);
		// Replace tags to <, >, " and whitespace
		$content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);
		// Delete  a series of whitespaces
		$content = preg_replace('/ ( +)/is', ' ', $content);
		// Truncate string
		$content = trim(cut_str($content, $str_size, $tail));
		// Replace back <, >, " to the original tags
		$content = str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'), $content);
		// Fixed to a newline bug for consecutive sets of English letters
		$content = preg_replace('/([a-z0-9\+:\/\.\~,\|\!\@\#\$\%\^\&\*\(\)\_]){20}/is',"$0-",$content);
		return $content; 
	}

	/**
	 * @brief function to receive contents from rss url
	 * For Tistory blog in Korea, the original RSS url has location header without contents. Fixed to work as same as rss_reader widget.
	 */
	function requestFeedContents($rss_url)
	{
		$rss_url = str_replace('&amp;','&',Context::convertEncodingStr($rss_url));
		return FileHandler::getRemoteResource($rss_url, null, 3, 'GET', 'application/xml');
	}

	function _getRssItems($args)
	{
		// Date Format
		$DATE_FORMAT = $args->date_format ? $args->date_format : "Y-m-d H:i:s";

		$buff = $this->requestFeedContents($args->rss_url);

		$encoding = preg_match("/<\?xml.*encoding=\"(.+)\".*\?>/i", $buff, $matches);
		if($encoding && stripos($matches[1], "UTF-8") === FALSE) $buff = Context::convertEncodingStr($buff);

		$buff = preg_replace("/<\?xml.*\?>/i", "", $buff);

		$oXmlParser = new XmlParser();
		$xml_doc = $oXmlParser->parse($buff);
		if($xml_doc->rss)
		{
			$rss->title = $xml_doc->rss->channel->title->body;
			$rss->link = $xml_doc->rss->channel->link->body;

			$items = $xml_doc->rss->channel->item;

			if(!$items) return;
			if($items && !is_array($items)) $items = array($items);

			$content_items = array();

			foreach ($items as $key => $value)
			{
				if($key >= $args->list_count * $args->page_count) break;
				unset($item);

				foreach($value as $key2 => $value2)
				{
					if(is_array($value2)) $value2 = array_shift($value2);
					$item->{$key2} = $this->_getRssBody($value2);
				}

				$content_item = new contentItem($rss->title);
				$content_item->setContentsLink($rss->link);
				$content_item->setTitle($item->title);
				$content_item->setNickName(max($item->author,$item->{'dc:creator'}));
				//$content_item->setCategory($item->category);
				$item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
				$content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
				$content_item->setThumbnail($this->_getRssThumbnail($item->description));
				$content_item->setLink($item->link);
				$date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
				$content_item->setRegdate($date);

				$content_items[] = $content_item;
			}
		}
		else if($xml_doc->{'rdf:rdf'})
		{
			// rss1.0 supported (XE's XML is case-insensitive because XML parser converts all to small letters. Fixed by misol
			$rss->title = $xml_doc->{'rdf:rdf'}->channel->title->body;
			$rss->link = $xml_doc->{'rdf:rdf'}->channel->link->body;

			$items = $xml_doc->{'rdf:rdf'}->item;

			if(!$items) return;
			if($items && !is_array($items)) $items = array($items);

			$content_items = array();

			foreach ($items as $key => $value)
			{
				if($key >= $args->list_count * $args->page_count) break;
				unset($item);

				foreach($value as $key2 => $value2)
				{
					if(is_array($value2)) $value2 = array_shift($value2);
					$item->{$key2} = $this->_getRssBody($value2);
				}

				$content_item = new contentItem($rss->title);
				$content_item->setContentsLink($rss->link);
				$content_item->setTitle($item->title);
				$content_item->setNickName(max($item->author,$item->{'dc:creator'}));
				//$content_item->setCategory($item->category);
				$item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
				$content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
				$content_item->setThumbnail($this->_getRssThumbnail($item->description));
				$content_item->setLink($item->link);
				$date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
				$content_item->setRegdate($date);

				$content_items[] = $content_item;
			}
		}
		else if($xml_doc->feed && $xml_doc->feed->attrs->xmlns == 'http://www.w3.org/2005/Atom')
		{
			// Atom 1.0 spec supported by misol
			$rss->title = $xml_doc->feed->title->body;
			$links = $xml_doc->feed->link;
			if(is_array($links))
			{
				foreach ($links as $value)
				{
					if($value->attrs->rel == 'alternate')
					{
						$rss->link = $value->attrs->href;
						break;
					}
				}
			}
			else if($links->attrs->rel == 'alternate') $rss->link = $links->attrs->href;

			$items = $xml_doc->feed->entry;

			if(!$items) return;
			if($items && !is_array($items)) $items = array($items);

			$content_items = array();

			foreach ($items as $key => $value)
			{
				if($key >= $args->list_count * $args->page_count) break;
				unset($item);

				foreach($value as $key2 => $value2)
				{
					if(is_array($value2)) $value2 = array_shift($value2);
					$item->{$key2} = $this->_getRssBody($value2);
				}

				$content_item = new contentItem($rss->title);
				$links = $value->link;
				if(is_array($links))
				{
					foreach ($links as $val)
					{
						if($val->attrs->rel == 'alternate')
						{
							$item->link = $val->attrs->href;
							break;
						}
					}
				}
				else if($links->attrs->rel == 'alternate') $item->link = $links->attrs->href;

				$content_item->setContentsLink($rss->link);
				if($item->title)
				{
					if(stripos($value->title->attrs->type, "html") === FALSE) $item->title = $value->title->body;
				}
				$content_item->setTitle($item->title);
				$content_item->setNickName(max($item->author,$item->{'dc:creator'}));
				$content_item->setAuthorSite($value->author->uri->body);

				//$content_item->setCategory($item->category);
				$item->description = ($item->content) ? $item->content : $item->description = $item->summary;
				$item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);

				if(($item->content && stripos($value->content->attrs->type, "html") === FALSE) || (!$item->content && stripos($value->summary->attrs->type, "html") === FALSE))
				{
					$item->description = htmlspecialchars($item->description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

				}

				$content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
				$content_item->setThumbnail($this->_getRssThumbnail($item->description));
				$content_item->setLink($item->link);
				$date = date('YmdHis', strtotime(max($item->published,$item->updated,$item->{'dc:date'})));
				$content_item->setRegdate($date);

				$content_items[] = $content_item;
			}
		}
		return $content_items;
	}

	function _getRssThumbnail($content)
	{
		@preg_match('@<img[^>]+src\s*=\s*(?:"(.+)"|\'(.+)\'|([^\s>(?:/>)]+))@', $content, $matches);

		if($matches[1])
		{
			return $matches[1];
		}
		elseif($matches[2])
		{
			return $matches[2];
		}
		elseif($matches[3])
		{
			return $matches[3];
		}
		else
		{
			return NULL;
		}
	}

	function _getTrackbackItems($args)
	{
		$oTrackbackModel = getModel('trackback');
		if(!$oTrackbackModel)
		{
			return;
		}

		$obj = new stdClass;
		// Get categories
		$output = executeQueryArray('widgets.content.getCategories',$obj);
		if($output->toBool() && $output->data)
		{
			foreach($output->data as $key => $val)
			{
				$category_lists[$val->module_srl][$val->category_srl] = $val;
			}
		}

		$obj->module_srl = $args->module_srl;
		$obj->sort_index = $args->order_target;
		$obj->list_count = $args->list_count * $args->page_count;

		// Get model object from the trackback module and execute getTrackbackList() method
		$output = $oTrackbackModel->getNewestTrackbackList($obj);
		// If an error occurs, just ignore it.
		if(!$output->toBool() || !$output->data) return;
		// If the result exists, make each document as an object
		$content_items = array();
		foreach($output->data as $key => $item)
		{
			$domain = $args->module_srls_info[$item->module_srl]->domain;
			$category = $category_lists[$item->module_srl]->text;
			$url = getSiteUrl($domain,'','document_srl',$item->document_srl);
			$browser_title = $args->module_srls_info[$item->module_srl]->browser_title;

			$content_item = new contentItem($browser_title);
			$content_item->adds($item);
			$content_item->setTitle($item->title);
			$content_item->setCategory($category);
			$content_item->setNickName($item->blog_name);
			$content_item->setContent($item->excerpt);  ///<<
			$content_item->setDomain($domain);  ///<<
			$content_item->setLink($url);
			$content_item->add('mid', $args->mid_lists[$item->module_srl]);
			$content_item->setRegdate($item->regdate);
			$content_items[] = $content_item;
		}
		return $content_items;
	}

	function _compile($args,$content_items)
	{
		$oTemplate = &TemplateHandler::getInstance();
		// Set variables for widget
		$widget_info = new stdClass();
		$widget_info->modules_info = $args->modules_info;
		$widget_info->option_view_arr = $args->option_view_arr;
		$widget_info->list_count = $args->list_count;
		$widget_info->page_count = $args->page_count;
		$widget_info->subject_cut_size = $args->subject_cut_size;
		$widget_info->content_cut_size = $args->content_cut_size;
		$widget_info->nickname_cut_size = $args->nickname_cut_size;
		$widget_info->new_window = $args->new_window;

		$widget_info->duration_new = $args->duration_new * 60*60;
		$widget_info->thumbnail_type = $args->thumbnail_type;
		$widget_info->thumbnail_width = $args->thumbnail_width;
		$widget_info->thumbnail_height = $args->thumbnail_height;
		$widget_info->cols_list_count = $args->cols_list_count;
		$widget_info->mid_lists = $args->mid_lists;

		$widget_info->show_browser_title = $args->show_browser_title;
		$widget_info->show_category = $args->show_category;
		$widget_info->show_comment_count = $args->show_comment_count;
		$widget_info->show_trackback_count = $args->show_trackback_count;
		$widget_info->show_icon = $args->show_icon;

		$widget_info->list_type = $args->list_type;
		$widget_info->tab_type = $args->tab_type;

		$widget_info->markup_type = $args->markup_type;
		// If it is a tab type, list up tab items and change key value(module_srl) to index 
		if($args->tab_type != 'none' && $args->tab_type)
		{
			$tab = array();
			foreach($args->mid_lists as $module_srl => $mid)
			{
				if(!is_array($content_items[$module_srl]) || !count($content_items[$module_srl])) continue;

				unset($tab_item);
				$tab_item = new stdClass();
				$tab_item->title = $content_items[$module_srl][0]->getBrowserTitle();
				$tab_item->content_items = $content_items[$module_srl];
				$tab_item->domain = $content_items[$module_srl][0]->getDomain();
				$tab_item->url = $content_items[$module_srl][0]->getContentsLink();
				if(!$tab_item->url) $tab_item->url = getSiteUrl($tab_item->domain, '','mid',$mid);
				$tab[] = $tab_item;
			}
			$widget_info->tab = $tab;
		}
		else
		{
			$widget_info->content_items = $content_items;
		}
		unset($args->option_view_arr);
		unset($args->modules_info);

		Context::set('colorset', $args->colorset);
		Context::set('widget_info', $widget_info);

		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		return $oTemplate->compile($tpl_path, "content");
	}
}

class contentItem extends Object
{
	var $browser_title = null;
	var $has_first_thumbnail_idx = false;
	var $first_thumbnail_idx = null;
	var $contents_link = null;
	var $domain = null;

	function contentItem($browser_title='')
	{
		$this->browser_title = $browser_title;
	}
	function setContentsLink($link)
	{
		$this->contents_link = $link;
	}
	function setFirstThumbnailIdx($first_thumbnail_idx)
	{
		if(is_null($this->first_thumbnail) && $first_thumbnail_idx>-1)
		{
			$this->has_first_thumbnail_idx = true;
			$this->first_thumbnail_idx= $first_thumbnail_idx;
		}
	}
	function setExtraImages($extra_images)
	{
		$this->add('extra_images',$extra_images);
	}
	function setDomain($domain)
	{
		static $default_domain = null;
		if(!$domain)
		{
			if(is_null($default_domain)) $default_domain = Context::getDefaultUrl();
			$domain = $default_domain;
		}
		$this->domain = $domain;
	}
	function setLink($url)
	{
		$this->add('url', strip_tags($url));
	}
	function setTitle($title)
	{
		$this->add('title', strip_tags($title));
	}
	function setThumbnail($thumbnail)
	{
		$this->add('thumbnail', $thumbnail);
	}
	function setContent($content)
	{
		$this->add('content', removeHackTag($content));
	}
	function setRegdate($regdate)
	{
		$this->add('regdate', strip_tags($regdate));
	}
	function setNickName($nick_name)
	{
		$this->add('nick_name', strip_tags($nick_name));
	}
	// Save author's homepage url. By misol
	function setAuthorSite($site_url)
	{
		$this->add('author_site', strip_tags($site_url));
	}
	function setCategory($category)
	{
		$this->add('category', strip_tags($category));
	}
	function getBrowserTitle()
	{
		return $this->browser_title;
	}
	function getDomain()
	{
		return $this->domain;
	}
	function getContentsLink()
	{
		return $this->contents_link;
	}

	function getFirstThumbnailIdx()
	{
		return $this->first_thumbnail_idx;
	}

	function getLink()
	{
		return $this->get('url');
	}
	function getModuleSrl()
	{
		return $this->get('module_srl');
	}
	function getTitle($cut_size = 0, $tail='...')
	{
		$title = strip_tags($this->get('title'));

		if($cut_size) $title = cut_str($title, $cut_size, $tail);

		$attrs = array();
		if($this->get('title_bold') == 'Y') $attrs[] = 'font-weight:bold';
		if($this->get('title_color') && $this->get('title_color') != 'N') $attrs[] = 'color:#'.$this->get('title_color');

		if(count($attrs)) $title = sprintf("<span style=\"%s\">%s</span>", implode(';', $attrs), htmlspecialchars($title));

		return $title;
	}
	function getContent()
	{
		return $this->get('content');
	}
	function getCategory()
	{
		return $this->get('category');
	}
	function getNickName($cut_size = 0, $tail='...')
	{
		if($cut_size) $nick_name = cut_str($this->get('nick_name'), $cut_size, $tail);
		else $nick_name = $this->get('nick_name');

		return $nick_name;
	}
	function getAuthorSite()
	{
		return $this->get('author_site');
	}
	function getCommentCount()
	{
		$comment_count = $this->get('comment_count');
		return $comment_count>0 ? $comment_count : '';
	}
	function getTrackbackCount()
	{
		$trackback_count = $this->get('trackback_count');
		return $trackback_count>0 ? $trackback_count : '';
	}
	function getRegdate($format = 'Y.m.d H:i:s')
	{
		return zdate($this->get('regdate'), $format);
	}
	function printExtraImages()
	{
		return $this->get('extra_images');
	}
	function haveFirstThumbnail()
	{
		return $this->has_first_thumbnail_idx;
	}
	function getThumbnail()
	{
		return $this->get('thumbnail');
	}
	function getMemberSrl() 
	{
		return $this->get('member_srl');
	}
}
/* End of file content.class.php */
/* Location: ./widgets/content/content.class.php */
