@if (isset($iframe_sequence) && !empty($iframe_sequence))
<script>
	const iframe_sequence = '{{ $iframe_sequence }}';
	window.opener = window.parent;
	window.close = function() {
		parent.document.getElementById('editor_iframe_' + iframe_sequence).remove();
	};
</script>
<style>
	.x.popup { width: 100vw; height: 100vh; }
	.x.popup > div { width: 100vw; height: 100vh; display: flex; flex-direction: column; }
	.x_modal-header { flex: 0 0 auto; }
	.x_modal-body { flex: 1; overflow: scroll; }
</style>
@endif
