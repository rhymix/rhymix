@load('../assets/file_upload.js')

<div class="ev_file_upload">

	@if ($value)
		@php
			$file = FileModel::getFile(intval($value));
		@endphp
		@if ($file)
			<div class="ev_file_info">
				<span class="filename">{{ $file->source_filename }}</span>
				<span class="filesize">({{ FileHandler::filesize($file->file_size) }})</span>
				<input type="hidden" name="_delete_{{ $input_name }}" value="N" />
				<button type="button" class="btn evFileRemover">{{ lang('cmd_delete') }}</button>
			</div>
		@endif
	@endif

	<div class="ev_file_input">
		<input type="file" name="{{ $input_name }}"
			id="{{ $input_id }}"|if="$input_id" class="file rx_ev_file"
			style="{{ $definition->style }}"|if="$definition->style"
			@required(toBool($definition->is_required) && !$value)
			@disabled(toBool($definition->is_disabled))
			@readonly(toBool($definition->is_readonly))
		/>
	</div>

</div>
