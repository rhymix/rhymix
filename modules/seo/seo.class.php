<?php
class seo extends ModuleObject
{
	public $SEO = array(
		'link' => array(),
		'meta' => array()
	);

	protected $canonical_url;

	private $triggers = array(
		array('display', 'seo', 'controller', 'triggerBeforeDisplay', 'before'),
		array('file.deleteFile', 'seo', 'controller', 'triggerAfterFileDeleteFile', 'after'),
		array('document.updateDocument', 'seo', 'controller', 'triggerAfterDocumentUpdateDocument', 'after'),
		array('document.deleteDocument', 'seo', 'controller', 'triggerAfterDocumentDeleteDocument', 'after')
	);

	public function getConfig()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('seo');

		if (!$config) $config = new stdClass;
		if (!$config->enable) $config->enable = 'Y';
		if (!$config->use_optimize_title) $config->use_optimize_title = 'N';
		if (!$config->ga_except_admin) $config->ga_except_admin = 'N';
		if (!$config->ga_track_subdomain) $config->ga_track_subdomain = 'N';
		if ($config->site_image)
		{
			$request_uri = Rhymix\Framework\URL::encodeIdna(Context::getRequestUri());
			$config->site_image_url = $request_uri . 'files/attach/site_image/' . $config->site_image;

			$site_image = Rhymix\Framework\Cache::get('seo:site_image');
			if (!$site_image)
			{
				$path = _XE_PATH_ . 'files/attach/site_image/';
				list($width, $height) = @getimagesize($path . $config->site_image);
				$site_image_dimension = array('width' => $width, 'height' => $height);
				Rhymix\Framework\Cache::set('seo:site_image', $site_image_dimension, 0, true);
			}
		}

		return $config;
	}

	public function addMeta($property, $content, $attr_name = 'property')
	{
		if (!$content) return;

		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($content);
		if (!in_array($property, array('og:url'))) {
			$content = htmlspecialchars($content);
			$content = preg_replace("/(\s+)/", ' ', $content);
		}

		$this->SEO['meta'][] = array('property' => $property, 'content' => $content, 'attr_name' => $attr_name);
	}

	public function addLink($rel, $href)
	{
		if (!$href) return;

		$this->SEO['link'][] = array('rel' => $rel, 'href' => $href);
	}

	protected function applySEO()
	{
		$config = $this->getConfig();
		$logged_info = Context::get('logged_info');

		foreach ($this->SEO as $type => $list) {
			if (!$list || !count($list)) continue;

			foreach ($list as $val) {
				if ($type == 'meta') {
					$attr_name = $val['attr_name'];
					Context::addHtmlHeader('<meta ' . $attr_name . '="' . $val['property'] . '" content="' . $val['content'] . '" />');
				} elseif ($type == 'link') {
					Context::addHtmlHeader('<link rel="' . $val['rel'] . '" href="' . $val['href'] . '" />');
				}
			}
		}

		// Google Analytics
		if ($config->ga_id && !($config->ga_except_admin == 'Y' && $logged_info->is_admin == 'Y')) {
			$gaq_push = array();
			// $gaq_push[] = '_gaq.push([\'_setAccount\', \'' . $config->ga_id . '\']);';
			$gaq_push[] = "ga('create', '{$config->ga_id}', 'auto');";
			$canonical_url = str_replace(Context::get('request_uri'), '/', $this->canonical_url);
			$gaq_push[] = "ga('send', 'pageview', '{$canonical_url}');";
			$gaq_push = implode(PHP_EOL, $gaq_push);

			$ga_script = <<< GASCRIPT
<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

{$gaq_push}
</script>
GASCRIPT;

			Context::addHtmlHeader($ga_script . PHP_EOL);
		}

		// Naver Analytics
		if ($config->na_id && !($config->na_except_admin == 'Y' && $logged_info->is_admin == 'Y')) {
			$na_script = <<< NASCRIPT
<!-- NAVER Analytics -->
<script src="//wcs.naver.net/wcslog.js"></script>
<script>if(!wcs_add){var wcs_add={wa:'{$config->na_id}'};}if(typeof wcs_do!="undefined"){wcs_do();}</script>
NASCRIPT;
			Context::addHtmlFooter($na_script . PHP_EOL);
		}
	}

	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		$seo_config = $this->getConfig();

		if($seo_config->enable === 'Y') {
			foreach ($this->triggers as $trigger) {
				if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return TRUE;
			}
		}

		return FALSE;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		$seo_config = $this->getConfig();

		if($seo_config->enable === 'Y') {
			foreach ($this->triggers as $trigger) {
				if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) {
					$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}
		}

		return new Object(0, 'success_updated');
	}

	function moduleUninstall()
	{
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger) {
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		return new Object();
	}
}
/* !End of file */
