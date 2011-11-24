<?php
    /**
     * @class ColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file
     * 
     * Since the <column> tag supports different attributes depending on
     * the type of query (select, update, insert, delete) this is only
     * the base class for the classes that will model each type <column> tag. 
	 *
     **/

	class ColumnTag {
		var $name;
		
		function ColumnTag($name){	
			$this->name = $name;
		}
	}