<?php
/**
 * Ant
 * Copyright (C) 2011 Jack Polgar
 *
 * Ant is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 * 
 * Ant is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Ant. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Ant
 * @copyright Copyright (C) 2011 Jack Polgar
 */
 
class Ant
{
	private static $app_path;
	private static $routes;
	private static $app;
	private static $version = '1.0';
	
	public static function init( array $config )
	{
		// Set the config
		self::$app_path = $config['app_path'];
		self::$routes = $config['routes'];
		
		// Route the request and run
		self::run(self::route(self::request()));
	}
	
	private static function run(array $route )
	{
		// Check if the controller exists
		if (!file_exists(self::$app_path.'controllers/' . ($route['namespace'] ? $route['namespace'].'/' : '') . strtolower($route['controller']) . '_controller.php'))
			self::halt("Unable to load controller: ". ($route['namespace'] ? $route['namespace'].'/' : '') . $route['controller']);
		
		// Fetch the controller file
		require_once self::$app_path.'controllers/' . ($route['namespace'] ? $route['namespace'].'/' : '') . strtolower($route['controller']) . '_controller.php';
		
		// Check if the class exists
		if(!class_exists($route['controller'] . 'Controller'))
			self::halt('Unable to initialize controller '. ($route['namespace'] ? $route['namespace'].'/' : '') . $route['controller']);
			
		// Check if the method exists
		if(!method_exists($route['controller'] . 'Controller', $route['method']))
			self::halt('Unable to call controller method');
		
		// Start the app
		$controller = $route['controller'] . 'Controller';
		self::$app = new $controller;
		self::$app->$route['method']();
	}
	
	public static function version()
	{
		return self::$version;
	}
	
	private static function halt( $message )
	{
		echo "Ant Halted during initialization";
		echo "<p>{$message}</p>";
		echo "Ant ".self::version();
		exit;
	}
	
	private static function route( $request )
	{
		// Only one route? No need to continue...
		if(count(self::$routes) == 1) return self::set_controller($request);
		
		// Check for RegEx matches
		foreach(self::$routes as $key => $val)
		{
			// Convert wild-cards to regular expression
			$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

			// Is there a RegEx match?
			if(preg_match('#^'.$key.'$#', $request))
			{
				// Do we have a back-reference?
				if(strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE)
				{
					$val = preg_replace('#^'.$key.'$#', $val, $request);
				}

				return self::set_controller($val);
			}
		}
		
		// No matches? eh
		return self::set_controller($request);
	}
	
	private static function set_controller( $request )
	{	
		$request = explode('/', $request);
		
		// Check if the request exists
		if($request[0] == '') $request = explode('/', self::$routes['/']);
		
		$route_info = array(
			'namespace' => null,
			'controller' => null,
			'method' => null
		);
		
		// Check for a namespace
		if(count($request) == 3)
		{
			$route_info['namespace'] = @$request[0];
			$route_info['controller'] = @$request[1];
			$route_info['method'] = @$request[2];
		}
		// No namespace
		else
		{
			$route_info['controller'] = @$request[0];
			$route_info['method'] = @$request[1];
		}
		
		return $route_info;
	}
	
	public static function request()
	{
		// Check if there is a PATH_INFO variable
		// Note: some servers seem to have trouble with getenv()
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		if(trim($path, '/') != '' && $path != "/index.php") return trim($path, '/');

		// Check if ORIG_PATH_INFO exists
		$path = str_replace($_SERVER['SCRIPT_NAME'], '', (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO'));
		if(trim($path, '/') != '' && $path != "/index.php") return trim($path, '/');

		// Check for ?uri=x/y/z
		if(isset($_REQUEST['url']) ) return $_REQUEST['url'];

		// Check the _GET variable
		if(is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '') return key($_GET);

		// Check for QUERY_STRING
		$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if(trim($path, '/') != '') return trim($path, '/');

		// I dont know what else to try, screw it..
		return '';
	}
}