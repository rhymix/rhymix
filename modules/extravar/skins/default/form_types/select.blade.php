<select name="{{ $input_name }}"
	id="{{ $input_id }}"|if="$input_id" class="select rx_ev_select"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))>
	@foreach ($default ?: [] as $v)
		<option value="{{ $v }}" @selected(is_array($value) && in_array(trim($v), $value))>{{ $v }}</option>
	@endforeach
</select>
