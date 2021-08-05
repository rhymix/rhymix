<?php

namespace Rhymix\Framework\Parsers;

/**
 * Module action (conf/module.xml) parser class for XE compatibility.
 */
class ModuleActionParser extends BaseParser
{
	/**
	 * Shortcuts for route definition.
	 */
	protected static $_shortcuts = array(
		'int' => '[0-9]+',
		'float' => '[0-9]+(?:\.[0-9]+)?',
		'alpha' => '[a-zA-Z]+',
		'alnum' => '[a-zA-Z0-9]+',
		'hex' => '[0-9a-f]+',
		'word' => '[a-zA-Z0-9_]+',
		'any' => '[^/]+',
		'delete' => '[^/]+',
	);
	
	/**
	 * Load an XML file.
	 * 
	 * @param string $filename
	 * @return object|false
	 */
	public static function loadXML(string $filename)
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return false;
		}
		
		// Get the current language.
		$lang = \Context::getLangType() ?: 'en';
		
		// Initialize the module definition.
		$info = new \stdClass;
		$info->admin_index_act = '';
		$info->default_index_act = '';
		$info->setup_index_act = '';
		$info->simple_setup_index_act = '';
		$info->route = new \stdClass;
		$info->route->GET = [];
		$info->route->POST = [];
		$info->action = new \stdClass;
		$info->grant = new \stdClass;
		$info->menu = new \stdClass;
		$info->error_handlers = [];
		
		// Parse grants.
		foreach ($xml->grants->grant ?: [] as $grant)
		{
			$grant_info = new \stdClass;
			$grant_info->title = self::_getChildrenByLang($grant, 'title', $lang);
			$grant_info->default = trim($grant['default']);
			$grant_name = trim($grant['name']);
			$info->grant->{$grant_name} = $grant_info;
		}
		
		// Parse menus.
		foreach ($xml->menus->menu ?: [] as $menu)
		{
			$menu_info = new \stdClass;
			$menu_info->title = self::_getChildrenByLang($menu, 'title', $lang);
			$menu_info->index = null;
			$menu_info->acts = array();
			$menu_info->type = trim($menu['type']);
			$menu_name = trim($menu['name']);
			$info->menu->{$menu_name} = $menu_info;
		}
		
		// Parse actions.
		foreach ($xml->actions->action ?: [] as $action)
		{
			// Parse permissions.
			$action_name = trim($action['name']);
			$action_type = trim($action['type'] ?? '');
			$action_class = trim($action['class'] ?? '');
			$permission = trim($action['permission'] ?? '');
			$permission_info = (object)['target' => '', 'check_var' => '', 'check_type' => ''];
			if ($permission)
			{
				$permission_info->target = $permission;
				$permission_info->check_var = trim($action['check_var']) ?: trim($action['check-var']);
				$permission_info->check_type = trim($action['check_type']) ?: trim($action['check-type']);
			}
			
			// Parse the list of allowed HTTP methods.
			$method_attr = trim($action['method']);
			if ($method_attr)
			{
				$methods = explode('|', strtoupper($method_attr));
			}
			elseif ($action_type === 'controller' || starts_with('proc', $action_name))
			{
				$methods = ['POST'];
			}
			elseif ($action_class && starts_with('disp', $action_name))
			{
				$methods = ['GET'];
			}
			else
			{
				$methods = ['GET', 'POST'];
			}
			
			// Parse routes.
			$global_route = (trim($action['global_route']) ?: trim($action['global-route'])) === 'true' ? 'true' : 'false';
			$route_attr = trim($action['route']);
			$route_tags = $action->route ?: [];
			$route_arg = [];
			if ($route_attr || count($route_tags))
			{
				$routes = $route_attr ? array_map(function($route) {
					return ['route' => trim($route), 'priority' => 0];
				}, explode_with_escape('|', $route_attr)) : array();
				foreach ($route_tags as $route_tag)
				{
					$routes[] = ['route' => trim($route_tag['route']), 'priority' => intval($route_tag['priority'] ?: 0)];
				}
				foreach ($routes as $route)
				{
					$route_info = self::analyzeRoute($route);
					$route_arg[$route_info->route] = ['priority' => intval($route_info->priority), 'vars' => $route_info->vars];
					foreach ($methods as $method)
					{
						$info->route->{$method}[$route_info->regexp] = $action_name;
					}
				}
			}
			
			// Parse the standalone attribute.
			if ($global_route === 'true')
			{
				$standalone = 'true';
			}
			elseif ($action_class)
			{
				$standalone = trim($action['standalone']);
				if (!$standalone || !in_array($standalone, ['true', 'false', 'auto']))
				{
					$standalone = 'auto';
				}
			}
			else
			{
				$standalone = trim($action['standalone']);
				if (!$standalone || !in_array($standalone, ['true', 'false', 'auto']))
				{
					$standalone = 'true';
				}
			}

			// Automatically determine the type for custom classes.
			if ($action_class && !$action_type)
			{
				if (starts_with('disp', $action_name))
				{
					$action_type = 'view';
				}
				elseif (starts_with('proc', $action_name))
				{
					$action_type = 'controller';
				}
				else
				{
					$action_type = 'auto';
				}
			}
			
			// Parse other information about this action.
			$action_info = new \stdClass;
			$action_info->type = $action_type;
			$action_info->class_name = preg_replace('/\\\\+/', '\\\\', $action_class);
			$action_info->grant = trim($action['grant']) ?: 'guest';
			$action_info->permission = $permission_info;
			$action_info->ruleset = trim($action['ruleset']);
			$action_info->method = implode('|', $methods);
			$action_info->route = $route_arg;
			$action_info->standalone = $standalone;
			$action_info->check_csrf = (trim($action['check_csrf']) ?: trim($action['check-csrf'])) === 'false' ? 'false' : 'true';
			$action_info->meta_noindex = (trim($action['meta_noindex']) ?: trim($action['meta-noindex'])) === 'true' ? 'true' : 'false';
			$action_info->global_route = $global_route;
			$info->action->{$action_name} = $action_info;
			
			// Set the menu name and index settings.
			$menu_name = trim($action['menu_name']);
			if ($menu_name && isset($info->menu->{$menu_name}))
			{
				$info->menu->{$menu_name}->acts[] = $action_name;
				if (toBool($action['menu_index']))
				{
					$info->menu->{$menu_name}->index = $action_name;
				}
			}
			if (toBool($action['index']))
			{
				$info->default_index_act = $action_name;
			}
			if (toBool($action['admin_index']))
			{
				$info->admin_index_act = $action_name;
			}
			if (toBool($action['setup_index']))
			{
				$info->setup_index_act = $action_name;
			}
			if (toBool($action['simple_setup_index']))
			{
				$info->simple_setup_index_act = $action_name;
			}
			
			// Set error handler settings.
			$error_handlers = explode(',', trim($action['error_handlers']) ?: trim($action['error-handlers']));
			foreach ($error_handlers as $error_handler)
			{
				if (intval($error_handler) > 200)
				{
					$info->error_handlers[intval($error_handler)] = $action_name;
				}
			}
		}
		
		// Parse permissions not defined in the <actions> section.
		foreach ($xml->permissions->permission ?: [] as $permission)
		{
			$action_name = trim($permission['action']);
			if (isset($info->action->{$action_name}))
			{
				$info->action->{$action_name}->permission->target = trim($permission['target']);
				$info->action->{$action_name}->permission->check_var = trim($permission['check_var']) ?: trim($permission['check-var']);
				$info->action->{$action_name}->permission->check_type = trim($permission['check_type']) ?: trim($permission['check-type']);
			}
		}
		
		// Return the complete result.
		return $info;
	}
	
	/**
	 * Convert route definition into a regular expression.
	 * 
	 * @param array $route
	 * @return object
	 */
	public static function analyzeRoute(array $route)
	{
		// Replace variables in the route definition into appropriate regexp.
		$var_regexp = '#\\$([a-zA-Z0-9_]+)(?::(' . implode('|', array_keys(self::$_shortcuts)) . '))?#';
		$vars = array();
		$regexp = preg_replace_callback($var_regexp, function($match) use(&$vars) {
			if (isset($match[2]))
			{
				$var_type = $match[2];
				$var_pattern = self::$_shortcuts[$match[2]];
			}
			else
			{
				$var_type = ends_with('_srl', $match[1]) ? 'int' : 'any';
				$var_pattern = self::$_shortcuts[$var_type];
			}
			$named_group = '(?P<' . $match[1] . '>' . $var_pattern . ')';
			$vars[$match[1]] = $var_type;
			return $named_group;
		}, $route['route']);
		
		// Anchor the regexp at both ends.
		$regexp = '#^' . strtr($regexp, ['#' => '\\#']) . '$#u';
		
		// Return the regexp and variable list.
		$result = new \stdClass;
		$result->route = preg_replace_callback($var_regexp, function($match) {
			return '$' . ((isset($match[2]) && $match[2] === 'delete') ? ($match[1] . ':' . $match[2]) : $match[1]);
		}, $route['route']);
		$result->priority = $route['priority'] ?: 0;
		$result->regexp = $regexp;
		$result->vars = $vars;
		return $result;
	}
}
