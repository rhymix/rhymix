<?php

class pageMobile extends ModuleObject {
	function init() {
		// 템플릿 경로 구함 (page의 경우 tpl에 관리자용 템플릿 모아놓음)
		$this->setTemplatePath($this->module_path.'tpl');
	}

	function dispPageIndex() {
		// 위젯을 1렬로 정렬 
		if($this->module_info->mcontent)
		{
            $cache_file = sprintf("%sfiles/cache/page/%d.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
            $interval = (int)($this->module_info->page_caching_interval);
            if($interval>0) {
                if(!file_exists($cache_file)) $mtime = 0;
                else $mtime = filemtime($cache_file);

                if($mtime + $interval*60 > time()) {
                    $page_content = FileHandler::readFile($cache_file); 
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
