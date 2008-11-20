<?php
    /**
     * @class  editorAPI
     * @author SOL군 (ngleader.com)
     * @brief 
     **/

    class editorAPI extends editor {
        function dispEditorSkinColorset(&$oModule) {
            $oModule->add('colorset', Context::get('colorset'));
        }
    }
?>
