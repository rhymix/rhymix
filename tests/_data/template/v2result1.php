<?php if (!defined("RX_VERSION")) exit(); ?><?php $this->config->version = 2; ?>


<?php (function($__dir, $__path, $__vars = null) { $__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html"); if ($__vars) $__tpl->setVars($__vars); echo $__tpl->compile(); })("common/tpl", 'refresh.html'); ?>
<?php \Context::loadJavascriptPlugin('ckeditor'); ?>
<?php \Context::loadFile(['./tests/_data/template/css/style.css', '', '', '', []]); ?>

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

<?php $__tmp_19148045bac5d8 = Context::get('bar') ?? []; if($__tmp_19148045bac5d8): foreach ($__tmp_19148045bac5d8 as $__Context->k => $__Context->val): ?>
<div>
<?php if (empty($__Context->nosuchvar)): ?>
		<img src="/rhymix/tests/_data/template/bar/rhymix.svg" alt="unit tests are cool" />
		<span <?php if ($__Context->k >= 2): ?>class="<?php echo htmlspecialchars($__Context->val ?? '', \ENT_QUOTES, 'UTF-8', false); ?>"<?php endif; ?>></span>
<?php endif; ?>
</div>
<?php endforeach; else: ?>
	<div>Nothing here...</div>
<?php endif; ?>

<?php if (!$__Context->m): ?>
	<p>The full class name is <?php echo htmlspecialchars(get_class(new Rhymix\Framework\Push), \ENT_QUOTES, 'UTF-8', true); ?>, <?php echo htmlspecialchars(Rhymix\Framework\Push::class, \ENT_QUOTES, 'UTF-8', false); ?> really.</p>
<?php endif; ?>

<div class="barContainer" data-bar="<?php echo $this->config->context === 'JS' ? (json_encode($__Context->bar ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)) : htmlspecialchars(json_encode($__Context->bar ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, 'UTF-8', false); ?>"></div>

<script type="text/javascript"<?php $this->config->context = "JS"; ?>>
	const bar = <?php echo $this->config->context === 'JS' ? json_encode($__Context->bar, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : htmlspecialchars(json_encode($__Context->bar, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, 'UTF-8', false); ?>;
<?php $this->config->context = "HTML"; ?></script>
