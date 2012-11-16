<?php
/**
 * @class  editorAPI
 * @author NHN (developers@xpressengine.com)
 * @brief 
 */
class editorAPI extends editor
{
	function dispEditorSkinColorset(&$oModule)
	{
		$oModule->add('colorset', Context::get('colorset'));
	}
}
/* End of file editor.api.php */
/* Location: ./modules/editor/editor.api.php */
