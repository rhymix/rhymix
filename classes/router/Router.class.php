<?php
/**
 * @file Router.class.php
 * @brief Parses URIs and determines routing
 * @author FunnyXE (admin@funnyxe.com)
 */
class Router
{
	/**
	 * URI Segments
	 * @var array
	 */
	private static $segments = array();

	/**
	 * Routes
	 * @var array
	 */
	private static $routes = array(
		// rss , blogAPI
		'(rss|atom)' => array('module' => 'rss', 'act' => '$1'),
		'([a-zA-Z0-9_]+)/(rss|atom|api)' => array('mid' => '$1', 'act' => '$2'),
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/(rss|atom|api)' => array('vid' => '$1', 'mid' => '$2', 'act' => '$3'),
		// trackback
		'([0-9]+)/(.+)/trackback' => array('document_srl' => '$1', 'key' => '$2', 'act' => 'trackback'),
		'([a-zA-Z0-9_]+)/([0-9]+)/(.+)/trackback' => array('mid' => '$1', 'document_srl' => '$2', 'key' => '$3', 'act' => 'trackback'),
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/([0-9]+)/(.+)/trackback' => array('vid' => '$1', 'mid' => '$2', 'document_srl' => '$3' , 'key' => '$4', 'act' => 'trackback'),
		// mid
		'([a-zA-Z0-9_]+)/?' => array('mid' => '$1'),
		// mid + document_srl
		'([a-zA-Z0-9_]+)/([0-9]+)' => array('mid' => '$1', 'document_srl' => '$2'),
		// vid + mid
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/' => array('vid' => '$1', 'mid' => '$2'),
		// vid + mid + document_srl
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/([0-9]+)?' => array('vid' => '$1', 'mid' => '$2', 'document_srl' => '$3'),
		// document_srl
		'([0-9]+)' => array('document_srl' => '$1'),
		// mid + entry title
		'([a-zA-Z0-9_]+)/entry/(.+)' => array('mid' => '$1', 'entry' => '$2'),
		// vid + mid + entry title
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/entry/(.+)' => array('vid' => '$1', 'mid' => '$2', 'entry' => '$3'),
		// shop / vid / [category|product] / identifier
		'([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/([a-zA-Z0-9_\.-]+)' => array('act' => 'route', 'vid' => '$1', 'type' => '$2', 'identifier'=> '$3')
	);

	/**
	 * Rewrite map
	 * @var array
	 */
	private static $rewrite_map = array();

	/**
	 * @brief Applys routes.
	 * @see This function should be called only once
	 * @return void
	 */
	public static function proc()
	{
		$uri = $_SERVER['REQUEST_URI'];

		if (stripos($uri, $_SERVER['SCRIPT_NAME']) === 0)
		{
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		}
		elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
		{
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}

		if ($uri == '/' || empty($uri))
		{
			return;
		}

		$path = parse_url($uri, PHP_URL_PATH);

		// Do some final cleaning of the URI and return it
		$path = str_replace(array('//', '../'), '/', trim($path, '/'));

		if(strlen($path) > 0)
		{
			self::$segments = explode('/', $path);

			// Remove the meanless segment
			unset(self::$segments[0]);
		}

		if(isset(self::$routes[$path]))
		{
			foreach(self::$routes[$path] as $key => $val)
			{
				$val = preg_replace('#^\$([0-9]+)$#e', '\$matches[$1]', $val);

				Context::set($key, $val, TRUE);
			}

			return;
		}

		// Apply routes
		foreach(self::$routes as $regex => $query)
		{
			if(preg_match('#^' . $regex . '$#', $path, $matches))
			{
				foreach($query as $key => $val)
				{
					$val = preg_replace('#^\$([0-9]+)$#e', '\$matches[$1]', $val);

					Context::set($key, $val, TRUE);
				}
			}
		}
	}

	/**
	 * @brief Add a rewrite map(s)
	 * @param array $map
	 * @return void
	 */
	public static function setMap($map)
	{
		self::$rewrite_map = array_merge(self::$rewrite_map, $map);
	}

	/**
	 * @brief Add a route
	 * @param string $target
	 * @param array $query
	 * @return void
	 */
	public static function add($target, $query)
	{
		self::$routes[$target] = $query;
	}

	/**
	 * @brief Add multiple routes
	 * @param array $routes
	 * @return void
	 */
	public function adds($routes)
	{
		self::$routes = array_merge(self::$routes, $routes);
	}

	/**
	 * @brief Get segment from request uri
	 * @param int $index
	 * @return string
	 */
	public static function getSegment($index)
	{
		return self::$segments[$index];
	}


	/**
	 * @brief Get segment from request uri
	 * @param int $index
	 * @return string
	 */
	public static function getSegments()
	{
		return self::$segments;
	}

	/**
	 * @brief Get route info
	 * @param string $regex
	 * @return array
	 */
	public static function getRoute($regex)
	{
		return self::$routes[$regex];
	}

	/**
	 * @brief Get routes list
	 * @return array
	 */
	public static function getRoutes()
	{
		return self::$routes;
	}

	/**
	 * @brief Get routes list
	 * @param string $regex
	 * @return boolean
	 */
	public static function isExistsRoute($regex)
	{
		return isset(self::$routes[$regex]);
	}

	/**
	 * @brief Makes shortten url
	 * @param string $regex
	 * @return string
	 */
	public static function makePrettyUrl($regex)
	{
		return self::$rewrite_map[$regex];
	}
}