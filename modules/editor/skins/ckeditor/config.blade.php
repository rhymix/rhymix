@php

// Basic configuration
$ckconfig = new \stdClass;
$ckconfig->skin = $colorset;
$ckconfig->auto_dark_mode = $editor_auto_dark_mode ?? false;
$ckconfig->legacy_html_mode = $html_mode ?? false;
$ckconfig->language = str_replace('jp', 'ja', Context::getLangType());
$ckconfig->height = $editor_height ?? 100;
$ckconfig->toolbar = $editor_toolbar ?? 'default';
$ckconfig->hide_toolbar = $editor_toolbar_hide ?? false;
$ckconfig->focus = $editor_focus ?? false;
$ckconfig->ios_patch = (bool)preg_match('/i(Phone|Pad|Pod)/', $_SERVER['HTTP_USER_AGENT'] ?? '');
$ckconfig->allow_upload = $allow_fileupload ?? false;

// Plugin configuration
$ckconfig->add_plugins = $editor_additional_plugins ?: [];
$ckconfig->remove_plugins = $editor_remove_plugins ?: [];
if (!in_array('clipboard', $ckconfig->remove_plugins)) {
	$ckconfig->add_plugins[] = 'rx_paste';
}
if ($ckconfig->ios_patch) {
	$ckconfig->add_plugins[] = 'divarea';
	$ckconfig->add_plugins[] = 'ios_enterkey';
	$ckconfig->remove_plugins[] = 'enterkey';
}

// Font configuration
$ckconfig->default_font = $content_font ?: 'none';
$ckconfig->default_font_size = intval(preg_replace('/\D/', '', $content_font_size ?? '13'), 10);
$ckconfig->fonts = array_values(array_map('strval', $lang->edit->fontlist ?: []));
$ckconfig->font_sizes = [8, 9, 10, 11, 12, 13, 14, 15, 16, 18, 20, 24, 28, 32, 36, 40, 48];
if (!in_array($ckconfig->default_font, $ckconfig->fonts) && $ckconfig->default_font !== 'none') {
	array_unshift($ckconfig->fonts, $ckconfig->default_font);
}
if (!in_array($ckconfig->default_font_size, $ckconfig->font_sizes)) {
	$ckconfig->font_sizes[] = $ckconfig->default_font_size;
	sort($ckconfig->font_sizes);
}
foreach ($ckconfig->fonts as &$_font_name) {
	$_font_name = trim(array_first(explode(',', $_font_name, 2))) . '/' . $_font_name;
}
foreach ($ckconfig->font_sizes as &$_font_size) {
	$_font_size = $_font_size . '/' . $_font_size . 'px';
}

// CSS configuration
$ckconfig->css_files = array_values($editor_additional_css ?: []);
$ckconfig->css_content = '';
$ckconfig->css_vars = (object)[
	'colorset' => $colorset,
	'content_font' => $content_font ?: 'none',
	'content_font_size' => $content_font_size ?: '13',
	'content_line_height' => $content_line_height ?: 'none',
	'content_word_break' => $content_word_break ?: 'none',
	'content_paragraph_spacing' => $content_paragraph_spacing ?: 'none',
];

// Legacy editor component configuration
$ckconfig->enable_component = $enable_component ?? false;
$ckconfig->enable_default_component = $enable_default_component ?? false;
$ckconfig->components = [];
foreach ($component_list ?? [] as $component_name => $component) {
	$ckconfig->components[$component_name] = escape($component->title, false);
}

// Cache-busting timestamp
$ckconfig->custom_config_exists = file_exists(RX_BASEDIR . 'common/js/plugins/ckeditor/ckeditor/config.js');
$_filemtime1 = filemtime(RX_BASEDIR . 'common/js/plugins/ckeditor/ckeditor/ckeditor.js');
$_filemtime2 = $ckconfig->custom_config_exists ? filemtime(RX_BASEDIR . 'common/js/plugins/ckeditor/ckeditor/config.js') : 0;
$ckconfig->timestamp = max($_filemtime1, $_filemtime2, $ckconfig_timestamp ?? 0);

// Set initial min-height to prevent layout shift when editor is loaded.
if ($editor_toolbar_hide) {
	$ckconfig->initial_height = $editor_height + 55;
} elseif ($editor_toolbar === 'simple') {
	$ckconfig->initial_height = $editor_height + 71;
} else {
	$ckconfig->initial_height = $editor_height + 137;
}

if (str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'Firefox/')) {
	$ckconfig->initial_height += 2;
}

@endphp
