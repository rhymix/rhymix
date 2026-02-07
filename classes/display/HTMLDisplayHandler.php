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
	protected static $_image_type = 'none';

	/**
	 * Produce HTML compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string compiled template string
	 */
	public function toDoc(&$oModule)
	{
		$template_path = $oModule->getTemplatePath();
		$template_file = $oModule->getTemplateFile();
		$oTemplate = new Rhymix\Framework\Template($template_path, $template_file);
		$output = $oTemplate->compile();

		// add .x div for adminitration pages
		if (Context::getResponseMethod() == 'HTML')
		{
			$x_exclude_actions = array(
				'dispPageAdminContentModify' => true,
				'dispPageAdminMobileContentModify' => true,
				'dispPageAdminMobileContent' => true,
			);
			$current_act = strval(Context::get('act'));
			if(Context::get('module') != 'admin' && strpos($current_act, 'Admin') !== false && !isset($x_exclude_actions[$current_act]))
			{
				$output = '<div class="x">' . $output . '</div>';
			}

			// Wrap content in layout
			$use_layout = Context::get('layout') !== 'none';
			if (!$use_layout && isset($_REQUEST['layout']) && !ModuleHandler::isPartialPageRenderingEnabled())
			{
				$use_layout = true;
			}
			if ($use_layout)
			{
				$layout_path = $oModule->getLayoutPath();
				$layout_file = $oModule->getLayoutFile();
				$edited_layout_file = $oModule->getEditedLayoutFile();

				// get the layout information currently requested
				$oLayoutModel = getModel('layout');
				$layout_info = Context::get('layout_info') ?: new stdClass;
				$layout_srl = $layout_info->layout_srl ?? 0;

				if (!$layout_path)
				{
					$layout_path = './common/tpl';
				}
				if (!$layout_file)
				{
					$layout_file = ($layout_path === './common/tpl') ? 'default_layout' : 'layout';
				}

				// Add layout header script.
				if ($layout_srl > 0)
				{
					$part_config = Rhymix\Modules\Module\Models\ModuleConfig::getModulePartConfig('layout', $layout_srl);
					if ($part_config && isset($part_config->header_script))
					{
						Context::addHtmlHeader($part_config->header_script, true);
					}
				}

				// Add layout edited CSS.
				if ($layout_srl > 0)
				{
					// search if the changes CSS exists in the admin layout edit window
					$edited_layout_css = $oLayoutModel->getUserLayoutCss($layout_srl);
					if(FileHandler::exists($edited_layout_css))
					{
						Context::loadFile(array($edited_layout_css, 'all', '', 100));
					}
				}

				Context::set('content', $output, false);
				Context::set('layout_info', $layout_info, false);

				$oTemplate = new Rhymix\Framework\Template;
				$output = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);
			}
		}

		return $output;
	}

	/**
	 * @deprecated
	 */
	public function prepareToPrint(&$output)
	{

	}

	/**
	 * Add OpenGraph metadata tags.
	 *
	 * @return void
	 */
	public static function _addOpenGraphMetadata()
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
					$canonical_url = getNotEncodedFullUrl('', 'mid', $current_module_info->mid ?? null);
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
			self::$_image_type = 'document';
		}
		elseif ($default_image = getAdminModel('admin')->getSiteDefaultImageUrl($site_module_info->domain_srl, $width, $height))
		{
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($default_image));
			if ($width && $height)
			{
				Context::addOpenGraphData('og:image:width', $width);
				Context::addOpenGraphData('og:image:height', $height);
			}
			self::$_image_type = 'site';
		}
		else
		{
			self::$_image_type = 'none';
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
	public static function _addTwitterMetadata()
	{
		$card_type = self::$_image_type === 'document' ? 'summary_large_image' : 'summary';
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
			if ($val['property'] === 'og:image' && self::$_image_type === 'document')
			{
				Context::addMetaTag('twitter:image', $val['content'], false, false);
			}
		}
	}

	/**
	 * @deprecated
	 */
	public function _loadDesktopJSCSS()
	{
		FrontEndFileHandler::loadCommonFiles();
	}

	/**
	 * @deprecated
	 */
	private function _loadMobileJSCSS()
	{
		FrontEndFileHandler::loadCommonFiles();
	}

	/**
	 * @deprecated
	 */
	private function _loadCommonJSCSS()
	{
		FrontEndFileHandler::loadCommonFiles();
	}
}
