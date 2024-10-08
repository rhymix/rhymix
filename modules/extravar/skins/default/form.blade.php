@switch ($type)
	@case ('text')
		@include ('form_types/text')
		@break
	@case ('textarea')
		@include ('form_types/textarea')
		@break
	@case ('password')
		@include ('form_types/password')
		@break
	@case ('select')
		@include ('form_types/select')
		@break
	@case ('radio')
	@case ('checkbox')
		@include ('form_types/checkbox')
		@break
	@case ('tel')
	@case ('tel_v2')
	@case ('tel_intl')
	@case ('tel_intl_v2')
		@include ('form_types/tel')
		@break
	@case ('homepage')
	@case ('url')
		@include ('form_types/text')
		@break
	@case ('email_address')
	@case ('email')
		@include ('form_types/text')
		@break
	@case ('kr_zip')
		@include ('form_types/kr_zip')
		@break
	@case ('country')
	@case ('language')
	@case ('timezone')
		@include ('form_types/locale')
		@break
	@case ('date')
	@case ('time')
		@include ('form_types/datetime')
		@break
	@case ('file')
		@include ('form_types/file_upload')
		@break
	@default
		@include ('form_types/text')
@endswitch

@if (!empty($definition->desc))
	<p class="rx_ev_description">
		{{ Context::replaceUserLang($definition->desc)|nl2br }}
	</p>
@endif
