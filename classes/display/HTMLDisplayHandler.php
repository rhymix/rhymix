<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class HTMLDisplayHandler
{
	/**
	 * Reserved scripts
	 */
	public static $reservedCSS = '@\bcommon/css/(?:xe|rhymix|mobile)\.(?:min\.)?(?:s?css|less)$@';
	public static $reservedJS = '@\bcommon/js/(?:jquery(?:-[123][0-9.x-]+)?|xe?|common|js_app|xml_handler|xml_js_filter)\.(?:min\.)?js$@';
	
	/**
	 * Replacement table for XE compatibility
	 */
	public static $replacements = array(
		'@\bcommon/xeicon/@' => 'common/css/xeicon/',
	);

	/**
	 * Produce HTML compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string compiled template string
	 */
	function toDoc(&$oModule)
	{
		$oTemplate = TemplateHandler::getInstance();

		// compile module tpl
		// deprecated themes skin

		$template_path = $oModule->getTemplatePath();

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

		$tpl_file = $oModule->getTemplateFile();

		$output = $oTemplate->compile($template_path, $tpl_file);

		// SECISSUE https://github.com/xpressengine/xe-core/issues/1583
		$oSecurity = new Security();
		$oSecurity->encodeHTML('is_keyword');

		// add .x div for adminitration pages
		if(Context::getResponseMethod() == 'HTML')
		{
			if(Context::get('module') != 'admin' && strpos(Context::get('act'), 'Admin') > 0 && Context::get('act') != 'dispPageAdminContentModify' && Context::get('act') != 'dispPageAdminMobileContentModify')
			{
				$output = '<div class="x">' . $output . '</div>';
			}

			if(Context::get('layout') != 'none')
			{
				$start = microtime(true);

				Context::set('content', $output, false);

				$layout_path = $oModule->getLayoutPath();
				$layout_file = $oModule->getLayoutFile();

				$edited_layout_file = $oModule->getEditedLayoutFile();

				// get the layout information currently requested
				$oLayoutModel = getModel('layout');
				$layout_info = Context::get('layout_info');
				$layout_srl = $layout_info->layout_srl;

				// compile if connected to the layout
				if($layout_srl > 0)
				{

					// handle separately if the layout is faceoff
					if($layout_info && $layout_info->type == 'faceoff')
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
				$output = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

				// if popup_layout, remove admin bar.
				$realLayoutPath = FileHandler::getRealPath($layout_path);
				if(substr_compare($realLayoutPath, '/', -1) !== 0)
				{
					$realLayoutPath .= '/';
				}

				$pathInfo = pathinfo($layout_file);
				$onlyLayoutFile = $pathInfo['filename'];

				$GLOBALS['__layout_compile_elapsed__'] = microtime(true) - $start;

				if(stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE && (Context::get('_use_ssl') == 'optional' || Context::get('_use_ssl') == 'always'))
				{
					Context::addHtmlFooter('<iframe id="xeTmpIframe" name="xeTmpIframe" style="width:1px;height:1px;position:absolute;top:-2px;left:-2px;"></iframe>');
				}
			}
		}
		return $output;
	}

	/**
	 * when display mode is HTML, prepare code before print.
	 * @param string $output compiled template string
	 * @return void
	 */
	function prepareToPrint(&$output)
	{
		if(Context::getResponseMethod() != 'HTML')
		{
			return;
		}

		$start = microtime(true);

		// move <style ..></style> in body to the header
		$output = preg_replace_callback('!<style(.*?)>(.*?)<\/style>!is', array($this, '_moveStyleToHeader'), $output);

		// move <link ..></link> in body to the header
		$output = preg_replace_callback('!<link(.*?)/>!is', array($this, '_moveLinkToHeader'), $output);

		// move <meta ../> in body to the header
		$output = preg_replace_callback('!<meta(.*?)(?:\/|)>!is', array($this, '_moveMetaToHeader'), $output);

		// change a meta fine(widget often put the tag like <!--Meta:path--> to the content because of caching)
		$output = preg_replace_callback('/<!--(#)?Meta:([a-z0-9\_\-\/\.\@\:]+)-->/is', array($this, '_transMeta'), $output);

		// handles a relative path generated by using the rewrite module
		if(Context::isAllowRewrite())
		{
			$url = parse_url(Context::getRequestUri());
			$real_path = $url['path'];

			$pattern = '/src=("|\'){1}(\.\/)?(files\/attach|files\/cache|files\/faceOff|files\/member_extra_info|modules|common|widgets|widgetstyle|layouts|addons)\/([^"\']+)\.(jpg|jpeg|png|gif)("|\'){1}/s';
			$output = preg_replace($pattern, 'src=$1' . $real_path . '$3/$4.$5$6', $output);

			$pattern = '/href=("|\'){1}(\?[^"\']+)/s';
			$output = preg_replace($pattern, 'href=$1' . $real_path . '$2', $output);

			if(Context::get('vid'))
			{
				$pattern = '/\/' . Context::get('vid') . '\?([^=]+)=/is';
				$output = preg_replace($pattern, '/?$1=', $output);
			}
		}

		// prevent the 2nd request due to url(none) of the background-image
		$output = preg_replace('/url\((["\']?)none(["\']?)\)/is', 'none', $output);

		if(is_array(Context::get('INPUT_ERROR')))
		{
			$INPUT_ERROR = Context::get('INPUT_ERROR');
			$keys = array_keys($INPUT_ERROR);
			$keys = '(' . implode('|', $keys) . ')';

			$output = preg_replace_callback('@(<input)([^>]*?)\sname="' . $keys . '"([^>]*?)/?>@is', array(&$this, '_preserveValue'), $output);
			$output = preg_replace_callback('@<select[^>]*\sname="' . $keys . '".+</select>@isU', array(&$this, '_preserveSelectValue'), $output);
			$output = preg_replace_callback('@<textarea[^>]*\sname="' . $keys . '".+</textarea>@isU', array(&$this, '_preserveTextAreaValue'), $output);
		}

		$GLOBALS['__trans_content_elapsed__'] = microtime(true) - $start;

		// Remove unnecessary information
		$output = preg_replace('/member\_\-([0-9]+)/s', 'member_0', $output);
		
		// Add OpenGraph metadata
		if (config('seo.og_enabled') && Context::get('module') !== 'admin')
		{
			$this->_addOpenGraphMetadata();
		}

		// set icon
		$oAdminModel = getAdminModel('admin');
		$favicon_url = $oAdminModel->getFaviconUrl(false);
		$mobicon_url = $oAdminModel->getMobileIconUrl(false);
		Context::set('favicon_url', $favicon_url);
		Context::set('mobicon_url', $mobicon_url);

		// convert the final layout
		Context::set('content', $output);
		Context::set('m', $is_mobile = Mobile::isFromMobilePhone() ? 1 : 0);
		$oTemplate = TemplateHandler::getInstance();
		if($is_mobile)
		{
			$this->_loadMobileJSCSS();
		}
		else
		{
			$this->_loadDesktopJSCSS();
		}
		$output = $oTemplate->compile('./common/tpl', 'common_layout');

		// replace the user-defined-language
		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($output);
	}

	/**
	 * when display mode is HTML, prepare code before print about <input> tag value.
	 * @param array $match input value.
	 * @return string input value.
	 */
	function _preserveValue($match)
	{
		$INPUT_ERROR = Context::get('INPUT_ERROR');

		$str = $match[1] . $match[2] . ' name="' . $match[3] . '"' . $match[4];

		// get type
		$type = 'text';
		if(preg_match('/\stype="([a-z]+)"/i', $str, $m))
		{
			$type = strtolower($m[1]);
		}

		switch($type)
		{
			case 'text':
			case 'hidden':
			case 'email':
			case 'search':
			case 'tel':
			case 'url':
			case 'email':
			case 'datetime':
			case 'date':
			case 'month':
			case 'week':
			case 'time':
			case 'datetime-local':
			case 'number':
			case 'range':
			case 'color':
				$str = preg_replace('@\svalue="[^"]*?"@', ' ', $str) . ' value="' . htmlspecialchars($INPUT_ERROR[$match[3]], ENT_COMPAT | ENT_HTML401, 'UTF-8', false) . '"';
				break;
			case 'password':
				$str = preg_replace('@\svalue="[^"]*?"@', ' ', $str);
				break;
			case 'radio':
			case 'checkbox':
				$str = preg_replace('@\schecked(="[^"]*?")?@', ' ', $str);
				if(@preg_match('@\s(?i:value)="' . $INPUT_ERROR[$match[3]] . '"@', $str))
				{
					$str .= ' checked="checked"';
				}
				break;
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
		return $mm[0] . $INPUT_ERROR[$matches[1]] . '</textarea>';
	}

	/**
	 * add html style code extracted from html body to Context, which will be
	 * printed inside <header></header> later.
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
	 * add html link code extracted from html body to Context, which will be
	 * printed inside <header></header> later.
	 * @param array $matches
	 * @return void
	 */
	function _moveLinkToHeader($matches)
	{
		Context::addHtmlHeader($matches[0]);
	}

	/**
	 * add meta code extracted from html body to Context, which will be
	 * printed inside <header></header> later.
	 * @param array $matches
	 * @return void
	 */
	function _moveMetaToHeader($matches)
	{
		Context::addHtmlHeader($matches[0]);
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
		Context::loadFile($matches[2]);
	}
	
	/**
	 * Add OpenGraph metadata tags.
	 * 
	 * @param string $output
	 * @return void
	 */
	function _addOpenGraphMetadata()
	{
		// Get information about the current request.
		$page_type = 'website';
		$current_module_info = Context::get('current_module_info');
		$site_module_info = Context::get('site_module_info');
		$document_srl = Context::get('document_srl');
		if ($document_srl)
		{
			$oDocument = Context::get('oDocument') ?: getModel('document')->getDocument($document_srl, false, false);
			if ($oDocument instanceof documentItem && $oDocument->document_srl == $document_srl && !$oDocument->isSecret())
			{
				$page_type = 'article';
			}
		}
		
		// Add basic metadata.
		Context::addOpenGraphData('og:title', Context::getBrowserTitle());
		Context::addOpenGraphData('og:site_name', Context::getSiteTitle());
		if ($page_type === 'article' && config('seo.og_extract_description'))
		{
			$description = trim(utf8_normalize_spaces($oDocument->getContentText(200)));
		}
		else
		{
			$description = Context::getMetaTag('description');
		}
		Context::addOpenGraphData('og:description', $description);
		Context::addMetaTag('description', $description);
		
		// Add metadata about this page.
		Context::addOpenGraphData('og:type', $page_type);
		if ($page_type === 'article')
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid, 'document_srl', $document_srl);
		}
		elseif (($page = Context::get('page')) > 1)
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid, 'page', $page);
		}
		elseif ($current_module_info->module_srl == $site_module_info->module_srl)
		{
			$canonical_url = getFullUrl('');
		}
		else
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid);
		}
		Context::addOpenGraphData('og:url', $canonical_url);
		Context::setCanonicalURL($canonical_url);
		
		// Add metadata about the locale.
		$lang_type = Context::getLangType();
		$locales = (include \RX_BASEDIR . 'common/defaults/locales.php');
		if (isset($locales[$lang_type]))
		{
			Context::addOpenGraphData('og:locale', $locales[$lang_type]);
		}
		if ($page_type === 'article' && $oDocument->getLangCode() !== $lang_type && isset($locales[$oDocument->getLangCode()]))
		{
			Context::addOpenGraphData('og:locale:alternate', $locales[$oDocument->getLangCode()]);
		}
		
		// Add image.
		if ($page_type === 'article' && config('seo.og_extract_images'))
		{
			if (($document_images = Rhymix\Framework\Cache::get("seo:document_images:$document_srl")) === null)
			{
				$document_images = array();
				if ($oDocument->hasUploadedFiles())
				{
					foreach ($oDocument->getUploadedFiles() as $file)
					{
						if ($file->isvalid !== 'Y' || !preg_match('/\.(?:bmp|gif|jpe?g|png)$/i', $file->uploaded_filename))
						{
							continue;
						}
						list($width, $height) = @getimagesize($file->uploaded_filename);
						if ($width < 100 && $height < 100)
						{
							continue;
						}
						$image = array('filepath' => $file->uploaded_filename, 'width' => $width, 'height' => $height);
						if ($file->cover_image === 'Y')
						{
							array_unshift($document_images, $image);
						}
						else
						{
							$document_images[] = $image;
						}
						if (count($document_images) >= 1)
						{
							break;
						}
					}
				}
				Rhymix\Framework\Cache::set("seo:document_images:$document_srl", $document_images, 0, true);
			}
		}
		else
		{
			$document_images = null;
		}
		
		if ($document_images)
		{
			$first_image = reset($document_images);
			$first_image['filepath'] = preg_replace('/^.\\/files\\//', \RX_BASEURL . 'files/', $first_image['filepath']);
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($first_image['filepath']));
			Context::addOpenGraphData('og:image:width', $first_image['width']);
			Context::addOpenGraphData('og:image:height', $first_image['height']);
		}
		elseif ($default_image = getAdminModel('admin')->getSiteDefaultImageUrl($width, $height))
		{
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($default_image));
			if ($width && $height)
			{
				Context::addOpenGraphData('og:image:width', $width);
				Context::addOpenGraphData('og:image:height', $height);
			}
		}
		
		// Add datetime for articles.
		if ($page_type === 'article' && config('seo.og_use_timestamps'))
		{
			Context::addOpenGraphData('article:published_time', $oDocument->getRegdate('c'));
			Context::addOpenGraphData('article:modified_time', $oDocument->getUpdate('c'));
		}
	}

	/**
	 * import basic .js files.
	 * @return void
	 */
	function _loadDesktopJSCSS()
	{
		$lang_type = Context::getLangType();
		$this->_loadCommonJSCSS();
		
		// for admin page, add admin css
		if(Context::get('module') == 'admin' || strpos(Context::get('act'), 'Admin') > 0)
		{
			Context::loadFile(array('./modules/admin/tpl/css/admin.css', '', '', 10), true);
			Context::loadFile(array("./modules/admin/tpl/css/admin.iefix.css", '', 'ie', 10), true);
			Context::loadFile('./modules/admin/tpl/js/admin.js', true);
			Context::loadFile(array('./modules/admin/tpl/css/admin.bootstrap.css', '', '', 1), true);
			Context::loadFile(array('./modules/admin/tpl/js/jquery.tmpl.js', '', '', 1), true);
			Context::loadFile(array('./modules/admin/tpl/js/jquery.jstree.js', '', '', 1), true);
		}
	}

	/**
	 * import basic .js files for mobile
	 */
	private function _loadMobileJSCSS()
	{
		$this->_loadCommonJSCSS();
		Context::loadFile(array('./common/css/mobile.css', '', '', -1500000), true);
	}

	/**
	 * import common .js and .css files for (both desktop and mobile)
	 */
	private function _loadCommonJSCSS()
	{
		Context::loadFile(array('./common/css/rhymix.scss', '', '', -1600000), true);
		$original_file_list = array('x', 'common', 'js_app', 'xml_handler', 'xml_js_filter');
		$jquery_version = preg_match('/MSIE [5-8]\./', $_SERVER['HTTP_USER_AGENT']) ? '1.11.3' : '2.1.4';
		
		if(config('view.minify_scripts') === 'none')
		{
			Context::loadFile(array('./common/js/jquery-' . $jquery_version . '.js', 'head', '', -1730000), true);
			Context::loadFile(array('./common/js/plugins/jquery.migrate/jquery-migrate-1.2.1.js', 'head', '', -1720000), true);
			foreach($original_file_list as $filename)
			{
				Context::loadFile(array('./common/js/' . $filename . '.js', 'head', '', -1700000), true);
			}
		}
		else
		{
			Context::loadFile(array('./common/js/jquery-' . $jquery_version . '.min.js', 'head', '', -1730000), true);
			Context::loadFile(array('./common/js/plugins/jquery.migrate/jquery-migrate-1.2.1.min.js', 'head', '', -1720000), true);
			$concat_target_filename = 'files/cache/assets/minified/rhymix.min.js';
			if(file_exists(\RX_BASEDIR . $concat_target_filename))
			{
				$concat_target_mtime = filemtime(\RX_BASEDIR . $concat_target_filename);
				$original_mtime = 0;
				foreach($original_file_list as $filename)
				{
					$original_mtime = max($original_mtime, filemtime(\RX_BASEDIR . 'common/js/' . $filename . '.js'));
				}
				if($concat_target_mtime > $original_mtime)
				{
					Context::loadFile(array('./' . $concat_target_filename, 'head', '', -100000), true);
					return;
				}
			}
			Rhymix\Framework\Formatter::minifyJS(array_map(function($str) {
				return \RX_BASEDIR . 'common/js/' . $str . '.js';
			}, $original_file_list), \RX_BASEDIR . $concat_target_filename);
			Context::loadFile(array('./' . $concat_target_filename, 'head', '', -100000), true);
		}
	}
}
/* End of file HTMLDisplayHandler.class.php */
/* Location: ./classes/display/HTMLDisplayHandler.class.php */
