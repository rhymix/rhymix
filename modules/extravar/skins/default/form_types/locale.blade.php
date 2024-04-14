@if ($type === 'country')
	@php
		$lang_type = Context::getLangType();
		$lang_sort = $lang_type === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH;
		$countries = Rhymix\Framework\i18n::listCountries($lang_sort);
	@endphp
	<select name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="select rx_ev_select rx_ev_select_country"
		style="{{ $definition->style }}"|if="$definition->style"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))>
		@foreach ($countries as $country)
			@php
				$country_name = $lang_type === 'ko' ? $country->name_korean : $country->name_english;
				$is_selected = strval($value) !== '' && $value === $country->iso_3166_1_alpha3;
			@endphp
			<option value="{{ $country->iso_3166_1_alpha3 }}" @selected($is_selected)>{{ $country_name }}</option>
		@endforeach
	</select>
@elseif ($type === 'language')
	@php
		$enabled_languages = Rhymix\Framework\Config::get('locale.enabled_lang');
		$supported_languages = Rhymix\Framework\Lang::getSupportedList();
	@endphp
	<select name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="select rx_ev_select rx_ev_select_language"
		style="{{ $definition->style }}"|if="$definition->style"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))>
		@foreach ($enabled_languages as $language)
			@php
				$is_selected = strval($value) !== '' && $value === $language;
			@endphp
			<option value="{{ $language }}" @selected($is_selected)>{{ $supported_languages[$language]['name'] }}</option>
		@endforeach
	</select>
@elseif ($type === 'timezone')
	@php
		$timezones = Rhymix\Framework\DateTime::getTimezoneList();
	@endphp
	<select name="{{ $input_name }}"
		id="{{ $input_id }}"|if="$input_id" class="select rx_ev_select rx_ev_select_timezone"
		style="{{ $definition->style }}"|if="$definition->style"
		@required(toBool($definition->is_required))
		@disabled(toBool($definition->is_disabled))
		@readonly(toBool($definition->is_readonly))>
		@foreach ($timezones as $timezone_code => $timezone_name)
			@php
				$is_selected = strval($value) !== '' && $value === $timezone_code;
			@endphp
			<option value="{{ $timezone_code }}" @selected($is_selected)>{{ $timezone_name }}</option>
		@endforeach
	</select>
@endif
