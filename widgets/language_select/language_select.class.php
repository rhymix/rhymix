<?php
    /**
     * @class language_select 
     * @author NHN (developers@xpressengine.com)
     * @brief Language selector
     * @version 0.1
     **/

    class language_select extends WidgetHandler {

        /**
         * @brief Widget execution
         *
         * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
         * After generating the result, do not print but return it.
         **/
        function proc($args) {
            // Set a path of the template skin (values of skin, colorset settings)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'language_select';
			
			Context::set('colorset', $args->colorset);

            // Compile a template
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
