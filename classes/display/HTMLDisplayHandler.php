<?php

class HTMLDisplayHandler
{
	/**
	 * jQuery versions
	 */
	public const JQUERY_V2 = '2.2.4';
	public const JQUERY_V2_MIGRATE = '1.4.1';
	public const JQUERY_V3 = '3.6.3';
	public const JQUERY_V3_MIGRATE = '3.4.0';

	/**
	 * Default viewport setting
	 */
	public const DEFAULT_VIEWPORT = 'width=device-width, initial-scale=1.0, user-scalable=yes';

	/**
	 * Reserved scripts
	 */
	public static $reservedCSS = '@\bcommon/css/(?:xe|rhymix|mobile)\.(?:min\.)?(?:s?css|less)$@';
	public static $reservedJS = '@\bcommon/js/(?:jquery(?:-[123][0-9.x-]+)?|xe?|common|js_app|xml_handler|xml_js_filter)\.(?:min\.)?js$@';

	/**
	 * List of scripts to block loading
	 */
	public static $blockedScripts = array(
		'@(?:^|/)j[Qq]uery(?:-[0-9]+(?:\.[0-9x]+)*|-latest)?(?:\.min)?\.js$@',
	);

	/**
	 * Replacement table for XE compatibility
	 */
	public static $replacements = array(
		'@\bcommon/xeicon/@' => 'common/css/xeicon/',
		'@\beditor/skins/xpresseditor/js/xe_textarea\.(?:min\.)?js@' => 'editor/skins/ckeditor/js/xe_textarea.js',
		'@/lang$@' => '/lang/lang.xml',
	);

	/**
	 * Image type information for SEO
	 */
	protected $_image_type = 'none';

	/**
	 * Produce HTML compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string compiled template string
	 */
	public function toDoc(&$oModule)
	{
		// SECISSUE https://github.com/xpressengine/xe-core/issues/1583
		$oSecurity = new Security();
		$oSecurity->encodeHTML('is_keyword', 'search_keyword', 'search_target', 'order_target', 'order_type');

		$template_path = $oModule->getTemplatePath();
		$template_file = $oModule->getTemplateFile();

		if(!is_dir($template_path))
		{
			if($oModule->module_info->module == $oModule->module)
			{
				$skin = $oModule->origin_module_info->skin;
			}
			else
			{
				$skin = $oModule->module_config->skin;
			}

			if(Context::get('module') != 'admin' && strpos(Context::get('act'), 'Admin') === false)
			{
				if($skin && is_string($skin))
				{
					$theme_skin = explode('|@|', $skin);
					$template_path = $oModule->getTemplatePath();
					if(count($theme_skin) == 2)
					{
						$theme_path = sprintf('./themes/%s', $theme_skin[0]);
						// FIXME $theme_path $theme_path $theme_path ??
						if(substr($theme_path, 0, strlen($theme_path)) != $theme_path)
						{
							$template_path = sprintf('%s/modules/%s/', $theme_path, $theme_skin[1]);
						}
					}
				}
				else
				{
					$template_path = $oModule->getTemplatePath();
				}
			}
			else
			{
				$template_path = $oModule->getTemplatePath();
			}
		}

		$oTemplate = new Rhymix\Framework\Template($template_path, $template_file);
		$output = $oTemplate->compile();

		// add .x div for adminitration pages
		if(Context::getResponseMethod() == 'HTML')
		{
			$x_exclude_actions = array(
				'dispPageAdminContentModify' => true,
				'dispPageAdminMobileContentModify' => true,
				'dispPageAdminMobileContent' => true,
			);
			$current_act = Context::get('act') ?? '';
			if(Context::get('module') != 'admin' && strpos($current_act, 'Admin') !== false && !isset($x_exclude_actions[$current_act]))
			{
				$output = '<div class="x">' . $output . '</div>';
			}

			// Wrap content in layout
			$use_layout = Context::get('layout') !== 'none';
			if (!$use_layout && isset($_REQUEST['layout']) && !self::isPartialPageRendering())
			{
				$use_layout = true;
			}
			if ($use_layout)
			{
				$start = microtime(true);

				Context::set('content', $output, false);

				$layout_path = $oModule->getLayoutPath();
				$layout_file = $oModule->getLayoutFile();

				$edited_layout_file = $oModule->getEditedLayoutFile();

				// get the layout information currently requested
				$oLayoutModel = getModel('layout');
				$layout_info = Context::get('layout_info');
				$layout_srl = $layout_info->layout_srl ?? 0;

				// compile if connected to the layout
				if($layout_srl > 0)
				{
					// handle separately if the layout is faceoff
					if($layout_info && isset($layout_info->type) && $layout_info->type == 'faceoff')
					{
						$oLayoutModel->doActivateFaceOff($layout_info);
						Context::set('layout_info', $layout_info);
					}

					// search if the changes CSS exists in the admin layout edit window
					$edited_layout_css = $oLayoutModel->getUserLayoutCss($layout_srl);

					if(FileHandler::exists($edited_layout_css))
					{
						Context::loadFile(array($edited_layout_css, 'all', '', 100));
					}
				}
				if(!$layout_path)
				{
					$layout_path = './common/tpl';
				}
				if(!$layout_file)
				{
					$layout_file = 'default_layout';
				}

				$oTemplate = new Rhymix\Framework\Template;
				$output = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

				// Add layout header script.
				if ($layout_srl > 0)
				{
					$part_config = ModuleModel::getModulePartConfig('layout', $layout_srl);
					if ($part_config && isset($part_config->header_script))
					{
						Context::addHtmlHeader($part_config->header_script, true);
					}
				}

				// if popup_layout, remove admin bar.
				$realLayoutPath = FileHandler::getRealPath($layout_path);
				if(substr_compare($realLayoutPath, '/', -1) !== 0)
				{
					$realLayoutPath .= '/';
				}

				$pathInfo = pathinfo($layout_file);
				$onlyLayoutFile = $pathInfo['filename'];

				$GLOBALS['__layout_compile_elapsed__'] = microtime(true) - $start;
			}
		}

		// Add OpenGraph and Twitter metadata
		if (config('seo.og_enabled') && Context::get('module') !== 'admin')
		{
			$this->_addOpenGraphMetadata();
			if (config('seo.twitter_enabled'))
			{
				$this->_addTwitterMetadata();
			}
		}

		// set icon
		$site_module_info = Context::get('site_module_info');
		$oAdminModel = getAdminModel('admin');
		$favicon_url = $oAdminModel->getFaviconUrl($site_module_info->domain_srl);
		$mobicon_url = $oAdminModel->getMobileIconUrl($site_module_info->domain_srl);
		Context::set('favicon_url', $favicon_url);
		Context::set('mobicon_url', $mobicon_url);

		// Only print the X-UA-Compatible meta tag if somebody is still using IE
		if (preg_match('!Trident/7\.0!', $_SERVER['HTTP_USER_AGENT'] ?? ''))
		{
			Context::addMetaTag('X-UA-Compatible', 'IE=edge', true);
		}

		return $output;
	}

	/**
	 * Check if partial page rendering (dropping the layout) is enabled.
	 *
	 * @return bool
	 */
	public static function isPartialPageRendering()
	{
		$ppr = config('view.partial_page_rendering') ?? 'internal_only';
		if ($ppr === 'disabled')
		{
			return false;
		}
		elseif ($ppr === 'ajax_only' && empty($_SERVER['HTTP_X_REQUESTED_WITH']))
		{
			return false;
		}
		elseif ($ppr === 'internal_only' && (!isset($_SERVER['HTTP_REFERER']) || !Rhymix\Framework\URL::isInternalURL($_SERVER['HTTP_REFERER'])))
		{
			return false;
		}
		elseif ($ppr === 'except_robots' && isCrawler())
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * when display mode is HTML, prepare code before print.
	 * @param string $output compiled template string
	 * @return void
	 */
	public function prepareToPrint(&$output)
	{
		if(Context::getResponseMethod() != 'HTML')
		{
			return;
		}

		$start = microtime(true);

		// move <style>...</style> in body to the header
		$output = preg_replace_callback('!<style(.*?)>(.*?)<\/style>!is', array($this, '_moveStyleToHeader'), $output);

		// move <link> and <meta> in body to the header
		$output = preg_replace_callback('!<(link|meta)\b(.*?)>!is', array($this, '_moveLinkToHeader'), $output);

		// change a meta fine(widget often put the tag like <!--Meta:path--> to the content because of caching)
		$output = preg_replace_callback('/<!--(#)?Meta:([a-z0-9\_\-\/\.\@\:]+)(\?\$\_\_Context\-\>[a-z0-9\_\-\/\.\@\:\>]+)?-->/is', array($this, '_transMeta'), $output);

		// handles a relative path generated by using the rewrite module
		if(Context::isAllowRewrite())
		{
			$pattern = '/(action)=(["\'])(["\'])/s';
			$output = preg_replace($pattern, '$1=$2' . \RX_BASEURL . '$3', $output);

			$pattern = '/(action|poster|src|href)=(["\'])\.\/([^"\']*)(["\'])/s';
			$output = preg_replace($pattern, '$1=$2' . \RX_BASEURL . '$3$4', $output);

			$pattern = '/src=(["\'])((?:files\/(?:attach|cache|faceOff|member_extra_info|thumbnails)|addons|common|(?:m\.)?layouts|modules|widgets|widgetstyle)\/[^"\']+)(["\'])/s';
			$output = preg_replace($pattern, 'src=$1' . \RX_BASEURL . '$2$3', $output);

			$pattern = '/href=(["\'])(\?[^"\']+)/s';
			$output = preg_replace($pattern, 'href=$1' . \RX_BASEURL . '$2', $output);
		}

		// prevent the 2nd request due to url(none) of the background-image
		$output = preg_replace('/url\((["\']?)none(["\']?)\)/is', 'none', $output);

		$INPUT_ERROR = Context::get('INPUT_ERROR');
		if(is_array($INPUT_ERROR) && count($INPUT_ERROR))
		{
			$keys = array_map(function($str) { return preg_quote($str, '@'); }, array_keys($INPUT_ERROR));
			$keys = '(' . implode('|', $keys) . ')';

			$output = preg_replace_callback('@(<input)([^>]*?)\sname="' . $keys . '"([^>]*?)/?>@is', array(&$this, '_preserveValue'), $output);
			$output = preg_replace_callback('@<select[^>]*\sname="' . $keys . '".+</select>@isU', array(&$this, '_preserveSelectValue'), $output);
			$output = preg_replace_callback('@<textarea[^>]*\sname="' . $keys . '".+</textarea>@isU', array(&$this, '_preserveTextAreaValue'), $output);
		}

		$GLOBALS['__trans_content_elapsed__'] = microtime(true) - $start;

		// Remove unnecessary information
		$output = preg_replace('/member\_\-([0-9]+)/s', 'member_0', $output);

		// convert the final layout
		Context::set('content', $output);
		if(Mobile::isFromMobilePhone())
		{
			$this->_loadMobileJSCSS();
		}
		else
		{
			$this->_loadDesktopJSCSS();
		}
		$oTemplate = new Rhymix\Framework\Template('./common/tpl', 'common_layout');
		$output = $oTemplate->compile();

		// replace the user-defined-language
		$output = Context::replaceUserLang($output);

		// remove template path comment tag
		/*
		if(!Rhymix\Framework\Debug::isEnabledForCurrentUser())
		{
			$output = preg_replace('/\n<!-- Template (?:start|end) : .*? -->\r?\n/', "\n", $output);
		}
		*/
	}

	/**
	 * when display mode is HTML, prepare code before print about <input> tag value.
	 * @param array $match input value.
	 * @return string input value.
	 */
	function _preserveValue($match)
	{
		$INPUT_ERROR = Context::get('INPUT_ERROR');
		if (!is_scalar($INPUT_ERROR[$match[3]]))
		{
			return $match[0];
		}

		$str = $match[1] . $match[2] . ' name="' . $match[3] . '"' . $match[4];

		// get type
		$type = 'text';
		if(preg_match('/\stype="([^"]+)"/i', $str, $m))
		{
			$type = strtolower($m[1]);
		}

		switch($type)
		{
			case 'radio':
			case 'checkbox':
				if(preg_match('@\s(?i:value)="' . preg_quote($INPUT_ERROR[$match[3]], '@') . '"@', $str))
				{
					$str = preg_replace('@\schecked(="[^"]*?")?@', ' checked="checked"', $str);
				}
				break;
			default:
				if (!preg_match('@\svalue="([^"]*?)"@', $str))
				{
					$str = $str . ' value=""';
				}
				$str = preg_replace_callback('@\svalue="([^"]*?)"@', function() use($INPUT_ERROR, $match) {
					return ' value="' . escape($INPUT_ERROR[$match[3]], true) . '"';
				}, $str);
		}

		return $str . ' />';
	}

	/**
	 * when display mode is HTML, prepare code before print about <select> tag value.
	 * @param array $matches select tag.
	 * @return string select tag.
	 */
	function _preserveSelectValue($matches)
	{
		$INPUT_ERROR = Context::get('INPUT_ERROR');
		preg_replace('@\sselected(="[^"]*?")?@', ' ', $matches[0]);
		preg_match('@<select.*?>@is', $matches[0], $mm);

		preg_match_all('@<option[^>]*\svalue="([^"]*)".+</option>@isU', $matches[0], $m);

		$key = array_search($INPUT_ERROR[$matches[1]], $m[1]);
		if($key === FALSE)
		{
			return $matches[0];
		}

		$m[0][$key] = preg_replace('@(\svalue=".*?")@is', '$1 selected="selected"', $m[0][$key]);

		return $mm[0] . implode('', $m[0]) . '</select>';
	}

	/**
	 * when display mode is HTML, prepare code before print about <textarea> tag value.
	 * @param array $matches textarea tag information.
	 * @return string textarea tag
	 */
	function _preserveTextAreaValue($matches)
	{
		$INPUT_ERROR = Context::get('INPUT_ERROR');
		preg_match('@<textarea.*?>@is', $matches[0], $mm);
		return $mm[0] . escape($INPUT_ERROR[$matches[1]], true) . '</textarea>';
	}

	/**
	 * Move <style> in the document body to the <head> section.
	 *
	 * @param array $matches
	 * @return void
	 */
	function _moveStyleToHeader($matches)
	{
		if(isset($matches[1]) && stristr($matches[1], 'scoped'))
		{
			return $matches[0];
		}
		Context::addHtmlHeader($matches[0]);
	}

	/**
	 * Move <link> and <meta> in the document body to the <head> section.
	 *
	 * @param array $matches
	 * @return void
	 */
	function _moveLinkToHeader($matches)
	{
		if ($matches[1] === 'link' && preg_match('/\brel="([^"]+)"/', $matches[2], $rel) && $rel[1] !== 'stylesheet' && preg_match('/\bhref="([^"]+)"/', $matches[2], $href))
		{
			Context::addLink($href[1], $rel[1]);
		}
		else
		{
			Context::addHtmlHeader($matches[0]);
		}
	}

	/**
	 * add given .css or .js file names in widget code to Context
	 * @param array $matches
	 * @return void
	 */
	function _transMeta($matches)
	{
		if($matches[1])
		{
			return '';
		}
		if($matches[3] ?? false)
		{
			$vars = Context::get(str_replace('?$__Context->', '', $matches[3]));
			Context::loadFile(array($matches[2], null, null, null, $vars));
		}
		else
		{
			Context::loadFile($matches[2]);
		}
	}

	/**
	 * Add OpenGraph metadata tags.
	 *
	 * @return void
	 */
	function _addOpenGraphMetadata()
	{
		// Get information about the current request.
		$page_type = 'website';
		$current_module_info = Context::get('current_module_info');
		$site_module_info = Context::get('site_module_info');
		$document_srl = Context::get('document_srl');
		$grant = Context::get('grant');
		$permitted = isset($grant->access) ? $grant->access : false;
		if (isset($grant->view) && !$grant->view)
		{
			$permitted = false;
		}
		if ($document_srl && $permitted)
		{
			if (isset($grant->consultation_read) && !$grant->consultation_read && $current_module_info->consultation === 'Y')
			{
				$permitted = false;
			}
			else
			{
				$oDocument = Context::get('oDocument') ?: DocumentModel::getDocument($document_srl, false, false);
				if (is_object($oDocument) && $oDocument->document_srl == $document_srl)
				{
					$page_type = 'article';
					if (method_exists($oDocument, 'isSecret') && $oDocument->isSecret())
					{
						$permitted = false;
					}
				}
			}
		}

		// Get existing metadata.
		$og_data = array();
		foreach (Context::getOpenGraphData() as $val)
		{
			$og_data[$val['property']] = $val['content'];
		}

		// Add basic metadata.
		Context::addOpenGraphData('og:title', $permitted ? Context::getBrowserTitle() : lang('msg_not_permitted'));
		Context::addOpenGraphData('og:site_name', Context::getSiteTitle());
		if (!isset($og_data['og:description']) || !Context::getMetaTag('description'))
		{
			if ($page_type === 'article' && $permitted && config('seo.og_extract_description'))
			{
				$description = trim(utf8_normalize_spaces($oDocument->getContentText(200)));
			}
			else
			{
				$description = Context::getMetaTag('description');
			}
			Context::addOpenGraphData('og:description', $description);
			Context::addMetaTag('description', $description);
		}

		// Add metadata about this page.
		if (!isset($og_data['og:type']))
		{
			Context::addOpenGraphData('og:type', $page_type);
		}
		if (!isset($og_data['og:url']) || !Context::getCanonicalURL())
		{
			if ($page_type === 'article')
			{
				$canonical_url = getNotEncodedFullUrl('', 'mid', $current_module_info->mid, 'document_srl', $document_srl);
			}
			elseif (($page = Context::get('page')) > 1)
			{
				$canonical_url = getNotEncodedFullUrl('', 'mid', $current_module_info->mid, 'page', $page);
			}
			elseif (isset($current_module_info->module_srl) && $current_module_info->module_srl == ($site_module_info->module_srl ?? 0))
			{
				$canonical_url = getNotEncodedFullUrl('');
			}
			else
			{
				if (Rhymix\Framework\Router::getRewriteLevel() === 2 && Context::getCurrentRequest()->url !== '')
				{
					$canonical_url = Rhymix\Framework\URL::getCurrentDomainURL(\RX_BASEURL . preg_replace('/\?.*$/', '', \RX_REQUEST_URL));
				}
				else
				{
					$canonical_url = getNotEncodedFullUrl('', 'mid', $current_module_info->mid);
				}
			}
			Context::setCanonicalURL($canonical_url);
		}

		// Add metadata about the locale.
		$lang_type = Context::getLangType();
		$locales = (include \RX_BASEDIR . 'common/defaults/locales.php');
		if (isset($locales[$lang_type]))
		{
			Context::addOpenGraphData('og:locale', $locales[$lang_type]['locale']);
		}
		if ($page_type === 'article' && $permitted && $oDocument->getLangCode() !== $lang_type && isset($locales[$oDocument->getLangCode()]))
		{
			Context::addOpenGraphData('og:locale:alternate', $locales[$oDocument->getLangCode()]);
		}

		// Add image.
		if ($document_images = Context::getMetaImages())
		{
			// pass
		}
		elseif ($page_type === 'article' && $permitted && config('seo.og_extract_images'))
		{
			if (($document_images = Rhymix\Framework\Cache::get("seo:document_images:$document_srl")) === null)
			{
				$document_images = array();
				if ($oDocument->hasUploadedFiles())
				{
					$document_files = $oDocument->getUploadedFiles();
					usort($document_files, function($a, $b) {
						return ord($b->cover_image) - ord($a->cover_image);
					});

					foreach ($document_files as $file)
					{
						if ($file->isvalid !== 'Y' || !preg_match('/\.(?:bmp|gif|jpe?g|png|webp|mp4)$/i', $file->uploaded_filename))
						{
							continue;
						}

						if (str_starts_with($file->mime_type, 'video/'))
						{
							if ($file->thumbnail_filename)
							{
								list($width, $height) = @getimagesize($file->thumbnail_filename);
								if ($width >= 100 || $height >= 100)
								{
									$document_images[] = array('filepath' => $file->thumbnail_filename, 'width' => $width, 'height' => $height);
									break;
								}
							}
						}
						else
						{
							list($width, $height) = @getimagesize($file->uploaded_filename);
							if ($width >= 100 || $height >= 100)
							{
								$document_images[] = array('filepath' => $file->uploaded_filename, 'width' => $width, 'height' => $height);
								break;
							}
						}
					}
				}
				Rhymix\Framework\Cache::set("seo:document_images:$document_srl", $document_images);
			}
		}
		else
		{
			$document_images = null;
		}

		if ($document_images)
		{
			$first_image = array_first($document_images);
			$first_image['filepath'] = preg_replace('/^.\\/files\\//', \RX_BASEURL . 'files/', $first_image['filepath']);
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($first_image['filepath']));
			Context::addOpenGraphData('og:image:width', $first_image['width']);
			Context::addOpenGraphData('og:image:height', $first_image['height']);
			$this->_image_type = 'document';
		}
		elseif ($default_image = getAdminModel('admin')->getSiteDefaultImageUrl($site_module_info->domain_srl, $width, $height))
		{
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($default_image));
			if ($width && $height)
			{
				Context::addOpenGraphData('og:image:width', $width);
				Context::addOpenGraphData('og:image:height', $height);
			}
			$this->_image_type = 'site';
		}
		else
		{
			$this->_image_type = 'none';
		}

		// Add tags and hashtags for articles.
		if ($page_type === 'article' && $permitted)
		{
			$tags = $oDocument->getTags();
			foreach ($tags as $tag)
			{
				if ($tag !== '')
				{
					Context::addOpenGraphData('og:article:tag', $tag);
				}
			}

			if (config('seo.og_extract_hashtags'))
			{
				$hashtags = $oDocument->getHashtags();
				foreach ($hashtags as $hashtag)
				{
					if (!in_array($hashtag, $tags))
					{
						Context::addOpenGraphData('og:article:tag', escape($hashtag, false));
					}
				}
			}

			Context::addOpenGraphData('og:article:section', Context::replaceUserLang($current_module_info->browser_title));
		}

		// Add author name for articles.
		if ($page_type === 'article' && $permitted && config('seo.og_use_nick_name'))
		{
			Context::addMetaTag('author', $oDocument->getNickName());
			Context::addOpenGraphData('og:article:author', $oDocument->getNickName());
		}

		// Add datetime for articles.
		if ($page_type === 'article' && $permitted && config('seo.og_use_timestamps'))
		{
			Context::addOpenGraphData('og:article:published_time', $oDocument->getRegdate('c'));
			Context::addOpenGraphData('og:article:modified_time', $oDocument->getUpdate('c'));
		}
	}

	/**
	 * Add Twitter metadata tags.
	 *
	 * @return void
	 */
	function _addTwitterMetadata()
	{
		$card_type = $this->_image_type === 'document' ? 'summary_large_image' : 'summary';
		Context::addMetaTag('twitter:card', $card_type, false, false);

		foreach(Context::getOpenGraphData() as $val)
		{
			if ($val['property'] === 'og:title')
			{
				Context::addMetaTag('twitter:title', $val['content'], false, false);
			}
			if ($val['property'] === 'og:description')
			{
				Context::addMetaTag('twitter:description', $val['content'], false, false);
			}
			if ($val['property'] === 'og:image' && $this->_image_type === 'document')
			{
				Context::addMetaTag('twitter:image', $val['content'], false, false);
			}
		}
	}

	/**
	 * import basic .js files.
	 * @return void
	 */
	function _loadDesktopJSCSS()
	{
		$this->_loadCommonJSCSS();
	}

	/**
	 * import basic .js files for mobile
	 */
	private function _loadMobileJSCSS()
	{
		$this->_loadCommonJSCSS();
	}

	/**
	 * import common .js and .css files for (both desktop and mobile)
	 */
	private function _loadCommonJSCSS()
	{
		if (config('view.jquery_version') === 3)
		{
			$jquery_version = self::JQUERY_V3;
			$jquery_migrate_version = self::JQUERY_V3_MIGRATE;
		}
		else
		{
			$jquery_version = self::JQUERY_V2;
			$jquery_migrate_version = self::JQUERY_V2_MIGRATE;
		}

		Context::loadFile(array('./common/css/rhymix.scss', '', '', -1600000000), true);

		$original_file_list = array(
			'plugins/jquery.migrate/jquery-migrate-' . $jquery_migrate_version . '.min.js',
			'plugins/cookie/js.cookie.min.js',
			'plugins/blankshield/blankshield.min.js',
			'plugins/uri/URI.min.js',
			'x.js',
			'common.js',
			'js_app.js',
			'xml_handler.js',
			'xml_js_filter.js',
		);

		if(config('view.minify_scripts') === 'none')
		{
			Context::loadFile(array('./common/js/jquery-' . $jquery_version . '.js', 'head', '', -1800000000), true);
			foreach($original_file_list as $filename)
			{
				Context::loadFile(array('./common/js/' . $filename, 'head', '', -1700000000), true);
			}
		}
		else
		{
			Context::loadFile(array('./common/js/jquery-' . $jquery_version . '.min.js', 'head', '', -1800000000), true);
			$concat_target_filename = 'files/cache/assets/minified/rhymix.min.js';
			if(file_exists(\RX_BASEDIR . $concat_target_filename))
			{
				$concat_target_mtime = filemtime(\RX_BASEDIR . $concat_target_filename);
				$original_mtime = 0;
				foreach($original_file_list as $filename)
				{
					$original_mtime = max($original_mtime, filemtime(\RX_BASEDIR . 'common/js/' . $filename));
				}
				if($concat_target_mtime > $original_mtime)
				{
					Context::loadFile(array('./' . $concat_target_filename, 'head', '', -1700000000), true);
					return;
				}
			}
			Rhymix\Framework\Formatter::minifyJS(array_map(function($str) {
				return \RX_BASEDIR . 'common/js/' . $str;
			}, $original_file_list), \RX_BASEDIR . $concat_target_filename);
			Context::loadFile(array('./' . $concat_target_filename, 'head', '', -1700000000), true);
		}
	}
}
