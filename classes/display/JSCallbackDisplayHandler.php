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
		$json = str_replace(array("\r\n", "\n", "\t"), array('\n', '\n', '\t'), json_encode2($variables));
		return sprintf('<script type="text/javascript">
//<![CDATA[
%s(%s);
//]]>
</script>', Context::getJSCallbackFunc(), $json);
	}

}
/* End of file JSCallback.class.php */
/* Location: ./classes/display/JSCallback.class.php */
