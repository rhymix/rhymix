@if ($value)
	@php
		$file = FileModel::getFile(intval($value));
	@endphp
	@if ($file)
		<div class="uploaded_file">
			<span class="filename">{{ $file->source_filename }}</span>
			<span class="filesize">({{ FileHandler::filesize($file->file_size) }})</span>
			<label>
				<input type="checkbox" name="_delete_{{ $input_name }}" value="Y" />
				@lang('common.cmd_delete')
			</label>
		</div>
	@endif
@endif

<input type="file" name="{{ $input_name }}"
	id="{{ $input_id }}"|if="$input_id" class="file rx_ev_file"
	style="{{ $definition->style }}"|if="$definition->style"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))
/>
