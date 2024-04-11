@include ('header.blade.php')

<form action="./" class="x_form-horizontal" method="post">
	<input type="hidden" name="module" value="member" />
	<input type="hidden" name="act" value="procExtravarAdminInsertConfig" />
	<input type="hidden" name="success_return_url" value="{{ getUrl(['module' => 'admin', 'act' => $act]) }}" />
	<input type="hidden" name="xe_validator_id" value="modules/extravar/views/config/1" />
	@if ($XE_VALIDATOR_MESSAGE && $XE_VALIDATOR_ID === 'modules/extravar/views/config/1')
		<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
			<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
		</div>
	@endif
	<div class="x_control-group">
		<label class="x_control-label" for="skin">{{ $lang->skin }}</label>
		<div class="x_controls">
			<select id="skin" name="skin">
				@foreach ($skin_list as $key => $val)
					<option value="{{ $key }}" @selected(isset($config->skin) && $key === $config->skin)>
						{{ $val->title }} ({{ $key }})
					</option>
				@endforeach
			</select>
		</div>
	</div>
	<div class="x_clearfix btnArea">
		<span class="x_pull-right">
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</span>
	</div>
</form>
