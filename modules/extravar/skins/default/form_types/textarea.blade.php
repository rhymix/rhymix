<textarea name="{{ $input_name }}"
	id="{{ $input_id }}"|if="$input_id" class="rx_ev_textarea"
	style="{{ $definition->style }}"|if="$definition->style"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))
	rows="8" cols="42">{{ $value !== null ? $value : $definition->getDefaultValue() }}</textarea>
