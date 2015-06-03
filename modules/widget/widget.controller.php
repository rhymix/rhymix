<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widgetController
 * @author NAVER (developers@xpressengine.com)
 * @brief Controller class for widget modules
 */
class widgetController extends widget
{
	// The results are not widget modify/delete and where to use the flag for
	// layout_javascript_mode include all the results into the javascript mode Sikkim
	var $javascript_mode = false;
	var $layout_javascript_mode = false;

	// Where the cache files are created widget
	var $cache_path = './files/cache/widget_cache/';

	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Selected photos - the return of the skin-color three
	 */
	function procWidgetGetColorsetList()
	{
		$widget = Context::get('selected_widget');
		$skin = Context::get('skin');

		$path = sprintf('./widgets/%s/', $widget);
		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($path, $skin);

		for($i=0;$i<count($skin_info->colorset);$i++)
		{
			$colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
			$colorset_list[] = $colorset;
		}

		if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
		$this->add('colorset_list', $colorsets);
	}

	/**
	 * @brief Return the generated code of the widget
	 */
	function procWidgetGenerateCode()
	{
		$widget = Context::get('selected_widget');
		if(!$widget) return new Object(-1,'msg_invalid_request');
		if(!Context::get('skin')) return new Object(-1,Context::getLang('msg_widget_skin_is_null'));

		$attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);

		$widget_code = sprintf('<img class="zbxe_widget_output" widget="%s" %s />', $widget, implode(' ',$attribute));
		// Code output
		$this->add('widget_code', $widget_code);
	}

	/**
	 * @brief Edit page request for the creation of the widget code
	 */
	function procWidgetGenerateCodeInPage()
	{
		$widget = Context::get('selected_widget');
		if(!$widget) return new Object(-1,'msg_invalid_request');

		if(!in_array($widget,array('widgetBox','widgetContent')) && !Context::get('skin')) return new Object(-1,Context::getLang('msg_widget_skin_is_null'));

		$attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);
		// Wanted results
		$widget_code = $this->execute($widget, $vars, true, false);

		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($widget_code);

		$this->add('widget_code', $widget_code);
	}

	/**
	 * @brief Upload widget styles
	 */
	function procWidgetStyleExtraImageUpload()
	{
		$attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);

		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout.html');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("top_refresh.html");
	}

	/**
	 * @brief Add content widget
	 */
	function procWidgetInsertDocument()
	{
		// Variable Wanted
		$module_srl = Context::get('module_srl');
		$document_srl = Context::get('document_srl');
		$content = Context::get('content');
		$editor_sequence = Context::get('editor_sequence');

		$err = 0;
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($module_srl);
		if(!$layout_info || $layout_info->type != 'faceoff') $err++;
		// Destination Information Wanted page module
		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		if(!$page_info->module_srl || $page_info->module != 'page') $err++;

		if($err > 1) return new Object(-1,'msg_invalid_request');
		// Check permissions
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$user_group = $logged_info->group_list;
		$is_admin = false;
		if(count($user_group)&&count($page_info->grants['manager']))
		{
			$manager_group = $page_info->grants['manager'];
			foreach($user_group as $group_srl => $group_info)
			{
				if(in_array($group_srl, $manager_group)) $is_admin = true;
			}
		}
		if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_info->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');
		// Enter post
		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');

		$obj = new stdClass();
		$obj->module_srl = $module_srl;
		$obj->content = $content;
		$obj->document_srl = $document_srl;

		$oDocument = $oDocumentModel->getDocument($obj->document_srl, true);
		if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl)
		{
			$output = $oDocumentController->updateDocument($oDocument, $obj);
		}
		else
		{
			$output = $oDocumentController->insertDocument($obj);
			$obj->document_srl = $output->get('document_srl');
		}
		// Stop when an error occurs
		if(!$output->toBool()) return $output;
		// Return results
		$this->add('document_srl', $obj->document_srl);
	}

	/**
	 * @brief Copy the content widget
	 */
	function procWidgetCopyDocument()
	{
		// Variable Wanted
		$document_srl = Context::get('document_srl');

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		$oDocumentAdminController = getAdminController('document');

		$oDocument = $oDocumentModel->getDocument($document_srl, true);
		if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');
		$module_srl = $oDocument->get('module_srl');
		// Destination Information Wanted page module
		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		if(!$page_info->module_srl || $page_info->module != 'page') return new Object(-1,'msg_invalid_request');
		// Check permissions
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$user_group = $logged_info->group_list;
		$is_admin = false;
		if(count($user_group)&&count($page_info->grants['manager']))
		{
			$manager_group = $page_info->grants['manager'];
			foreach($user_group as $group_srl => $group_info)
			{
				if(in_array($group_srl, $manager_group)) $is_admin = true;
			}
		}
		if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_info->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');

		$output = $oDocumentAdminController->copyDocumentModule(array($oDocument->get('document_srl')), $oDocument->get('module_srl'),0);
		if(!$output->toBool()) return $output;
		// Return results
		$copied_srls = $output->get('copied_srls');
		$this->add('document_srl', $copied_srls[$oDocument->get('document_srl')]);
	}

	/**
	 * @brief Deleting widgets
	 */
	function procWidgetDeleteDocument()
	{
		// Variable Wanted
		$document_srl = Context::get('document_srl');

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');

		$oDocument = $oDocumentModel->getDocument($document_srl, true);
		if(!$oDocument->isExists()) return new Object();
		$module_srl = $oDocument->get('module_srl');
		// Destination Information Wanted page module
		$oModuleModel = getModel('module');
		$page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$page_info->module_srl || $page_info->module != 'page') return new Object(-1,'msg_invalid_request');
		// Check permissions
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$user_group = $logged_info->group_list;
		$is_admin = false;
		if(count($user_group)&&count($page_info->grants['manager']))
		{
			$manager_group = $page_info->grants['manager'];
			foreach($user_group as $group_srl => $group_info)
			{
				if(in_array($group_srl, $manager_group)) $is_admin = true;
			}
		}
		if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_info->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');

		$output = $oDocumentController->deleteDocument($oDocument->get('document_srl'), true);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief Modify the code in Javascript widget/Javascript edit mode for dragging and converted to
	 */
	function setWidgetCodeInJavascriptMode()
	{
		$this->layout_javascript_mode = true;
	}

	/**
	 * @brief Widget code compiles and prints the information to trigger
	 * display:: before invoked in
	 */
	function triggerWidgetCompile(&$content)
	{
		if(Context::getResponseMethod()!='HTML') return new Object();
		$content = $this->transWidgetCode($content, $this->layout_javascript_mode);
		return new Object();
	}

	/**
	 * @breif By converting the specific content of the widget tag return
	 */
	function transWidgetCode($content, $javascript_mode = false, $isReplaceLangCode = true)
	{
		// Changing user-defined language
		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($content, $isReplaceLangCode);
		// Check whether to include information about editing
		$this->javascript_mode = $javascript_mode;
		// Widget code box change
		$content = preg_replace_callback('!<div([^\>]*)widget=([^\>]*?)\><div><div>((<img.*?>)*)!is', array($this,'transWidgetBox'), $content);
		// Widget code information byeogyeong
		$content = preg_replace_callback('!<img([^\>]*)widget=([^\>]*?)\>!is', array($this,'transWidget'), $content);

		return $content;
	}

	/**
	 * @brief Widget code with the actual code changes
	 */
	function transWidget($matches)
	{
		$buff = trim($matches[0]);

		$oXmlParser = new XmlParser();
		$xml_doc = $oXmlParser->parse(trim($buff));

		if($xml_doc->img) $vars = $xml_doc->img->attrs;
		else $vars = $xml_doc->attrs;

		$widget = $vars->widget;
		if(!$widget) return $matches[0];
		unset($vars->widget);

		return $this->execute($widget, $vars, $this->javascript_mode);
	}

	/**
	 * @brief Widget box with the actual code changes
	 */
	function transWidgetBox($matches)
	{
		$buff = preg_replace('/<div><div>(.*)$/i','</div>',$matches[0]);
		$oXmlParser = new XmlParser();
		$xml_doc = $oXmlParser->parse($buff);

		$vars = $xml_doc->div->attrs;
		$widget = $vars->widget;
		if(!$widget) return $matches[0];
		unset($vars->widget);

		$vars->widgetbox_content = $matches[3];
		return $this->execute($widget, $vars, $this->javascript_mode);
	}

	/**
	 * @brief Re-create specific content within a widget
	 * Widget on the page and create cache file in the module using
	 */
	function recompileWidget($content)
	{
		// Language in bringing
		$lang_list = Context::get('lang_supported');
		// Bringing widget cache sequence
		preg_match_all('!<img([^\>]*)widget=([^\>]*?)\>!is', $content, $matches);

		$oXmlParser = new XmlParser();

		$cnt = count($matches[1]);
		for($i=0;$i<$cnt;$i++)
		{
			$buff = $matches[0][$i];
			$xml_doc = $oXmlParser->parse(trim($buff));

			$args = $xml_doc->img->attrs;
			if(!$args) continue;
			// If you are not caching path
			$widget = $args->widget;
			$sequence = $args->widget_sequence;
			$cache = $args->widget_cache;
			if(!$sequence || !$cache) continue;

			if(count($args))
			{
				foreach($args as $k => $v) $args->{$k} = urldecode($v);
			}
			// If the cache file for each language widget regeneration
			foreach($lang_list as $lang_type => $val)
			{
				$cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $sequence, $lang_type);
				if(!file_exists($cache_file)) continue;
				$this->getCache($widget, $args, $lang_type, true);
			}
		}
	}

	/**
	 * @brief Widget cache handling
	 */
	function getCache($widget, $args, $lang_type = null, $ignore_cache = false)
	{
		// If the specified language specifies the current language
		if(!$lang_type) $lang_type = Context::getLangType();
		// widget, the cache number and cache values are set
		$widget_sequence = $args->widget_sequence;
		$widget_cache = $args->widget_cache;

		/**
		 * Even if the cache number and value of the cache and return it to extract data
		 */
		if(!$ignore_cache && (!$widget_cache || !$widget_sequence))
		{
			$oWidget = $this->getWidgetObject($widget);
			if(!$oWidget || !method_exists($oWidget, 'proc')) return;

			$widget_content = $oWidget->proc($args);
			$oModuleController = getController('module');
			$oModuleController->replaceDefinedLangCode($widget_content);
			return $widget_content;
		}

		$oCacheHandler = CacheHandler::getInstance('template');
		if($oCacheHandler->isSupport())
		{
			$key = 'widget_cache:' . $widget_sequence;

			$cache_body = $oCacheHandler->get($key);
			$cache_body = preg_replace('@<\!--#Meta:@', '<!--Meta:', $cache_body);
		}

		if($cache_body)
		{
			return $cache_body;
		}
		else
		{
			/**
			 * Cache number and cache values are set so that the cache file should call
			 */
			FileHandler::makeDir($this->cache_path);
			// Wanted cache file
			$cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $widget_sequence, $lang_type);
			// If the file exists in the cache, the file validation
			if(!$ignore_cache && file_exists($cache_file))
			{
				$filemtime = filemtime($cache_file);
				// Should be modified compared to the time of the cache or in the future if creating more than widget.controller.php file a return value of the cache
				if($filemtime + $widget_cache * 60 > $_SERVER['REQUEST_TIME'] && $filemtime > filemtime(_XE_PATH_.'modules/widget/widget.controller.php'))
				{
					$cache_body = FileHandler::readFile($cache_file);
					$cache_body = preg_replace('@<\!--#Meta:@', '<!--Meta:', $cache_body);

					return $cache_body;
				}
			}
			// cache update and cache renewal of the file mtime
			if(!$oCacheHandler->isSupport())
			{
			touch($cache_file);
			}

			$oWidget = $this->getWidgetObject($widget);
			if(!$oWidget || !method_exists($oWidget,'proc')) return;

			$widget_content = $oWidget->proc($args);
			$oModuleController = getController('module');
			$oModuleController->replaceDefinedLangCode($widget_content);
			if($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($key, $widget_content, $widget_cache * 60);
			}
			else
			{
				FileHandler::writeFile($cache_file, $widget_content);
			}
		}

		return $widget_content;
	}

	/**
	 * @brief Widget name and argument and produce a result and Return the results
	 * Tags used in templateHandler $this-&gt; execute() will be replaced by the code running
	 *
	 * $Javascript_mode is true when editing your page by the code for handling Includes photos
	 */
	function execute($widget, $args, $javascript_mode = false, $escaped = true)
	{
		// Save for debug run-time widget
		if(__DEBUG__==3) $start = getMicroTime();
		$before = microtime(true);
		// urldecode the value of args haejum
		$object_vars = get_object_vars($args);
		if(count($object_vars))
		{
			foreach($object_vars as $key => $val)
			{
				if(in_array($key, array('widgetbox_content','body','class','style','widget_sequence','widget','widget_padding_left','widget_padding_top','widget_padding_bottom','widget_padding_right','widgetstyle','document_srl'))) continue;
				if($escaped) $args->{$key} = utf8RawUrlDecode($val);
			}
		}


		/**
		 * Widgets widgetContent/widgetBox Wanted If you are not content
		 */
		$widget_content = '';
		if($widget != 'widgetContent' && $widget != 'widgetBox')
		{
			if(!is_dir(sprintf(_XE_PATH_.'widgets/%s/',$widget))) return;
			// Hold the contents of the widget parameter
			$widget_content = $this->getCache($widget, $args);
		}

		if($widget == 'widgetBox')
		{
			$widgetbox_content = $args->widgetbox_content;
		}

		/**
		 * Wanted specified by the administrator of the widget style
		 */
		// Sometimes the wrong code, background-image: url (none) can be heard but none in this case, the request for the url so unconditionally Removed
		$style = preg_replace('/url\((.+)(\/?)none\)/is','', $args->style);
		// Find a style statement that based on the internal margin dropping pre-change
		$widget_padding_left = $args->widget_padding_left;
		$widget_padding_right = $args->widget_padding_right;
		$widget_padding_top = $args->widget_padding_top;
		$widget_padding_bottom = $args->widget_padding_bottom;
		$inner_style = sprintf("padding:%dpx %dpx %dpx %dpx !important;", $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left);

		/**
		 * Wanted widget output
		 */

		$widget_content_header = '';
		$widget_content_body = '';
		$widget_content_footer = '';
		// If general call is given on page styles should return immediately dreamin '
		if(!$javascript_mode)
		{
			if($args->id) $args->id = ' id="'.$args->id.'" ';
			switch($widget)
			{
				// If a direct orthogonal addition information
				case 'widgetContent' :
					if($args->document_srl)
					{
						$oDocumentModel = getModel('document');
						$oDocument = $oDocumentModel->getDocument($args->document_srl);
						$body = $oDocument->getContent(false,false,false, false);
					}
					else
					{
						$body = base64_decode($args->body);
					}
					// Change the editor component
					$oEditorController = getController('editor');
					$body = $oEditorController->transComponent($body);

					$widget_content_header = sprintf('<div class="xe_content xe-widget-wrapper ' . $args->css_class . '" %sstyle="%s"><div style="%s">', $args->id, $style,  $inner_style);
					$widget_content_body = $body;
					$widget_content_footer = '</div></div>';

					break;
					// If the widget box; it could
				case 'widgetBox' :
					$widget_content_header = sprintf('<div class="xe-widget-wrapper ' . $args->css_class . '" %sstyle="%s;"><div style="%s"><div>', $args->id, $style,  $inner_style);
					$widget_content_body = $widgetbox_content;

					break;
					// If the General wijetil
				default :
					$widget_content_header = sprintf('<div class="xe-widget-wrapper ' . $args->css_class . '" %sstyle="%s">',$args->id,$style);
					$widget_content_body = sprintf('<div style="*zoom:1;%s">%s</div>', $inner_style,$widget_content);
					$widget_content_footer = '</div>';
					break;
			}
			// Edit page is called when a widget if you add the code for handling
		}
		else
		{
			switch($widget)
			{
				// If a direct orthogonal addition information
				case 'widgetContent' :
					if($args->document_srl)
					{
						$oDocumentModel = getModel('document');
						$oDocument = $oDocumentModel->getDocument($args->document_srl);
						$body = $oDocument->getContent(false,false,false);
					}
					else
					{
						$body = base64_decode($args->body);
					}
					// by args
					$attribute = array();
					if($args)
					{
						foreach($args as $key => $val)
						{
							if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget','widgetstyle','document_srl'))) continue;
							if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
							$attribute[] = sprintf('%s="%s"', $key, htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
						}
					}

					$oWidgetController = getController('widget');

					$widget_content_header = sprintf(
						'<div class="xe_content widgetOutput ' . $args->css_class . '" widgetstyle="%s" style="%s" widget_padding_left="%s" widget_padding_right="%s" widget_padding_top="%s" widget_padding_bottom="%s" widget="widgetContent" document_srl="%d" %s>'.
						'<div class="widgetResize"></div>'.
						'<div class="widgetResizeLeft"></div>'.
						'<div class="widgetBorder">'.
						'<div style="%s">',$args->widgetstyle,
						$style,
						$args->widget_padding_left, $args->widget_padding_right, $args->widget_padding_top, $args->widget_padding_bottom,
						$args->document_srl,
						implode(' ',$attribute),
						$inner_style);

					$widget_content_body = $body;
					$widget_content_footer = sprintf('</div>'.
						'</div>'.
						'<div class="widgetContent" style="display:none;width:1px;height:1px;overflow:hidden;">%s</div>'.
						'</div>',base64_encode($body));

					break;
					// If the widget box; it could
				case 'widgetBox' :
					// by args
					$attribute = array();
					if($args)
					{
						foreach($args as $key => $val)
						{
							if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget','widgetstyle','document_srl'))) continue;
							if(!is_numeric($val) && (!is_string($val) || strlen($val)==0)) continue;
							if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
							$attribute[] = sprintf('%s="%s"', $key, htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
						}
					}

					$widget_content_header = sprintf(
						'<div class="widgetOutput ' . $args->css_class . '" widgetstyle="%s" widget="widgetBox" style="%s;" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s" %s >'.
						'<div class="widgetBoxResize"></div>'.
						'<div class="widgetBoxResizeLeft"></div>'.
						'<div class="widgetBoxBorder"><div class="nullWidget" style="%s">',$args->widgetstyle,$style, $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left,implode(' ',$attribute),$inner_style);

					$widget_content_body = $widgetbox_content;

					break;
					// If the General wijetil
				default :
					// by args
					$attribute = array();
					if($args)
					{
						$allowed_key = array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget');
						foreach($args as $key => $val)
						{
							if(in_array($key, $allowed_key)) continue;
							if(!is_numeric($val) && (!is_string($val) || strlen($val)==0)) continue;
							if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
							$attribute[] = sprintf('%s="%s"', $key, htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
						}
					}

					$widget_content_header = sprintf('<div class="widgetOutput ' . $args->css_class . '" widgetstyle="%s" style="%s" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s" widget="%s" %s >'.
						'<div class="widgetResize"></div>'.
						'<div class="widgetResizeLeft"></div>'.
						'<div class="widgetBorder">',$args->widgetstyle,$style,
						$widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left,
						$widget, implode(' ',$attribute));

					$widget_content_body = sprintf('<div style="%s">%s</div>',$inner_style, $widget_content);

					$widget_content_footer = '</div></div>';

					break;
			}
		}
		// Compile the widget style.
		if($args->widgetstyle) $widget_content_body = $this->compileWidgetStyle($args->widgetstyle,$widget, $widget_content_body, $args, $javascript_mode);

		$output = $widget_content_header . $widget_content_body . $widget_content_footer;
		// Debug widget creation time information added to the results
		if(__DEBUG__==3) $GLOBALS['__widget_excute_elapsed__'] += getMicroTime() - $start;

		$after = microtime(true);

		$elapsed_time = $after - $before;

		$slowlog = new stdClass;
		$slowlog->caller = "widget.execute";
		$slowlog->called = $widget;
		$slowlog->called_extension = $widget;
		writeSlowlog('widget', $elapsed_time, $slowlog);

		// Return result
		return $output;
	}

	/**
	 * @brief Return widget object
	 */
	function getWidgetObject($widget)
	{
		if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $widget))
		{
			return Context::getLang('msg_invalid_request');
		}

		if(!$GLOBALS['_xe_loaded_widgets_'][$widget])
		{
			// Finding the location of a widget
			$oWidgetModel = getModel('widget');
			$path = $oWidgetModel->getWidgetPath($widget);
			// If you do not find the class file error output widget (html output)
			$class_file = sprintf('%s%s.class.php', $path, $widget);
			if(!file_exists($class_file)) return sprintf(Context::getLang('msg_widget_is_not_exists'), $widget);
			// Widget classes include
			require_once($class_file);

			// Creating Objects
			if(!class_exists($widget, false))
			{
				return sprintf(Context::getLang('msg_widget_object_is_null'), $widget);
			}

			$oWidget = new $widget();
			if(!is_object($oWidget)) return sprintf(Context::getLang('msg_widget_object_is_null'), $widget);

			if(!method_exists($oWidget, 'proc')) return sprintf(Context::getLang('msg_widget_proc_is_null'), $widget);

			$oWidget->widget_path = $path;

			$GLOBALS['_xe_loaded_widgets_'][$widget] = $oWidget;
		}
		return $GLOBALS['_xe_loaded_widgets_'][$widget];
	}

	function compileWidgetStyle($widgetStyle,$widget,$widget_content_body, $args, $javascript_mode)
	{
		if(!$widgetStyle) return $widget_content_body;

		$oWidgetModel = getModel('widget');
		// Bring extra_var widget style tie
		$widgetstyle_info = $oWidgetModel->getWidgetStyleInfo($widgetStyle);
		if(!$widgetstyle_info) return $widget_content_body;

		$widgetstyle_extra_var = new stdClass();
		$widgetstyle_extra_var_key = get_object_vars($widgetstyle_info);
		if(count($widgetstyle_extra_var_key['extra_var']))
		{
			foreach($widgetstyle_extra_var_key['extra_var'] as $key => $val)
			{
				$widgetstyle_extra_var->{$key} =  $args->{$key};
			}
		}
		Context::set('widgetstyle_extra_var', $widgetstyle_extra_var);
		// #18994272 오타를 수정했으나 하위 호환성을 위해 남겨둠 - deprecated
		Context::set('widgetstyle_extar_var', $widgetstyle_extra_var);

		if($javascript_mode && $widget=='widgetBox')
		{
			Context::set('widget_content', '<div class="widget_inner">'.$widget_content_body.'</div>');
		}
		else
		{
			Context::set('widget_content', $widget_content_body);
		}
		// Compilation
		$widgetstyle_path = $oWidgetModel->getWidgetStylePath($widgetStyle);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($widgetstyle_path, 'widgetstyle');

		return $tpl;
	}

	/**
	 * @brief request parameters and variables sort through the information widget
	 */
	function arrangeWidgetVars($widget, $request_vars, &$vars)
	{
		$oWidgetModel = getModel('widget');
		$widget_info = $oWidgetModel->getWidgetInfo($widget);

		if(!$vars)
		{
			$vars = new stdClass();
		}

		$widget = $vars->selected_widget;
		$vars->css_class = $request_vars->css_class;
		$vars->widgetstyle = $request_vars->widgetstyle;

		$vars->skin = trim($request_vars->skin);
		$vars->colorset = trim($request_vars->colorset);
		$vars->widget_sequence = (int)($request_vars->widget_sequence);
		$vars->widget_cache = (int)($request_vars->widget_cache);
		$vars->style = trim($request_vars->style);
		$vars->widget_padding_left = trim($request_vars->widget_padding_left);
		$vars->widget_padding_right = trim($request_vars->widget_padding_right);
		$vars->widget_padding_top = trim($request_vars->widget_padding_top);
		$vars->widget_padding_bottom = trim($request_vars->widget_padding_bottom);
		$vars->document_srl= trim($request_vars->document_srl);

		if(count($widget_info->extra_var))
		{
			foreach($widget_info->extra_var as $key=>$val)
			{
				$vars->{$key} = trim($request_vars->{$key});
			}
		}
		// If the widget style
		if($request_vars->widgetstyle)
		{
			$widgetStyle_info = $oWidgetModel->getWidgetStyleInfo($request_vars->widgetstyle);
			if(count($widgetStyle_info->extra_var))
			{
				foreach($widgetStyle_info->extra_var as $key=>$val)
				{
					if($val->type =='color' || $val->type =='text' || $val->type =='select' || $val->type =='filebox' || $val->type == 'textarea')
					{
						$vars->{$key} = trim($request_vars->{$key});
					}
				}
			}
		}

		if($vars->widget_sequence)
		{
			$cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $vars->widget_sequence, Context::getLangType());
			FileHandler::removeFile($cache_file);
		}

		if($vars->widget_cache>0) $vars->widget_sequence = getNextSequence();

		$attribute = array();
		foreach($vars as $key => $val)
		{
			if(!$val)
			{
				unset($vars->{$key});
				continue;
			}
			if(strpos($val,'|@|') > 0) $val = str_replace('|@|', ',', $val);
			$vars->{$key} = Context::convertEncodingStr($val);
			$attribute[] = sprintf('%s="%s"', $key, htmlspecialchars(Context::convertEncodingStr($val), ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
		}

		return $attribute;
	}
}
/* End of file widget.controller.php */
/* Location: ./modules/widget/widget.controller.php */
