<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Rhymix\Framework\Debug;
use Rhymix\Framework\Formatter;
use Rhymix\Framework\Router;
use Rhymix\Framework\Template;
use Rhymix\Framework\URL;
use Rhymix\Modules\Admin\Models\Icon as AdminIconModel;
use Rhymix\Modules\Module\Models\ModuleConfig as ModuleConfigModel;
use Context;
use FileHandler;
use FrontEndFileHandler;
use HTMLDisplayHandler;
use LayoutModel;
use ModuleHandler;

/**
 * The HTML response class.
 *
 * This is the default response for most web pages.
 */
class HTMLResponse extends AbstractResponse
{
	/**
	 * Override the default content type.
	 */
	protected string $_content_type = 'text/html';
	protected string $_charset = 'UTF-8';

	/**
	 * Internal state.
	 */
	protected string $_layout_dirname = '';
	protected string $_layout_filename = '';
	protected string $_template_dirname = '';
	protected string $_template_filename = '';

	/**
	 * List of actions that do not need to wrap with .x
	 */
	protected static $_x_exclude_acts = [
		'dispPageAdminContentModify' => true,
		'dispPageAdminMobileContentModify' => true,
		'dispPageAdminMobileContent' => true,
	];

	/**
	 * Set the layout path and filename.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return self
	 */
	public function setLayout(string $dirname, string $filename = 'layout'): self
	{
		$this->_layout_dirname = $dirname;
		$this->_layout_filename = $filename;
		return $this;
	}

	/**
	 * Set the template path and filename.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return self
	 */
	public function setTemplate(string $dirname, string $filename): self
	{
		$this->_template_dirname = $dirname;
		$this->_template_filename = $filename;
		return $this;
	}

	/**
	 * Get the current layout path.
	 *
	 * @return string
	 */
	public function getLayoutPath(): string
	{
		return $this->_layout_dirname;
	}

	/**
	 * Get the current layout filename.
	 *
	 * @return string
	 */
	public function getLayoutFile(): string
	{
		return $this->_layout_filename;
	}

	/**
	 * Get the current template path.
	 *
	 * @return string
	 */
	public function getTemplatePath(): string
	{
		return $this->_template_dirname;
	}

	/**
	 * Get the current template filename.
	 *
	 * @return string
	 */
	public function getTemplateFile(): string
	{
		return $this->_template_filename;
	}

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		// Extract temporary variable.
		$edited_layout_file = $this->_vars['edited_layout_file'] ?? '';
		unset($this->_vars['edited_layout_file']);

		// Render the base template.
		if ($this->_template_dirname && $this->_template_filename)
		{
			$tpl = new Template($this->_template_dirname, $this->_template_filename);
			if ($this->_vars)
			{
				$tpl->setVars($this->_vars);
			}
			$output = $tpl->compile();
		}
		else
		{
			$output = '';
		}

		// Wrap in .x for legacy configuration screens.
		$act = strval(Context::get('act'));
		if (Context::get('module') !== 'admin' && strpos($act, 'Admin') !== false && !isset(self::$_x_exclude_acts[$act]))
		{
			$output = '<div class="x">' . "\n" . $output . "\n" . '</div>';
		}

		// Wrap in layout.
		$use_layout = Context::get('layout') !== 'none';
		if (!$use_layout && isset($_REQUEST['layout']) && !ModuleHandler::isPartialPageRenderingEnabled())
		{
			$use_layout = true;
		}
		if ($use_layout)
		{
			$start = microtime(true);

			// Get layout information.
			$layout_path = $this->getLayoutPath();
			$layout_file = $this->getLayoutFile();
			$layout_info = Context::get('layout_info') ?: new \stdClass;
			$layout_srl = $layout_info->layout_srl ?? 0;

			// Fallback to default layout if not specified.
			if (!$layout_path)
			{
				$layout_path = './common/tpl';
			}
			if (!$layout_file)
			{
				$layout_file = ($layout_path === './common/tpl') ? 'default_layout' : 'layout';
			}

			// Add layout header script and user-edited CSS.
			if ($layout_srl > 0)
			{
				$part_config = ModuleConfigModel::getModulePartConfig('layout', $layout_srl);
				if ($part_config && !empty($part_config->header_script))
				{
					Context::addHtmlHeader($part_config->header_script, true);
				}

				$edited_layout_css = LayoutModel::getUserLayoutCss($layout_srl);
				if (FileHandler::exists($edited_layout_css))
				{
					Context::loadFile([$edited_layout_css, 'all', '', 100]);
				}
			}

			// Compile the layout with $content.
			$tpl = new Template;
			Context::set('content', $output, false);
			$output = $tpl->compile($layout_path, $layout_file, $edited_layout_file);
			Debug::addTime('layout', microtime(true) - $start);
		}

		yield $output;
	}

	/**
	 * Finalize the response.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		$start = microtime(true);

		// Move <style>...</style> to the header.
		$content = preg_replace_callback('!<style(.*?)>(.*?)<\/style>!is', [$this, '_moveStyleToHeader'], $content);

		// Move <link> and <meta> to the header.
		$content = preg_replace_callback('!<(link|meta)\b(.*?)>!is', [$this, '_moveLinkToHeader'], $content);

		// Extract asset links from meta comments left by widgets.
		$content = preg_replace_callback('/<!--(#)?Meta:([a-z0-9\_\-\/\.\@\:]+)(\?\$\_\_Context\-\>[a-z0-9\_\-\/\.\@\:\>]+)?-->/is', [$this, '_transMeta'], $content);

		// Convert relative URLs to absolute URLs if URL rewriting is enabled.
		if (Router::getRewriteLevel() > 0)
		{
			$content = preg_replace([
				'/(action)=(["\'])(["\'])/s',
				'/(action|poster|src|href)=(["\'])\.\/([^"\']*)(["\'])/s',
				'/src=(["\'])((?:files\/(?:attach|cache|faceOff|member_extra_info|thumbnails)|addons|common|(?:m\.)?layouts|modules|widgets|widgetstyle)\/[^"\']+)(["\'])/s',
				'/href=(["\'])(\?[^"\']+)/s',
			], [
				'$1=$2' . \RX_BASEURL . '$3',
				'$1=$2' . \RX_BASEURL . '$3$4',
				'src=$1' . \RX_BASEURL . '$2$3',
				'href=$1' . \RX_BASEURL . '$2',
			], $content);
		}

		// If there were input errors, preserve the entered values.
		$vars = Context::get('INPUT_ERROR');
		if (is_array($vars) && count($vars))
		{
			$keys = array_map(function($str) {
				return preg_quote($str, '@');
			}, array_keys($vars));
			$keys = '(' . implode('|', $keys) . ')';

			$content = preg_replace_callback('@(<input)([^>]*?)\sname="' . $keys . '"([^>]*?)/?>@is', [$this, '_preserveValue'], $content);
			$content = preg_replace_callback('@<select[^>]*\sname="' . $keys . '".+</select>@isU', [$this, '_preserveSelectValue'], $content);
			$content = preg_replace_callback('@<textarea[^>]*\sname="' . $keys . '".+</textarea>@isU', [$this, '_preserveTextareaValue'], $content);
		}

		// Remove url("none") to avoid 404 errors in some browsers.
		$content = preg_replace('/url\((["\']?)none(["\']?)\)/is', 'none', $content);

		// Remove member links with negative member_srl, for privacy protection.
		$content = preg_replace('/member\_\-([0-9]+)/s', 'member_0', $content);

		// Log the time taken for content transformations.
		Debug::addTime('trans_content', microtime(true) - $start);

		// Add OpenGraph and Twitter metadata.
		if (config('seo.og_enabled') && Context::get('module') !== 'admin')
		{
			HTMLDisplayHandler::_addOpenGraphMetadata();
			if (config('seo.twitter_enabled'))
			{
				HTMLDisplayHandler::_addTwitterMetadata();
			}
		}

		// Add favicon and mobile icon links.
		$site_module_info = Context::get('site_module_info');
		Context::set('favicon_url', AdminIconModel::getFaviconUrl(intval($site_module_info->domain_srl ?? 0)));
		Context::set('mobicon_url', AdminIconModel::getMobiconUrl(intval($site_module_info->domain_srl ?? 0)));

		// If somebody is still using IE 11, force the latest rendering engine.
		if (preg_match('!Trident/7\.0!', $_SERVER['HTTP_USER_AGENT'] ?? ''))
		{
			Context::addMetaTag('X-UA-Compatible', 'IE=edge', true);
		}

		// Wrap in an HTML document structure with all assets and meta tags.
		Context::set('content', $content);
		FrontEndFileHandler::loadCommonFiles();
		$oTemplate = new Template('./common/tpl', 'common_layout');
		$content = $oTemplate->compile();

		// Replace all user-defined lang codes.
		$content = Context::replaceUserLang($content);

		// Remove template compilation comments.
		/*
		if (!Rhymix\Framework\Debug::isEnabledForCurrentUser())
		{
			$content = preg_replace('/\n<!-- Template (?:start|end) : .*? -->\r?\n/', "\n", $content);
		}
		*/

		// Set the legacy response method to HTML.
		Context::setResponseMethod('HTML');

		return $content;
	}

	/**
	 * Insert the preserved value into <input>.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _preserveValue(array $matches): string
	{
		$vars = Context::get('INPUT_ERROR');
		if (!is_scalar($vars[$matches[3]]))
		{
			return $matches[0];
		}

		$str = $matches[1] . $matches[2] . ' name="' . $matches[3] . '"' . $matches[4];
		$type = preg_match('/\stype="([^"]+)"/i', $str, $m) ? strtolower($m[1]) : 'text';

		if ($type === 'radio' || $type === 'checkbox')
		{
			if (preg_match('@\s(?i:value)="' . preg_quote($vars[$matches[3]], '@') . '"@', $str))
			{
				$str = preg_replace('@\schecked(="[^"]*?")?@', ' checked="checked"', $str);
			}
		}
		else
		{
			if (!preg_match('@\svalue="([^"]*?)"@', $str))
			{
				$str = $str . ' value=""';
			}
			$str = preg_replace_callback('@\svalue="([^"]*?)"@', function() use($vars, $matches) {
				return ' value="' . escape($vars[$matches[3]], true) . '"';
			}, $str);
		}

		return $str . ' />';
	}

	/**
	 * Select the <option> that matches the preserved value in a <select>.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _preserveSelectValue(array $matches): string
	{
		preg_replace('@\sselected(="[^"]*?")?@', ' ', $matches[0]);
		preg_match('@<select.*?>@is', $matches[0], $mm);
		preg_match_all('@<option[^>]*\svalue="([^"]*)".+</option>@isU', $matches[0], $m);

		$vars = Context::get('INPUT_ERROR');
		$key = array_search($vars[$matches[1]], $m[1]);
		if ($key === false)
		{
			return $matches[0];
		}

		$m[0][$key] = preg_replace('@(\svalue=".*?")@is', '$1 selected="selected"', $m[0][$key]);
		return $mm[0] . implode('', $m[0]) . '</select>';
	}

	/**
	 * Insert the preserved value into <textarea>.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _preserveTextareaValue(array $matches): string
	{
		$vars = Context::get('INPUT_ERROR');
		preg_match('@<textarea.*?>@is', $matches[0], $mm);
		return $mm[0] . escape($vars[$matches[1]], true) . '</textarea>';
	}

	/**
	 * Move <style> in the document body to the <head> section.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _moveStyleToHeader(array $matches): string
	{
		if (isset($matches[1]) && stristr($matches[1], 'scoped'))
		{
			return $matches[0];
		}

		Context::addHtmlHeader($matches[0]);
		return '';
	}

	/**
	 * Move <link> and <meta> in the document body to the <head> section.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _moveLinkToHeader(array $matches): string
	{
		if ($matches[1] === 'link' &&
			preg_match('/\brel="([^"]+)"/', $matches[2], $rel) && $rel[1] !== 'stylesheet' &&
			preg_match('/\bhref="([^"]+)"/', $matches[2], $href))
		{
			Context::addLink($href[1], $rel[1]);
		}
		else
		{
			Context::addHtmlHeader($matches[0]);
		}

		return '';
	}

	/**
	 * Extract asset names from meta comments left by widgets, and load them.
	 *
	 * @param array $matches
	 * @return string
	 */
	protected function _transMeta(array $matches): string
	{
		if ($matches[1])
		{
			return '';
		}

		if ($matches[3] ?? false)
		{
			$vars = Context::get(str_replace('?$__Context->', '', $matches[3]));
			Context::loadFile([$matches[2], null, null, null, $vars]);
		}
		else
		{
			Context::loadFile($matches[2]);
		}

		return '';
	}
}
