<?php

class i18nTest extends \Codeception\TestCase\Test
{
	public function testListCountries()
	{
		$sort_code_2 = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_CODE_2);
		$this->assertEquals('AD', array_first($sort_code_2)->iso_3166_1_alpha2);
		$this->assertEquals('AND', array_first_key($sort_code_2));
		
		$sort_code_3 = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_CODE_3);
		$this->assertEquals('ABW', array_first($sort_code_3)->iso_3166_1_alpha3);
		$this->assertEquals('ABW', array_first_key($sort_code_3));
		
		$sort_code_n = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_CODE_NUMERIC);
		$this->assertEquals('004', array_first($sort_code_n)->iso_3166_1_numeric);
		$this->assertEquals('AFG', array_first_key($sort_code_n));
		
		$sort_cctld = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_CCTLD);
		$this->assertEquals('zw', array_last($sort_cctld)->cctld);
		$this->assertEquals('ZWE', array_last_key($sort_cctld));
		
		$sort_english = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
		$this->assertEquals('Afghanistan', array_first($sort_english)->name_english);
		$this->assertEquals('AFG', array_first_key($sort_english));
		$this->assertEquals('Åland Islands', array_last($sort_english)->name_english);
		$this->assertEquals('ALA', array_last_key($sort_english));
		
		$sort_korean = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_NAME_KOREAN);
		$this->assertEquals('가나', array_first($sort_korean)->name_korean);
		$this->assertEquals('GHA', array_first_key($sort_korean));
		$this->assertEquals('홍콩', array_last($sort_korean)->name_korean);
		$this->assertEquals('HKG', array_last_key($sort_korean));
		
		$sort_native = Rhymix\Framework\i18n::listCountries(Rhymix\Framework\i18n::SORT_NAME_NATIVE);
		$this->assertEquals('Amerika Sāmoa', array_first($sort_native)->name_native);
		$this->assertEquals('대한민국', $sort_native['KOR']->name_korean);
		$this->assertEquals('United States of America', $sort_native['USA']->name_english);
		$this->assertEquals('nz', $sort_native['NZL']->cctld);
	}
}
