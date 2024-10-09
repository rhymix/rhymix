@php
	$has_value = is_array($value);
	$default_value = $definition->getDefaultValue();
@endphp

@if ($parent_type === 'member')
	<div class="rx_ev_{{ $type }}" style="padding-top:5px">
		@foreach ($definition->getOptions() as $v)
			@php
				$column_suffix = $type === 'checkbox' ? '[]' : '';
				$tempid = $definition->getNextTempID();
				$is_checked = $has_value ? in_array($v, $value) : ($v === $default_value);
			@endphp
			<label for="{{ $tempid }}">
				<input type="{{ $type }}" name="{{ $input_name . $column_suffix }}"
					id="{{ $tempid }}" value="{{ $v }}"
					style="{{ $definition->style }}"|if="$definition->style"
					@checked($is_checked)
					@disabled(toBool($definition->is_disabled))
					@readonly(toBool($definition->is_readonly))
				/> {{ $v }}
			</label>
		@endforeach
	</div>
@else
	<ul class="rx_ev_{{ $type }}">
		@foreach ($definition->getOptions() as $v)
			@php
				$column_suffix = $type === 'checkbox' ? '[]' : '';
				$tempid = $definition->getNextTempID();
				$is_checked = $has_value ? in_array($v, $value) : ($v === $default_value);
			@endphp
			<li>
				<input type="{{ $type }}" name="{{ $input_name . $column_suffix }}"
					id="{{ $tempid }}" class="{{ $type }}" value="{{ $v }}"
					style="{{ $definition->style }}"|if="$definition->style"
					@checked($is_checked)
					@disabled(toBool($definition->is_disabled))
					@readonly(toBool($definition->is_readonly))
				/><label for="{{ $tempid }}">{{ $v }}</label>
			</li>
		@endforeach
	</ul>
@endif
