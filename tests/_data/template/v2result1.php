<?php if (!defined("RX_VERSION")) exit(); ?><?php $this->config->version = 2; ?>


<div><?php (function($__dir, $__path, $__vars = null) { $__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html"); if ($__vars) $__tpl->setVars($__vars); echo $__tpl->compile(); })("common/tpl", 'refresh.html'); ?></div>
<div><?php \Context::loadJavascriptPlugin('ckeditor'); ?></div>
<?php \Context::loadFile(['./tests/_data/template/css/style.css', 'print', '', '', []]); ?>

<?php
	$__Context->foo = 'FOOFOOFOO';
?>
<?php
	$__Context->bar = ['Rhy', 'miX', 'is', 'da', 'BEST!'];
?>

	{{ $foo }}


<form action="<?php echo htmlspecialchars(\RX_BASEURL, \ENT_QUOTES, 'UTF-8', false); ?>" method="post">
	<input type="hidden" name="_rx_csrf_token" value="<?php echo \Rhymix\Framework\Session::getGenericToken(); ?>" />
	<input type="text"<?php if (Context::getInstance()->get('foo')): ?> required="required"<?php endif; ?>>
	<input type="text" value="<?php echo htmlspecialchars($__Context->bar[0] ?? '', \ENT_QUOTES, 'UTF-8', false); ?>"<?php if ($__Context->bar[3] === 'da'): ?> required="required"<?php endif; ?> />
</form>

<div<?php if (!(isset($__Context->baz))): ?> class="foobar"<?php endif; ?>>
<?php if ($__Context->foo || $__Context->bar): ?>
		<p>Hello <?php echo $__Context->foo ?? ''; ?></p>
		<p><?php echo htmlspecialchars(implode('|', array_map(function($i) { return strtoupper($i); }, $__Context->bar)), \ENT_QUOTES, 'UTF-8', false); ?></p>
<?php endif; ?>
</div>

<?php $__tmp_042521f3da7d65 = Context::get('bar') ?? []; if($__tmp_042521f3da7d65): foreach ($__tmp_042521f3da7d65 as $__Context->k => $__Context->val): ?>
<div>
<?php if (empty($__Context->nosuchvar)): ?>
		<img src="/rhymix/tests/_data/template/bar/rhymix.svg" alt="unit tests are cool" />
		<span <?php if ($__Context->k >= 2): ?>class="<?php echo htmlspecialchars($__Context->val ?? '', \ENT_QUOTES, 'UTF-8', false); ?>"<?php endif; ?>></span>
<?php endif; ?>
</div>
<?php endforeach; else: ?>
	<div>Nothing here...</div>
<?php endif; ?>

<?php (function($__dir, $__path, $__vars, $__varname, $__empty = null) { if (!$__vars): $__vars = []; if ($__empty): $__path = $__empty; $__vars[] = ''; endif; endif; foreach ($__vars as $__var): $__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html"); $__tpl->setVars([(string)$__varname => $__var]); echo $__tpl->compile(); endforeach; })($this->relative_dirname, 'incl/eachtest', $__Context->bar, 'var'); ?>
<?php (function($__dir, $__path, $__vars, $__varname, $__empty = null) { if (!$__vars): $__vars = []; if ($__empty): $__path = $__empty; $__vars[] = ''; endif; endif; foreach ($__vars as $__var): $__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html"); $__tpl->setVars([(string)$__varname => $__var]); echo $__tpl->compile(); endforeach; })($this->relative_dirname, 'incl/eachtest', [], 'anything', 'incl/empty'); ?>

<?php if (!$__Context->m): ?>
	<p>The full class name is <?php echo htmlspecialchars(get_class(new Rhymix\Framework\Push), \ENT_QUOTES, 'UTF-8', true); ?>, <?php echo htmlspecialchars(Rhymix\Framework\Push::class, \ENT_QUOTES, 'UTF-8', false); ?> really.</p>
<?php endif; ?>

<div class="barContainer" data-bar="<?php echo $this->config->context === 'JS' ? (json_encode($__Context->bar ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)) : htmlspecialchars(json_encode($__Context->bar ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, 'UTF-8', false); ?>">
	<span<?php (function(array $__defs) { $__values = []; foreach ($__defs as $__key => $__val): if (is_numeric($__key)): $__values[] = $__val; elseif ($__val): $__values[] = $__key; endif; endforeach; if ($__values): echo ' class="'; echo htmlspecialchars(implode(' ', $__values), \ENT_QUOTES, 'UTF-8', false); echo '"'; endif; })((['a-1', 'font-normal' => $__Context->foo, 'text-blue' => false, 'bg-white' => true])); ?>></span>
	<span<?php (function(array $__defs) { $__values = []; foreach ($__defs as $__key => $__val): if (is_numeric($__key)): $__values[] = $__val; elseif ($__val): $__values[] = $__key; endif; endforeach; if ($__values): echo ' style="'; echo htmlspecialchars(implode('; ', $__values), \ENT_QUOTES, 'UTF-8', false); echo '"'; endif; })((['border-radius: 0.25rem', 'margin: 1rem' => Context::get('bar'), 'padding: 2rem' => false])); ?>></span>
</div>

<script type="text/javascript"<?php $this->config->context = "JS"; ?>>
	const bar = <?php echo $this->config->context === 'JS' ? json_encode($__Context->bar, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : htmlspecialchars(json_encode($__Context->bar, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, 'UTF-8', false); ?>;
<?php $this->config->context = "HTML"; ?></script>
