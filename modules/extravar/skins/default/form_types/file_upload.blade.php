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

	@php
		$file_config = FileModel::getUploadConfig($definition->module_srl);
		$allowed_filetypes = strtr($file_config->allowed_filetypes ?? '', ['*.' => '.', ';' => ',']);
		$allowed_filesize = ($file_config->allowed_filesize ?? 0) * 1024 * 1024;
	@endphp

	<div class="ev_file_input">
		<input type="file" name="{{ $input_name }}"
			id="{{ $input_id }}"|if="$input_id" class="file rx_ev_file"
			style="{{ $definition->style }}"|if="$definition->style"
			accept="{{ $allowed_filetypes }}"|if="$allowed_filetypes !== '' && $allowed_filetypes !== '.*'"
			data-allowed-filesize="{{ $allowed_filesize }}"
			data-msg-filesize="{{ lang('file.msg_exceeds_limit_size') }}"
			@required(toBool($definition->is_required) && !$value)
			@disabled(toBool($definition->is_disabled))
			@readonly(toBool($definition->is_readonly))
		/>
	</div>

</div>
