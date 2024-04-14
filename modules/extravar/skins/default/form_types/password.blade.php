<input type="password" name="{{ $input_name }}"
	id="{{ $input_id }}"|if="$input_id" class="password rx_ev_password"
	style="{{ $definition->style }}"|if="$definition->style"
	value="{{ strval($value) !== '' ? $value : $default }}"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))
/>
