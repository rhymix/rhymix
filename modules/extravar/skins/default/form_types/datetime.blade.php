@if ($type === 'date')
	@php
		$formatted_value = $value ? sprintf('%s-%s-%s', substr($value, 0, 4), substr($value, 4, 2), substr($value, 6, 2)) : '';
	@endphp
	<input type="hidden" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="rx_ev_date"
		value="{{ $value }}"
	/>
	<input type="date" class="date" value="{{ $formatted_value }}"
		pattern="\d{4}-\d{2}-\d{2}" placeholder="YYYY-MM-DD"
		style="{{ $definition->style }}"|if="$definition->style"
		onchange="jQuery(this).prev('.rx_ev_date').val(this.value.replace(/-/g, ''));"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
	<button type="button" class="btn dateRemover"
		onclick="jQuery(this).prev('.date').val('').trigger('change');return false;">
		{{ lang('cmd_delete') }}
	</button>
@else
	<input type="time" name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="rx_ev_time"
		value="{{ $value }}" pattern="\d{2}:\d{2}"
		style="{{ $definition->style }}"|if="$definition->style"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))
	/>
	<button type="button" class="btn timeRemover"
		onclick="jQuery(this).prev('.rx_ev_time').val('');return false;">
		{{ lang('cmd_delete') }}
	</button>
@endif
