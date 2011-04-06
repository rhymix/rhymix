<?php
    /**
     * @class  multimedia_link
     * @author NHN (developers@xpressengine.com)
     * @brief The components connected to the body of multimedia data
     **/

    class multimedia_link extends EditorHandler { 
        // editor_sequence from the editor must attend mandatory wearing ....
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence and components out of the path
         **/
        function multimedia_link($editor_sequence, $component_path) {
            $this->editor_sequence = $editor_sequence;
            $this->component_path = $component_path;
        }

        /**
         * @brief popup window to display in popup window request is to add content
         **/
        function getPopupContent() {
            // Pre-compiled source code to compile template return to
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            Context::set("tpl_path", $tpl_path);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief Editor of the components separately if you use a unique code to the html code for a method to change
         *
         * Images and multimedia, seolmundeung unique code is required for the editor component added to its own code, and then
         * DocumentModule:: transContent() of its components transHtml() method call to change the html code for your own
         **/
        function transHTML($xml_obj) {
            $src = $xml_obj->attrs->multimedia_src;
            $style = $xml_obj->attrs->style;

            preg_match_all('/(width|height)([^[:digit:]]+)([0-9]+)/i',$style,$matches);
            $width = trim($matches[3][0]);
            $height = trim($matches[3][1]);
            if(!$width) $width = 400;
            if(!$height) $height = 400;

            $auto_start = $xml_obj->attrs->auto_start;
            if($auto_start!="true") $auto_start = "false";
            else $auto_start = "true";

            $wmode = $xml_obj->attrs->wmode;
            if($wmode == 'window') $wmode = 'window';
            elseif($wmode == 'opaque') $wmode = 'opaque';
            else $wmode = 'transparent';
            

            $caption = $xml_obj->body;

            $src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
            $src = str_replace('&amp;amp;', '&amp;', $src);

            if(Context::getResponseMethod() != "XMLRPC") return sprintf("<script type=\"text/javascript\">displayMultimedia(\"%s\", \"%s\",\"%s\", { \"autostart\" : %s, \"wmode\" : \"%s\" });</script>", $src, $width, $height, $auto_start, $wmode);
            else return sprintf("<div style=\"width: %dpx; height: %dpx;\"><span style=\"position:relative; top:%dpx;left:%d\"><img src=\"%s\" /><br />Attached Multimedia</span></div>", $width, $height, ($height/2-16), ($width/2-31), Context::getRequestUri().'./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif');
        }
    }
?>
