<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Models the &lt;column&gt; tag inside an XML Query file <br />
 * Since the &lt;column&gt; tag supports different attributes depending on
 * the type of query (select, update, insert, delete) this is only
 * the base class for the classes that will model each type <column> tag.
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class ColumnTag
{

	/**
	 * Column name
	 * @var string
	 */
	var $name;

	/**
	 * Constructor
	 * @param string $name
	 * @return void
	 */
	function ColumnTag($name)
	{
		$this->name = $name;
	}

}
/* End of file ColumnTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/column/ColumnTag.class.php */
