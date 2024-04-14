<ul class="rx_ev_{{ $type }}">
	@foreach ($default ?? [] as $v)
		@php
			$column_suffix = $type === 'checkbox' ? '[]' : '';
			$tempid = $definition->getNextTempID();
			$is_checked = is_array($value) && in_array(trim($v), $value);
		@endphp
		<li>
			<input type="{{ $type }}" name="{{ $input_name . $column_suffix }}"
				id="{{ $tempid }}" class="{{ $type }}" value="{{ $v }}"
				style="{{ $definition->style }}"|if="$definition->style"
				@checked($is_checked)
				@required(toBool($definition->is_required))
				@disabled(toBool($definition->is_disabled))
				@readonly(toBool($definition->is_readonly))
			/><label for="{{ $tempid }}">{{ $v }}</label>
		</li>
	@endforeach
</ul>
