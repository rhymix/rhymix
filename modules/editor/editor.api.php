<?php
    /**
     * @class  editorAPI
     * @author SOL군 (ngleader.com)
     * @brief 
     **/

    class editorAPI extends editor {
        function dispEditorAdminSkinColorset(&$oModule) {
            $oModule->add('colorset', Context::get('colorset'));
        }
    }
?>