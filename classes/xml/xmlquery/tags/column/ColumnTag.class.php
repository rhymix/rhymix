<?php
	/**
	 * ColumnTag class
	 * Models the <column> tag inside an XML Query file
	 * Since the <column> tag supports different attributes depending on
	 * the type of query (select, update, insert, delete) this is only
	 * the base class for the classes that will model each type <column> tag. 
	 *
	 * @author Arnia Software
	 * @package /classes/xml/xmlquery/tags/column
	 * @version 0.1
	 */
	class ColumnTag {
		/**
		 * Column name
		 * @var string
		 */
		var $name;
		
		/**
		 * constructor
		 * @param string $name
		 * @return void
		 */
		function ColumnTag($name){	
			$this->name = $name;
		}
	}
