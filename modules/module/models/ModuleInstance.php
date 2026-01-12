<?php

namespace Rhymix\Modules\Module\Models;

/**
 * This class represents an instance of a module,
 * i.e. a module that is invoked with a prefix (mid).
 */
class ModuleInstance extends ModuleInfo
{
	/*
	 * Default attributes.
	 */
	public int $module_srl = 0;
	public string $module = '';
	public int $module_category_srl = 0;
	public int $menu_srl = 0;
	public int $site_srl = 0;
	public int $domain_srl = -1;
	public string $mid = '';
	public int $layout_srl = 0;
	public int $mlayout_srl = 0;
	public string $use_mobile = 'N';
	public string $skin = '';
	public string $is_skin_fix = 'Y';
	public string $mskin = '';
	public string $is_mskin_fix = 'Y';
	public string $browser_title = '';
	public string $is_default = 'N';
	public string $open_rss = 'Y';
	public $description;
	public $content;
	public $mcontent;
	public $header_text;
	public $footer_text;
	public $regdate;
}
