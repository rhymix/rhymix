@if ($type === 'tel')
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[0] }}" size="4" maxlength="4" class="tel rx_ev_tel1" style="{{ $definition->style }}"|if="$definition->style" />
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="4" maxlength="4" class="tel rx_ev_tel2" style="{{ $definition->style }}"|if="$definition->style" />
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[2] }}" size="4" maxlength="4" class="tel rx_ev_tel3" style="{{ $definition->style }}"|if="$definition->style" />
@elseif ($type === 'tel_v2')
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[0] }}" size="16" maxlength="16"
		class="rx_ev_tel_v2" style="{{ $definition->style }}"|if="$definition->style"
		pattern="^[0-9\s\(\)\.\+\-]*$"
	/>
@elseif ($type === 'tel_intl' || $type === 'tel_intl_v2')
	@php
		$lang_type = Context::getLangType();
		$lang_sort = $lang_type === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH;
		$countries = Rhymix\Framework\i18n::listCountries($lang_sort);
		$tempid = $definition->getNextTempID();
		if (is_array($value) && count($value) && ctype_alpha(end($value))) {
			$selected_iso_code = end($value);
		} else {
			$selected_iso_code = null;
		}
	@endphp
	<select name="{{ $input_name }}[]"
		id="{{ $tempid }}" class="select rx_ev_select rx_ev_select_country"
		onchange="jQuery('#{{ $tempid }}_iso_code').val(jQuery(this).find('option:selected').data('isoCode'))">
  		<option value=""></option>
		@foreach ($countries as $country)
			@php
				$country_name = $lang_type === 'ko' ? $country->name_korean : $country->name_english;
				if ($selected_iso_code) {
					$is_selected = $selected_iso_code === $country->iso_3166_1_alpha3;
				} else {
					$is_selected = strval($value[0] ?? '') !== '' && $value[0] === $country->calling_code;
					if ($is_selected) {
						$selected_iso_code = $country->iso_3166_1_alpha3;
					}
				}
			@endphp
			<option value="{{ $country->calling_code }}" data-iso-code="{{ $country->iso_3166_1_alpha3 }}" @selected($is_selected)>
				{{ $country_name }} (+{{ $country->calling_code }})
			</option>
		@endforeach
	</select>
	@if ($type === 'tel_intl')
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="4" maxlength="4" class="tel rx_ev_tel1" style="{{ $definition->style }}"|if="$definition->style" />
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[2] }}" size="4" maxlength="4" class="tel rx_ev_tel2" style="{{ $definition->style }}"|if="$definition->style" />
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[3] }}" size="4" maxlength="4" class="tel rx_ev_tel3" style="{{ $definition->style }}"|if="$definition->style" />
	@else
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="16" maxlength="16"
			class="rx_ev_tel_v2" style="{{ $definition->style }}"|if="$definition->style"
			pattern="^[0-9\s\(\)\.\+\-]*$"
		/>
	@endif
	<input type="hidden" name="{{ $input_name }}[]" id="{{ $tempid }}_iso_code" value="{{ $selected_iso_code }}" />
@endif
