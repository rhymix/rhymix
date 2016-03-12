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
			
			// Support HTML5 and CSS3.
			self::_supportHTML5($config);
			self::_supportCSS3($config);
			
			// Cache our instance of HTMLPurifier.
			self::$_htmlpurifier = new \HTMLPurifier($config);
		}
		
		// Return the cached instance.
		return self::$_htmlpurifier;
	}
	
	/**
	 * Patch HTMLPurifier to support some HTML5 tags and attributes.
	 * 
	 * These changes are based on https://github.com/xemlock/htmlpurifier-html5
	 * but modified to support even more tags and attributes.
	 * 
	 * @param object $config
	 * @return void
	 */
	protected static function _supportHTML5($config)
	{
		// Get the HTML definition.
		$def = $config->getHTMLDefinition(true);
		
		// Add various block-level tags.
		$def->addElement('header', 'Block', 'Flow', 'Common');
		$def->addElement('footer', 'Block', 'Flow', 'Common');
		$def->addElement('nav', 'Block', 'Flow', 'Common');
		$def->addElement('main', 'Block', 'Flow', 'Common');
		$def->addElement('section', 'Block', 'Flow', 'Common');
		$def->addElement('article', 'Block', 'Flow', 'Common');
		$def->addElement('aside', 'Block', 'Flow', 'Common');
		
		// Add various inline tags.
		$def->addElement('s', 'Inline', 'Inline', 'Common');
		$def->addElement('sub', 'Inline', 'Inline', 'Common');
		$def->addElement('sup', 'Inline', 'Inline', 'Common');
		$def->addElement('mark', 'Inline', 'Inline', 'Common');
		$def->addElement('wbr', 'Inline', 'Empty', 'Core');
		
		// Support figures.
		$def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
		$def->addElement('figcaption', 'Inline', 'Flow', 'Common');
		
		// Support insertions and deletions.
		$def->addElement('ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'Text'));
		$def->addElement('del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'Text'));
		
		// Support the <time> tag.
		$time = $def->addElement('time', 'Inline', 'Inline', 'Common', array('datetime' => 'Text', 'pubdate' => 'Bool'));
		$time->excludes = array('time' => true);
		
		// Suppport <audio> and <video> tags. DO NOT ALLOW AUTOPLAY.
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
		
		// Support additional properties.
		$def->addAttribute('img', 'srcset', 'Text');
		$def->addAttribute('iframe', 'allowfullscreen', 'Bool');
	}
	
	/**
	 * Patch HTMLPurifier to support more CSS2 and some CSS3 properties.
	 * 
	 * These changes are based on:
	 *   - https://github.com/mattiaswelander/htmlpurifier
	 * 
	 * @param object $config
	 * @return void
	 */
	protected static function _supportCSS3($config)
	{
		// Initialize $info.
		$info = array();
		
		// min-width, max-width, etc.
		$info['min-width'] = $info['max-width'] = $info['min-height'] =
		$info['max-height'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(0),
			new \HTMLPurifier_AttrDef_Enum(array('none', 'initial', 'inherit')),
		));
		
		// border-radius, etc.
		$border_radius = $info['border-top-left-radius'] =
		$info['border-top-right-radius'] = $info['border-bottom-left-radius'] =
		$info['border-bottom-right-radius'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(0),
			new \HTMLPurifier_AttrDef_CSS_Percentage(true),
			new \HTMLPurifier_AttrDef_Enum(array('initial', 'inherit')),
		));
		$info['border-radius'] = new \HTMLPurifier_AttrDef_CSS_Multiple($border_radius);
		
		// word-break word-wrap, etc.
		$info['word-break'] = new \HTMLPurifier_AttrDef_Enum(array(
			'normal', 'break-all', 'keep-all', 'initial', 'inherit',
		));
		$info['word-wrap'] = new \HTMLPurifier_AttrDef_Enum(array(
			'normal', 'break-word', 'initial', 'inherit',
		));
		$info['text-overflow'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_Enum(array('clip', 'ellipsis', 'initial', 'inherit')),
		));
		
		// text-shadow
		$info['text-shadow'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Multiple(new \HTMLPurifier_AttrDef_CSS_Composite(array(
				new \HTMLPurifier_AttrDef_CSS_Length(),
				new \HTMLPurifier_AttrDef_CSS_Color(),
			))),
			new \HTMLPurifier_AttrDef_Enum(array('none', 'initial', 'inherit')),
		));
		
		// box-shadow and box-sizing
		$info['box-shadow'] = new \HTMLPurifier_AttrDef_CSS_Multiple(new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(),
			new \HTMLPurifier_AttrDef_CSS_Percentage(),
			new \HTMLPurifier_AttrDef_CSS_Color(),
			new \HTMLPurifier_AttrDef_Enum(array('none', 'inset', 'initial', 'inherit')),
		)));
		$info['box-sizing'] = new \HTMLPurifier_AttrDef_Enum(array(
			'content-box', 'border-box', 'initial', 'inherit',
		));
		
		// outline
		$info['outline-color'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Color(),
			new \HTMLPurifier_AttrDef_Enum(array('invert', 'initial', 'inherit')),
		));
		$info['outline-offset'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(),
			new \HTMLPurifier_AttrDef_Enum(array('initial', 'inherit')),
		));
		$info['outline-style'] = new \HTMLPurifier_AttrDef_Enum(array(
			'none', 'hidden', 'dotted', 'dashed', 'solid', 'double', 'groove', 'ridge', 'inset', 'outset', 'initial', 'inherit',
		));
		$info['outline-width'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(),
			new \HTMLPurifier_AttrDef_Enum(array('medium', 'thin', 'thick', 'initial', 'inherit')),
		));
		$info['outline'] = new \HTMLPurifier_AttrDef_CSS_Multiple(new \HTMLPurifier_AttrDef_CSS_Composite(array(
			$info['outline-color'], $info['outline-style'], $info['outline-width'],
			new \HTMLPurifier_AttrDef_Enum(array('initial', 'inherit')),
		)));
		
		// flexbox
		$info['display'] = new \HTMLPurifier_AttrDef_Enum(array(
            'block', 'flex', '-webkit-flex', 'inline', 'inline-block', 'inline-flex', '-webkit-inline-flex', 'inline-table',
            'list-item', 'run-in', 'compact', 'marker', 'table', 'table-row-group', 'table-header-group', 'table-footer-group',
			'table-row', 'table-column-group', 'table-column', 'table-cell', 'table-caption',
			'none', 'initial', 'inherit',
		));
		$info['order'] = new \HTMLPurifier_AttrDef_CSS_Number();
		$info['align-content'] = $info['justify-content'] = new \HTMLPurifier_AttrDef_Enum(array(
			'stretch', 'center', 'flex-start', 'flex-end', 'space-between', 'space-around', 'initial', 'inherit',
		));
		$info['align-items'] = $info['align-self'] = new \HTMLPurifier_AttrDef_Enum(array(
			'stretch', 'center', 'flex-start', 'flex-end', 'baseline', 'initial', 'inherit',
		));
		$info['flex-basis'] = new \HTMLPurifier_AttrDef_CSS_Composite(array(
			new \HTMLPurifier_AttrDef_CSS_Length(),
			new \HTMLPurifier_AttrDef_CSS_Percentage(),
			new \HTMLPurifier_AttrDef_Enum(array('auto', 'initial', 'inherit')),
		));
		$info['flex-direction'] = new \HTMLPurifier_AttrDef_Enum(array(
			'row', 'row-reverse', 'column', 'column-reverse', 'initial', 'inherit',
		));
		$info['flex-wrap'] = new \HTMLPurifier_AttrDef_Enum(array(
			'nowrap', 'wrap', 'wrap-reverse', 'initial', 'inherit',
		));
		$info['flex-flow'] = new \HTMLPurifier_AttrDef_CSS_Multiple(new \HTMLPurifier_AttrDef_CSS_Composite(array(
			$info['flex-direction'], $info['flex-wrap'],
		)));
		$info['flex-grow'] = new \HTMLPurifier_AttrDef_CSS_Number();
		$info['flex-shrink'] = new \HTMLPurifier_AttrDef_CSS_Number();
		$info['flex'] = new \HTMLPurifier_AttrDef_CSS_Multiple(new \HTMLPurifier_AttrDef_CSS_Composite(array(
			$info['flex-grow'], $info['flex-shrink'], $info['flex-basis'],
			new \HTMLPurifier_AttrDef_Enum(array('auto', 'none', 'initial', 'inherit')),
		)));
		
		// misc
		$info['caption-side'] = new \HTMLPurifier_AttrDef_Enum(array(
			'top', 'bottom', 'initial', 'inherit',
		));
		$info['empty-cells'] = new \HTMLPurifier_AttrDef_Enum(array(
			'show', 'hide', 'initial', 'inherit',
		));
		$info['hanging-punctuation'] = new \HTMLPurifier_AttrDef_Enum(array(
			'none', 'first', 'last', 'allow-end', 'force-end', 'initial', 'inherit',
		));
		$info['overflow'] = $info['overflow-x'] = $info['overflow-y'] = new \HTMLPurifier_AttrDef_Enum(array(
			'visible', 'hidden', 'scroll', 'auto', 'initial', 'inherit',
		));
		$info['resize'] = new \HTMLPurifier_AttrDef_Enum(array(
			'none', 'both', 'horizontal', 'vertical', 'initial', 'inherit',
		));
		
		// Wrap all new properties with a decorator that handles !important.
		$allow_important = $config->get('CSS.AllowImportant');
		$css_definition = $config->getCSSDefinition();
		foreach ($info as $key => $val)
		{
			$css_definition->info[$key] = new \HTMLPurifier_AttrDef_CSS_ImportantDecorator($val, $allow_important);
		}
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
