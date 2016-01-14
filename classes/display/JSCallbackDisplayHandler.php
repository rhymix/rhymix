<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class JSCallbackDisplayHandler
{

	/**
	 * Produce JSCallback compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 */
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		return sprintf('<script type="text/javascript">
//<![CDATA[
%s(%s);
//]]>
</script>', Context::getJSCallbackFunc(), json_encode($variables));
	}
}
/* End of file JSCallback.class.php */
/* Location: ./classes/display/JSCallback.class.php */
