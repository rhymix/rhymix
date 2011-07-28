<?php 
	require('config/config.inc.php');        
  
	$oDB = &DB::getInstance('mssql');
	//$oDB = &DB::getInstance();
	$dbParser = $oDB->getParser();
	$dbParser = new DBParser('[', ']');
	$parser = new QueryParser($xml_obj->query, $dbParser);
	$query_file = $parser->toString();
	var_dump($parser->toString());

?>