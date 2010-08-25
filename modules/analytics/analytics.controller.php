<?php
	class analyticsController extends analytics {
		function init(){
		}

		function triggerBeforeDisplay(&$obj){
			$responseMethod = Context::getResponseMethod();
			
			if ($responseMethod != 'HTML')
				return;

			$script =  '<script type="text/javascript" src="http://static.analytics.openapi.naver.com/js/wcslog.js"></script><script type="text/javascript">if(!wcs_add) var wcs_add = {};wcs_add["wa"] = "AccoutId";wcs_do();</script>';
            Context::addHtmlFooter( $script );

		}
	}
?>
