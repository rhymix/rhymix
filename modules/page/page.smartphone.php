<?php
    /**
     * @class  pageSmartphone
     * @author zero (skklove@gmail.com)
     * @brief  page 모듈의 SmartPhone class
     **/

    class pageSPhone extends page {

        function procSmartPhone(&$oSmartPhone)
        {
            if(!$this->grant->access) return $oSmartPhone->setContent(Context::getLang('msg_not_permitted'));

            // 위젯을 1렬로 정렬 
            preg_match_all('!(<img)([^\>]*)(widget=)([^\>]*?)(\>)!is', $this->module_info->content, $matches);
            $content = '';
            for($i=0,$c=count($matches[0]);$i<$c;$i++) {
                $content .= preg_replace('/ style\=\"([^\"]+)\" /i',' style="overflow:hidden;clear:both;margin:0 0 20px 0; _margin-right:10px;" ',$matches[0][$i])."\n\n";
            }
            Context::set('content', $content);

            $oTemplate = new TemplateHandler();
            $content = $oTemplate->compile($this->module_path.'tpl','smartphone');

            $oSmartPhone->setContent($content);
        }
    }
?>
