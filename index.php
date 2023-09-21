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
 *  Copyright (c) Rhymix Developers and Contributors <devops@rhymix.org>
 *  Copyright (c) NAVER <http://www.navercorp.com>
 *
 *  This program is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License as published by the Free
 *  Software Foundation, either version 2 of the License, or (at your option)
 *  any later version.
 *
 *  This program is distributed in the hope that it will be useful, but WITHOUT
 *  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *  FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 *  more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * -----------------------------------------------------------------------------
 *
 *  Rhymix is a derivative work of XpressEngine (XE) version 1.x.
 *  The license has been changed from LGPL v2.1 to GPL v2 in accordance with
 *  section 3 of LGPL v2.1. This change is irreversible and applies to all of
 *  Rhymix, including parts that were copied verbatim from XpressEngine.
 *
 * -----------------------------------------------------------------------------
 */

/**
 * Uncomment the following line to show error messages.
 * This should only be used on crappy hosting with no logging facility.
 * You should comment it back as soon as the problem is fixed.
 */
//ini_set('display_errors', true);

/**
 * Include the autoloader.
 */
require __DIR__ . '/common/autoload.php';

/**
 * Set all request arguments and environment variables.
 */
Context::init();

/**
 * Initialize and execute the requested module.
 */
if (PHP_SAPI !== 'cli')
{
	$oModuleHandler = new ModuleHandler();
	$oModuleHandler->init() && $oModuleHandler->displayContent($oModuleHandler->procModule());
	Context::close();
}
else
{
	Rhymix\Framework\Debug::disable();
	ModuleHandler::procCommandLineArguments($argv);
}
