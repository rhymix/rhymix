@if ($type === 'homepage' || $type === 'url')
	<input type="url" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="homepage rx_ev_url"
		style="{{ $definition->style }}"|if="$definition->style"
		value="{{ strval($value) !== '' ? $value : $default }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@elseif ($type === 'email_address' || $type === 'email')
	<input type="email" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="email_address rx_ev_email"
		style="{{ $definition->style }}"|if="$definition->style"
		value="{{ strval($value) !== '' ? $value : $default }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@elseif ($type === 'number')
	<input type="number" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="number rx_ev_number"
		style="{{ $definition->style }}"|if="$definition->style"
		value="{{ strval($value) !== '' ? $value : $default }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@else
	<input type="text" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="text rx_ev_text"
		style="{{ $definition->style }}"|if="$definition->style"
		value="{{ strval($value) !== '' ? $value : $default }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@endif
