@php
	$oKrzipModel = KrzipModel::getInstance();
@endphp

{!! $oKrzipModel->getKrzipCodeSearchHtml($input_name, $value) !!}
