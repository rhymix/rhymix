<?php 

// Only supports queries inside modules for now

	class QueryTester {

		function QueryTester(){
			
		}
		
		function getQueryPath($type, $name, $query_name){
			return _XE_PATH_ . $type ."/".$name."/queries/" . $query_name . ".xml";
		}
		
		function getNewParserOutput($xml_file){
			$newXmlQueryParser = new XmlQueryParser();
			$xml_obj = $newXmlQueryParser->getXmlFileContent($xml_file);		
			$parser = new QueryParser($xml_obj->query);
			return $parser->toString();
		}
		
		function getOldParserOutput($query_id, $xml_file){
			$cache_file = _TEST_PATH_ . "cache/".$query_id.'.cache.php';
			$parser = new OldXmlQueryParser();
			$parser->parse($query_id, $xml_file, $cache_file);
			$buff = FileHandler::readFile($cache_file);
			return $buff;		
		}
		
		function cleanOutputAndAddArgs($outputString, $argsString = ''){
			$outputString = str_replace("<?php if(!defined('__ZBXE__')) exit();", "", $outputString);
			$outputString = str_replace("?>", "", $outputString);
			$outputString = $argsString . $outputString;			
			return $outputString;
		}
		
		function getXmlFileContent($xml_file){
			return FileHandler::readFile($xml_file);			
		}
		
		function printOutput($output){
			if(is_object($output)) {
				var_dump($output); return;
			}
			$output = htmlspecialchars($output);
			
			$output = preg_replace('/select/i', 'SELECT', $output);
			$output = preg_replace('/from/i', '<br/>FROM', $output);
			$output = preg_replace('/where/i', '<br/>WHERE', $output);			
			$output = preg_replace('/group by/i', '<br/>GROUP BY', $output);
			$output = preg_replace('/order by/i', '<br/>ORDER BY', $output);
			
			$output = str_replace("\n", "<br/>", $output);
			
			echo '<pre class=prettyprint>'
					.$output
				.'</pre>';	
		}
		
		function getNewParserOutputString($xml_file, $argsString){
			$outputString = '';
			$outputString = $this->getNewParserOutput($xml_file);
			$outputString = $this->cleanOutputAndAddArgs($outputString, $argsString);
			return $outputString;			
		}
		
		function getNewParserQuery($outputString){	
			//echo $outputString;
			//exit(0);
			$output = eval($outputString);
			if(is_a($output, 'Object'))
				if(!$output->toBool()) return("Date incorecte! Query-ul nu a putut fi executat.");
			$db = new DBCubrid();
			if($output->getAction() == 'select')
				return $db->getSelectSql($output);
			else if($output->getAction() == 'insert')
				return $db->getInsertSql($output);
			else if($output->getAction() == 'update')
				return $db->getUpdateSql($output);
			else if($output->getAction() == 'delete')
				return $db->getDeleteSql($output);
		}
		
		function testNewParser($xml_file, $escape_char, $argsString, $show_output_string){
			$outputString = $this->getNewParserOutputString($xml_file, $escape_char, $argsString);
			$query = $this->getNewParserQuery($outputString);
			
			echo '<tr>';
			if($show_output_string){
				echo '<td>';
				$this->printOutput($outputString);
				echo '</td>';
			}
			
			echo '<td>';
			$this->printOutput($query);
			echo '</td>';
			echo '</tr>';			
		}

		function getOldParserOutputString($query_id, $xml_file, $argsString){
			$outputString = $this->getOldParserOutput($query_id, $xml_file);
			$outputString = $this->cleanOutputAndAddArgs($outputString, $argsString);
			return $outputString;			
		}
		
		function getOldParserQuery($outputString){
			$output = eval($outputString);
			if(is_a($output, 'Object'))
				if(!$output->toBool()) exit("Date incorecte! Query-ul nu a putut fi executat.");
			
			/*	SQL Server
			 * 
			$db = new DBMssql(false);
			if($output->action == "select")
				return $db->_executeSelectAct($output);
			else if($output->action == "insert")
				return $db->_executeInsertAct($output);
			else if($output->action == "delete")
				return $db->_executeDeleteAct($output);				
			else if($output->action == "update")
				return $db->_executeUpdateAct($output);
			*/		

			/*
			 * Mysql  
			 */
			$db = new DBMysql(false);
			if($output->action == "select")
				$db->_executeSelectAct($output);
			else if($output->action == "insert")
				$db->_executeInsertAct($output);
			else if($output->action == "delete")
				$db->_executeDeleteAct($output);				
			else if($output->action == "update")
				$db->_executeUpdateAct($output);				
			return $db->getLatestQuery();
		}
		
		function testOldParser($query_id, $xml_file, $argsString, $show_output_string){
			$outputString = $this->getOldParserOutputString($query_id, $xml_file, $argsString);
			$query = $this->getOldParserQuery($outputString);
			
			
			echo '<tr>';
			if($show_output_string){
				echo '<td>';
				$this->printOutput($outputString);
				echo '</td>';
			}
			
			echo '<td>';
			$this->printOutput($query);
			echo '</td>';
			echo '</tr>';					
		}
		
		function showXmlInputFile($xml_file){
			echo '<tr colspan=2>';
			echo '<td>';
			$xml_file_content = $this->getXmlFileContent($xml_file);
			$this->printOutput($xml_file_content);		
			echo '</td></tr>';	
		}	
		
		function test($query_id, $xml_file, $argsString, $show_output_string, $escape_char = '"'){
			echo "<h3>$query_id</h3>";
			echo '<table border=1 cellpadding=5 cellspacing=0 width=50%>';

			$this->showXmlInputFile($xml_file);

			$this->testNewParser($xml_file, $escape_char, $argsString, $show_output_string);
			
			//$this->testOldParser($query_id, $xml_file, $argsString, $show_output_string);
			
			echo '</table>';			
		}
			
		function test_addon_getAddonInfo($show_output_string = false){
			$argsString = '$args->addon = "captcha";';
			$this->test("modules.addon.getAddonInfo"
						, $this->getQueryPath("modules", "addon", "getAddonInfo")
						, $argsString
						, $show_output_string);
		}
		
		function test_addon_getAddons($show_output_string = false){
			$argsString = '';
			$this->test("modules.addon.getAddons"
						, $this->getQueryPath("modules", "addon", "getAddons")
						, $argsString
						, $show_output_string);
		}
				
		function test_admin_getCommentCount($show_output_string = false){
			$argsString = '';
			$this->test("modules.admin.getCommentCount"
						, $this->getQueryPath("modules", "admin", "getCommentCount")
						, $argsString
						, $show_output_string);			
		}		
		
		function test_admin_getCommentDeclaredStatus($show_output_string = false){
			$argsString = '$args->date = "20110411";';
			$this->test("modules.admin.getCommentDeclaredStatus"
						, $this->getQueryPath("modules", "admin", "getCommentDeclaredStatus")
						, $argsString
						, $show_output_string);						
		}		

		function test_module_getDefaultModules($show_output_string = false){
			$argsString = '';
			$this->test("modules.module.getDefaultModules"
						, $this->getQueryPath("modules", "module", "getDefaultModules")
						, $argsString
						, $show_output_string);				
		}
		
		function test_module_getModuleCategories($show_output_string = false){
			$argsString = '';
			$this->test("modules.module.getModuleCategories"
						, $this->getQueryPath("modules", "module", "getModuleCategories")
						, $argsString
						, $show_output_string);				
		}		
		
		function test_module_getNonuniqueDomains($show_output_string = false){
			$argsString = '';
			$this->test("modules.module.getNonuniqueDomains"
						, $this->getQueryPath("modules", "module", "getNonuniqueDomains")
						, $argsString
						, $show_output_string);				
		}		

		function test_module_getAdminId($show_output_string = false){
			$argsString = '$args->module_srl = 23;';
			$this->test("modules.module.getAdminId"
						, $this->getQueryPath("modules", "module", "getAdminId")
						, $argsString
						, $show_output_string);				
		}			
		function test_module_getSiteInfo($show_output_string = false){
			$argsString = '$args->site_srl = 0;';
			$this->test("modules.module.getSiteInfo"
						, $this->getQueryPath("modules", "module", "getSiteInfo")
						, $argsString
						, $show_output_string);				
		}
		function test_module_insertModule($show_output_string = false){
			$argsString = ' $args->module_category_srl = 0; 
							$args->browser_title = "test";
							$args->layout_srl = 0;
							$args->mlayout_srl = 0;
							$args->module = "page";
							$args->mid = "test";
							$args->site_srl = 0;
							$args->module_srl = 47374;';
			$this->test("modules.module.insertModule"
						, $this->getQueryPath("modules", "module", "insertModule")
						, $argsString
						, $show_output_string);				
		}
		function test_module_updateModule($show_output_string = false){
			$argsString = ' $args->module_category_srl = 0; 
							$args->browser_title = "test";
							$args->layout_srl = 0;
							$args->mlayout_srl = 0;
							$args->module = "page";
							$args->mid = "test";
							$args->use_mobile = "";
							$args->site_srl = 0;
							$args->module_srl = 47374;';
			$this->test("modules.module.updateModule"
						, $this->getQueryPath("modules", "module", "updateModule")
						, $argsString
						, $show_output_string);				
		}
		function test_admin_deleteActionForward($show_output_string = false){
			$argsString = '$args->module = "page";
							$args->type = "page";
							$args->act = "tata";';
			$this->test("modules.admin.deleteActionForward"
						, $this->getQueryPath("modules", "module", "deleteActionForward")
						, $argsString
						, $show_output_string);			
		}
		
		function test_member_getAutologin($show_output_string = false){
			$argsString = '$args->autologin_key = 10;';
			$this->test("modules.member.getAutologin"
						, $this->getQueryPath("modules", "member", "getAutologin")
						, $argsString
						, $show_output_string);					
		}
		
		function test_opage_getOpageList($show_output_string = false){
			$argsString = '$args->s_title = "yuhuu";
							$args->module = 12;';
			$this->test("modules.opage.getOpageList"
						, $this->getQueryPath("modules", "opage", "getOpageList")
						, $argsString
						, $show_output_string);					
		}
		function test_getPageList($show_output_string = false){
			$argsString = '$args->sort_index = "module_srl";
            $args->page_count = 10;
            $args->s_module_category_srl = 0;
			$args->s_mid = "test";
			$args->s_browser_title = "caca";';

			$this->test("modules.page.getPageList"
						, $this->getQueryPath("modules", "page", "getPageList")
						, $argsString
						, $show_output_string);					
		}
		
		

	}
?>