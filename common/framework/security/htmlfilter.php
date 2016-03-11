<?php

namespace Rhymix\Framework\Security;

/**
 * The HTML filter class.
 */
class HTMLFilter
{
	/**
	 * HTMLPurifier instance is cached here.
	 */
	protected static $_htmlpurifier;
	
	/**
	 * Pre-processing and post-processing filters are stored here.
	 */
	protected static $_preproc = array();
	protected static $_postproc = array();
	
	/**
	 * Prepend a pre-processing filter.
	 * 
	 * @param callable $callback
	 * @return void
	 */
	public static function prependPreFilter($callback)
	{
		array_unshift(self::$_preproc, $callback);
	}
	
	/**
	 * Append a pre-processing filter.
	 * 
	 * @param callable $callback
	 * @return void
	 */
	public static function appendPreFilter($callback)
	{
		self::$_preproc[] = $callback;
	}
	
	/**
	 * Prepend a post-processing filter.
	 * 
	 * @param callable $callback
	 * @return void
	 */
	public static function prependPostFilter($callback)
	{
		array_unshift(self::$_postproc, $callback);
	}
	
	/**
	 * Append a post-processing filter.
	 * 
	 * @param callable $callback
	 * @return void
	 */
	public static function appendPostFilter($callback)
	{
		self::$_postproc[] = $callback;
	}
	
	/**
	 * Filter HTML content to block XSS attacks.
	 * 
	 * @param string $input
	 * @return string
	 */
	public static function clean($input)
	{
		foreach (self::$_preproc as $callback)
		{
			$input = $callback($input);
		}
		
		$input = self::_encodeWidgetsAndEditorComponents($input);
		$output = self::getHTMLPurifier()->purify($input);
		$output = self::_decodeWidgetsAndEditorComponents($output);
		
		foreach (self::$_postproc as $callback)
		{
			$output = $callback($output);
		}
		
		return $output;
	}
	
	/**
	 * Get an instance of HTMLPurifier.
	 * 
	 * @return object
	 */
	public static function getHTMLPurifier()
	{
		// Create an instance with reasonable defaults.
		if (self::$_htmlpurifier === null)
		{
			// Get the default configuration.
			$config = \HTMLPurifier_Config::createDefault();
			
			// Customize the default configuration.
			$config->set('Attr.AllowedFrameTargets', array('_blank'));
			$config->set('Attr.DefaultImageAlt', '');
			$config->set('Attr.EnableID', false);
			$config->set('AutoFormat.AutoParagraph', false);
			$config->set('AutoFormat.DisplayLinkURI', false);
			$config->set('AutoFormat.Linkify', false);
			$config->set('Core.Encoding', 'UTF-8');
			$config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
			$config->set('HTML.FlashAllowFullScreen', true);
			$config->set('HTML.MaxImgLength', null);
			$config->set('CSS.MaxImgLength', null);
			$config->set('CSS.Proprietary', true);
			$config->set('Output.FlashCompat', true);
			$config->set('Output.Newline', "\n");
			$config->set('URI.MakeAbsolute', false);
			
			// Allow embedding of external multimedia content.
			$config->set('HTML.SafeEmbed', true);
			$config->set('HTML.SafeIframe', true);
			$config->set('HTML.SafeObject', true);
			$config->set('URI.SafeIframeRegexp', self::_getIframeWhitelist());
			
			// Set the serializer path.
			$config->set('Cache.SerializerPath', RX_BASEDIR . 'files/cache/htmlpurifier');
			\FileHandler::makeDir(RX_BASEDIR . 'files/cache/htmlpurifier');
			
			// Modify the HTML definition to support editor components and widgets.			
			$def = $config->getHTMLDefinition(true);
			$def->addAttribute('img', 'editor_component', 'Text');
			$def->addAttribute('img', 'rx_encoded_properties', 'Text');
			$def->addAttribute('div', 'rx_encoded_properties', 'Text');
			
			// Support HTML5: Based on https://github.com/xemlock/htmlpurifier-html5
			$def->addAttribute('img', 'srcset', 'Text');
			$def->addAttribute('iframe', 'allowfullscreen', 'Bool');
			$def->addElement('header', 'Block', 'Flow', 'Common');
			$def->addElement('footer', 'Block', 'Flow', 'Common');
			$def->addElement('nav', 'Block', 'Flow', 'Common');
			$def->addElement('main', 'Block', 'Flow', 'Common');
			$def->addElement('section', 'Block', 'Flow', 'Common');
			$def->addElement('article', 'Block', 'Flow', 'Common');
			$def->addElement('aside', 'Block', 'Flow', 'Common');
			$def->addElement('address', 'Block', 'Flow', 'Common');
			$def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
			$def->addElement('figcaption', 'Inline', 'Flow', 'Common');
			$def->addElement('s', 'Inline', 'Inline', 'Common');
			$def->addElement('var', 'Inline', 'Inline', 'Common');
			$def->addElement('sub', 'Inline', 'Inline', 'Common');
			$def->addElement('sup', 'Inline', 'Inline', 'Common');
			$def->addElement('mark', 'Inline', 'Inline', 'Common');
			$def->addElement('wbr', 'Inline', 'Empty', 'Core');
			$def->addElement('ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'Text'));
			$def->addElement('del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'Text'));
			$time = $def->addElement('time', 'Inline', 'Inline', 'Common', array('datetime' => 'Text', 'pubdate' => 'Bool'));
			$time->excludes = array('time' => true);
			$def->addElement('audio', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
				'src' => 'URI',
				'type' => 'Text',
				'preload' => 'Enum#auto,metadata,none',
				'controls' => 'Bool',
				'muted' => 'Bool',
				'loop' => 'Bool',
			));
			$def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
				'src' => 'URI',
				'type' => 'Text',
				'width' => 'Length',
				'height' => 'Length',
				'poster' => 'URI',
				'preload' => 'Enum#auto,metadata,none',
				'controls' => 'Bool',
				'muted' => 'Bool',
				'loop' => 'Bool',
			));
			$def->addElement('source', 'Block', 'Empty', 'Common', array(
				'src' => 'URI',
				'media' => 'Text',
				'type' => 'Text',
			));
			$def->addElement('track', 'Block', 'Empty', 'Common', array(
				'src' => 'URI',
				'srclang' => 'Text',
				'label' => 'Text',
				'kind' => 'Enum#captions,chapters,descriptions,metadata,subtitles',
				'default' => 'Bool',
			));
			
			// Cache our instance of HTMLPurifier.
			self::$_htmlpurifier = new \HTMLPurifier($config);
		}
		
		// Return the cached instance.
		return self::$_htmlpurifier;
	}
	
	/**
	 * Get the iframe whitelist as a regular expression.
	 * 
	 * @return string
	 */
	protected static function _getIframeWhitelist()
	{
		$domains = \EmbedFilter::getInstance()->getWhiteIframeUrlList();
		$result = array();
		foreach($domains as $domain)
		{
			$result[] = preg_quote($domain, '%');
		}
		return '%^https?://(' . implode('|', $result) . ')%';
	}
	
	/**
	 * Encode widgets and editor components before processing.
	 * 
	 * @param string $content
	 * @return string
	 */
	protected static function _encodeWidgetsAndEditorComponents($content)
	{
		preg_match_all('!<(div|img)([^>]*)(editor_component="[^"]+"|class="zbxe_widget_output")([^>]*)>!i', $content, $matches, \PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$attrs = array();
			$html = $match[0];
			preg_match_all('/([a-zA-Z0-9_-]+)="([^"]+)"/', $match[2] . ' ' . $match[4], $found_attrs, \PREG_SET_ORDER);
			foreach ($found_attrs as $attr)
			{
				$attrkey = strtolower($attr[1]);
				if (strtolower($match[1]) === 'img' && ($attrkey === 'width' || $attrkey === 'height' || $attrkey === 'alt'))
				{
					continue;
				}
				if ($attrkey === 'src' || $attrkey === 'style' || substr($attrkey, 0, 2) === 'on')
				{
					continue;
				}
				$attrs[$attrkey] = htmlspecialchars_decode($attr[2]);
				$html = str_replace($attr[0], '', $html);
			}
			if (strtolower($match[1]) === 'img' && !isset($attrs['src']))
			{
				//$html = substr($html, 0, 4) . ' src=""' . substr($html, 4);
			}
			$encoded_properties = base64_encode(json_encode($attrs));
			$html = substr($html, 0, 4) . ' rx_encoded_properties="' . $encoded_properties . '"' . substr($html, 4);
			$content = str_replace($match[0], $html, $content);
		}
		return $content;
	}
	
	/**
	 * Decode widgets and editor components after processing.
	 * 
	 * @param string $content
	 * @return string
	 */
	protected static function _decodeWidgetsAndEditorComponents($content)
	{
		preg_match_all('!<(div|img)([^>]*)(rx_encoded_properties="([^"]+)")!i', $content, $matches, \PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$attrs = array();
			$decoded_properties = @json_decode(base64_decode($match[4])) ?: array();
			foreach ($decoded_properties as $key => $val)
			{
				$attrs[] = $key . '="' . htmlspecialchars($val) . '"';
			}
			$content = str_replace($match[3], implode(' ', $attrs), $content);
		}
		return $content;
	}
}
