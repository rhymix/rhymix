@if ($type === 'tel')
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[0] }}" size="4" maxlength="4" class="tel rx_ev_tel1" style="{{ $definition->style }}"|if="$definition->style" />
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="4" maxlength="4" class="tel rx_ev_tel2" style="{{ $definition->style }}"|if="$definition->style" />
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[2] }}" size="4" maxlength="4" class="tel rx_ev_tel3" style="{{ $definition->style }}"|if="$definition->style" />
@elseif ($type === 'tel_v2')
	<input type="tel" name="{{ $input_name }}[]" value="{{ $value[0] }}" size="16" maxlength="16" class="rx_ev_tel_v2" style="{{ $definition->style }}"|if="$definition->style" />
@elseif ($type === 'tel_intl' || $type === 'tel_intl_v2')
	@php
		$lang_type = Context::getLangType();
		$lang_sort = $lang_type === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH;
		$countries = Rhymix\Framework\i18n::listCountries($lang_sort);
	@endphp
	<select name="{{ $input_name }}[]" class="select rx_ev_select rx_ev_select_country" style="{{ $definition->style }}"|if="$definition->style">
  		<option value=""></option>
		@foreach ($countries as $country)
			@php
				$country_name = $lang_type === 'ko' ? $country->name_korean : $country->name_english;
				$is_selected = strval($value[0] ?? '') !== '' && $value[0] === $country->calling_code;
			@endphp
			<option value="{{ $country->calling_code }}" @selected($is_selected)>{{ $country_name }} (+{{ $country->calling_code }})</option>
		@endforeach
	</select>
	@if ($type === 'tel_intl')
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="4" maxlength="4" class="tel rx_ev_tel1" style="{{ $definition->style }}"|if="$definition->style" />
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[2] }}" size="4" maxlength="4" class="tel rx_ev_tel2" style="{{ $definition->style }}"|if="$definition->style" />
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[3] }}" size="4" maxlength="4" class="tel rx_ev_tel3" style="{{ $definition->style }}"|if="$definition->style" />
	@else
		<input type="tel" name="{{ $input_name }}[]" value="{{ $value[1] }}" size="16" maxlength="16" class="rx_ev_tel_v2" style="{{ $definition->style }}"|if="$definition->style" />
	@endif
@endif
