@if ($type === 'homepage' || $type === 'url')
	<input type="url" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="homepage rx_ev_url"
		value="{{ $value }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@elseif ($type === 'email_address' || $type === 'email')
	<input type="email" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="email_address rx_ev_email"
		value="{{ $value }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@else
	<input type="text" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="text rx_ev_text"
		value="{{ strval($value) !== '' ? $value : $default }}"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
@endif
