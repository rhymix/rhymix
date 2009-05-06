<?php
    /**
     * @class  planetSmartphone
     * @author zero (skklove@gmail.com)
     * @brief  planet 모듈의 SmartPhone class
     **/

    class planetSPhone extends planet {

        function procSmartPhone(&$oSmartPhone) {
            $prev_date = Context::get('prev_date');
            if($prev_date) $oSmartPhone->setPrevUrl(getUrl('date',$prev_date, 'document_srl',''));
            $next_date = Context::get('next_date');
            if($next_date) $oSmartPhone->setNextUrl(getUrl('date',$next_date, 'document_srl',''));

            $oTemplate = new TemplateHandler();
            $content = $oTemplate->compile($this->module_path.'tpl', 'smartphone');
            $oSmartPhone->setContent($content);
        }
    }
?>
