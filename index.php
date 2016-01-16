<?php
/**
 * -----------------------------------------------------------------------------
 * 
 *        R H Y M I X  :  C O N T E N T  M A N A G E M E N T  S Y S T E M
 * 
 *                            https://www.rhymix.org
 * 
 * -----------------------------------------------------------------------------
 * 
 *  Copyright (c) RhymiX Developers and Contributors <devops@rhymix.org>
 * 
 *  RhyMix is a derivative work (fork) of XpressEngine (XE) version 1.x.
 * 
 *  Copyright (c) NAVER <http://www.navercorp.com>
 * 
 *  This program is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU Lesser General Public License as published
 *  by the Free Software Foundation, either version 2.1 of the License, or
 *  (at your option) any later version.
 * 
 *  This program is distributed in the hope that it will be useful, but WITHOUT
 *  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *  FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *  for more details.
 * 
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program. If not, write to the Free Software Foundation,
 *  Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 * 
 * -----------------------------------------------------------------------------
 */

/**
 * Include the autoloader.
 */
require dirname(__FILE__) . '/common/autoload.php';

/**
 * @brief Initialize by creating Context object
 * Set all Request Argument/Environment variables
 **/
$oContext = Context::getInstance();
$oContext->init();

/**
 * @brief If default_url is set and it is different from the current url, attempt to redirect for SSO authentication and then process the module
 **/
if($oContext->checkSSO())
{
	$oModuleHandler = new ModuleHandler();

	try
	{
		if($oModuleHandler->init())
		{
			$oModuleHandler->displayContent($oModuleHandler->procModule());
		}
	}
	catch(Exception $e)
	{
		htmlHeader();
		echo Context::getLang($e->getMessage());
		htmlFooter();
	}
}

$oContext->close();

/* End of file index.php */
/* Location: ./index.php */
