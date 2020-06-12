<?php

namespace Rhymix\Framework\Parsers;

/**
 * Module action (conf/module.xml) parser class for XE compatibility.
 */
class ModuleActionParser
{
    /**
     * Shortcuts for route definition.
     */
    protected static $_shortcuts = array(
        'int' => '[1-9][0-9]*',
        'float' => '[0-9]+(?:\.[0-9]+)?',
        'number' => '[0-9]+',
        'alpha' => '[a-zA-Z]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'hex' => '[0-9a-f]+',
        'word' => '[a-zA-Z0-9_]+',
    );
    
	/**
	 * Load an XML file.
	 * 
	 * @param string $filename
	 * @return object|null
	 */
	public static function loadXML(string $filename): ?object
	{
        // Get the current language.
        $lang = \Context::getLangType();
        
        // Load the XML file.
		$xml = simplexml_load_file($filename);
		if ($xml === false)
		{
			return null;
		}
        
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
        $info->menu = new \stdClass;
        $info->grant = new \stdClass;
        $info->permission = new \stdClass;
        $info->permission_check = new \stdClass;
        
        // Parse grants.
        foreach ($xml->grants->grant as $grant)
        {
            $grant_info = new \stdClass;
            $grant_info->title = self::_getElementsByLang($grant, 'title', $lang);
            $grant_info->default = trim($grant['default']);
            $grant_name = trim($grant['name']);
            $info->grant->{$grant_name} = $grant_info;
        }
        
        // Parse permissions not defined in the <actions> section.
        foreach ($xml->permissions->permission as $permission)
        {
            $action_name = trim($permission['action']);
            $permission = trim($permission['target']);
            $info->permission->{$action_name} = $permission;
            
            $check = new \stdClass;
            $check->key = trim($permission['check_var']) ?: trim($permission['check-var']);
            $check->type = trim($permission['check_type']) ?: trim($permission['check-type']);
            $info->permission_check->{$action_name} = $check;
        }
        
        // Parse menus.
        foreach ($xml->menus->menu as $menu)
        {
            $menu_info = new \stdClass;
            $menu_info->title = self::_getElementsByLang($menu, 'title', $lang);
            $menu_info->index = null;
            $menu_info->acts = array();
            $menu_info->type = trim($menu['type']);
            $menu_name = trim($menu['name']);
            $info->menu->{$menu_name} = $menu_info;
        }
        
        // Parse actions.
        foreach ($xml->actions->action as $action)
        {
            // Parse permissions.
            $action_name = trim($action['name']);
            $permission = trim($action['permission']);
            if ($permission)
            {
                $info->permission->{$action_name} = $permission;
                if (isset($info->permission_check->{$action_name}))
                {
                    $info->permission_check->{$action_name} = new \stdClass;
                }
                $info->permission_check->{$action_name}->key = trim($action['check_var']) ?: trim($action['check-var']);
                $info->permission_check->{$action_name}->type = trim($action['check_type']) ?: trim($action['check-type']);
            }
            
            // Parse routes.
            $route = trim($action['route']);
            $method = trim($action['method']);
            $route_vars = [];
            if ($route)
            {
                $route_info = self::analyzeRoute($route);
                $route_vars = $route_info->vars;
                if ($method)
                {
                    $methods = explode('|', strtoupper($method));
                }
                else
                {
                    $methods = starts_with('proc', $action_name) ? ['POST'] : ['GET'];
                }
                foreach ($methods as $method)
                {
                    $info->route->{$method}[$route_info->regexp] = $action_name;
                }
            }
            
            // Parse other information about this action.
            $action_info = new \stdClass;
            $action_info->type = trim($action['type']);
            $action_info->grant = trim($action['grant']) ?: 'guest';
            $action_info->ruleset = trim($action['ruleset']);
            $action_info->method = $method;
            $action_info->route = $route;
            $action_info->route_vars = $route_vars;
            $action_info->standalone = trim($action['standalone']) === 'false' ? 'false' : 'true';
            $action_info->check_csrf = (trim($action['check_csrf']) ?: trim($action['check-csrf'])) === 'false' ? 'false' : 'true';
            $action_info->meta_noindex = (trim($action['meta_noindex']) ?: trim($action['meta-noindex'])) === 'true' ? 'true' : 'false';
            $info->action->{$action_name} = $action_info;
            
            // Set the menu name and index settings.
            $menu_name = trim($action['menu_name']);
            if ($menu_name)
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
        }
        
		// Return the complete result.
		return $info;
    }
    
    /**
     * Convert route definition into a regular expression.
     * 
     * @param string $route
     * @return object
     */
    public static function analyzeRoute(string $route): object
    {
        // Replace variables in the route definition into appropriate regexp.
        $var_regexp = '#\\$([a-z0-9_]+)(?::(' . implode('|', array_keys(self::$_shortcuts)) . '))?#i';
        $vars = array();
        $regexp = preg_replace_callback($var_regexp, function($match) use(&$vars) {
            if (isset($match[2]) && isset(self::$_shortcuts[$match[2]]))
            {
                $var_pattern = self::$_shortcuts[$match[2]];
            }
            else
            {
                $var_pattern = ends_with('_srl', $match[1]) ? '[0-9]+' : '[^/]+';
            }
            $named_group = '(?P<' . $match[1] . '>' . $var_pattern . ')';
            $vars[] = $match[1];
            return $named_group;
        }, $route);
        
        // Anchor the regexp at both ends.
        $regexp = '#^' . strtr($regexp, ['#' => '\\#']) . '$#u';
        
        // Return the regexp and variable list.
        $result = new \stdClass;
        $result->regexp = $regexp;
        $result->vars = $vars;
        return $result;
    }
    
    /**
     * Get child elements that match a language.
     * 
     * @param SimpleXMLElement $parent
     * @param string $tag_name
     * @param string $lang
     * @return string|null
     */
    protected static function _getElementsByLang(\SimpleXMLElement $parent, string $tag_name, string $lang): ?string
    {
        // If there is a child element that matches the language, return it.
        foreach ($parent->{$tag_name} as $child)
        {
            $attribs = $child->attributes('xml', true);
            if (strval($attribs['lang']) === $lang)
            {
                return trim($child);
            }
        }
        
        // Otherwise, return the first child element.
        foreach ($parent->{$tag_name} as $child)
        {
            return trim($child);
        }
        
        // If there are no child elements, return null.
        return null;
    }
}
