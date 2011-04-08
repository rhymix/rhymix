<?php

class pageMobile extends ModuleObject {
	function init() {
		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');
	}

	function dispPageIndex() {
		// Arrange a widget ryeolro
		if($this->module_info->mcontent)
		{
            $cache_file = sprintf("%sfiles/cache/page/%d.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
            $interval = (int)($this->module_info->page_caching_interval);
            if($interval>0) {
                if(!file_exists($cache_file)) $mtime = 0;
                else $mtime = filemtime($cache_file);

                if($mtime + $interval*60 > time()) {
                    $page_content = FileHandler::readFile($cache_file); 
					$page_content = preg_replace('@<\!--#Meta:@', '<!--Meta:', $page_content);
                } else {
                    $oWidgetController = &getController('widget');
                    $page_content = $oWidgetController->transWidgetCode($this->module_info->mcontent);
                    FileHandler::writeFile($cache_file, $page_content);
                }
            } else {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
                $page_content = $this->module_info->mcontent;
            }
            Context::set('content', $page_content);
		}
		else
		{
			preg_match_all('!(<img)([^\>]*)(widget=)([^\>]*?)(\>)!is', $this->module_info->content, $matches);
			$content = '';
			for($i=0,$c=count($matches[0]);$i<$c;$i++) {
				$content .= preg_replace('/ style\=\"([^\"]+)\" /i',' style="overflow:hidden;clear:both;margin:0 0 20px 0; _margin-right:10px;" ',$matches[0][$i])."\n\n";
			}
			Context::set('content', $content);
		}
		$this->setTemplateFile('mobile');
	}
}

?>
