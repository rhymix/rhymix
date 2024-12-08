@php
	$has_value = is_array($value);
	$default_value = $definition->getDefaultValue();
@endphp

<select name="{{ $input_name }}"
	id="{{ $input_id }}"|if="$input_id" class="select rx_ev_select"
	style="{{ $definition->style }}"|if="$definition->style"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))>
	<option value="">@lang('cmd_select')</option>
	@foreach ($definition->getOptions() as $v)
		<option value="{{ $v }}" @selected($has_value ? in_array($v, $value) : ($v === $default_value))>{{ $v }}</option>
	@endforeach
</select>
