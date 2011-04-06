<?php
    /**
     * @class counter_status
     * @author NHN (developers@xpressengine.com)
     * @version 0.1
     * @brief Display counter status by using data in the counter module
     **/

    class counter_status extends WidgetHandler {

        /**
         * @brief Widget execution
         * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
         * After generating the result, do not print but return it.
         **/
        function proc($args) {
            // Get status of the accumulated, yesterday's, today's counts
            $oCounterModel = &getModel('counter');

            $site_module_info = Context::get('site_module_info');
            $output = $oCounterModel->getStatus(array('00000000', date('Ymd', time()-60*60*24), date('Ymd')), $site_module_info->site_srl);
            foreach($output as $key => $val) {
                if(!$key) Context::set('total_counter', $val);
                elseif($key == date("Ymd")) Context::set('today_counter', $val);
                else Context::set('yesterday_counter', $val);
            }
            // Set a path of the template skin (values of skin, colorset settings)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);
            // Specify a template file
            $tpl_file = 'counter_status';
            // Compile a template
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
