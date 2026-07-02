@load('../assets/file_upload.css')
@load('../assets/file_upload.js')

<div class="ev_image_upload">

	@if ($value)
		<div class="ev_image_info">
			<div class="thumbnail">
				<img src="{{ $value->uploaded_filename }}" alt="{{ $file->source_filename }}" />
			</div>
			<input type="hidden" name="_delete_{{ $input_name }}" value="N" />
			<button type="button" class="btn evFileRemover">{{ lang('cmd_delete') }}</button>
		</div>
	@endif

	<div class="ev_image_input">
		<input type="file" name="{{ $input_name }}"
			id="{{ $input_id }}"|if="$input_id" class="file rx_ev_image" accept="image/*"
			style="{{ $definition->style }}"|if="$definition->style"
			@required(toBool($definition->is_required) && !$value)
			@disabled(toBool($definition->is_disabled))
			@readonly(toBool($definition->is_readonly))
		/>
	</div>

</div>
