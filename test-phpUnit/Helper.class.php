<?php 

	class Helper {
		function cleanQuery($query){
			$query = trim(preg_replace('/\s+/', ' ',$query));
			$query = str_replace(" , ", ', ', $query);
			$query = str_replace("( ", '(', $query);
			$query = str_replace(" )", ')', $query);
			$query = strtolower($query);		
			return $query;	
		}
	}

?>