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
	private static $controller_path;
	private static $routes;
	private static $version = '2.0';
	private static $request;
	private static $route_info;
	private static $app;
	
	/**
	 * Builds the routes then processes the request to the controller and method.
	 * @param array $config The config array with the controller path and routes.
	 */
	public static function init(array $config)
	{
		static::$controller_path = $config['controller_path'];
		
		foreach ($config['routes'] as $route => $args) {
			if (!is_array($args)) {
				$args = array(
					$args,
					'params' => array()
				);
			}
			static::$routes[$route] = $args;
		}
		
		static::$request = static::get_request();
		static::route();
	}
	
	/**
	 * Loads and executes the controller.
	 */
	public static function run()
	{
		$controller_info = array(
			'file' => static::$controller_path . (isset(static::$route_info['namespace']) ? static::$route_info['namespace'] . '/' : '') . strtolower(static::$route_info['controller']) . '_controller.php',
			'class' => static::$route_info['controller'] . 'Controller',
			'method' => 'action_' . static::$route_info['method']
		);
		
		// Fetch the AppController
		if (file_exists(static::$controller_path . 'app_controller.php')) {
			require_once static::$controller_path . 'app_controller.php';
		}
		
		// Check if the controller file exists
		if (file_exists($controller_info['file'])) {
			require_once $controller_info['file'];
		} else {
			self::halt("Unable to load controller <code>" . static::$route_info['controller'] . "</code>");
		}
		
		// Check if the class and method exists
		if (class_exists($controller_info['class']) and method_exists($controller_info['class'], $controller_info['method'])) {
			static::$app = new $controller_info['class']();
			call_user_func_array(array(static::$app, $controller_info['method']), static::$route_info['args']);
		} else {
			self::halt("Unable to call method <code>" . $controller_info['class'] . '::' . $controller_info['method'] . "</code>");
		}
		
		unset($controller_info);
	}
	
	/**
	 * Returns the URL paramater.
	 * @param string/integer $param The param to fetch.
	 */
	public static function param($param)
	{
		return static::$route_info['params'][$param];
	}
	
	/**
	 * Private function to set the routed controller, method, parameters and method arguments.
	 * @param array $route The route array.
	 */
	private static function set_request($route)
	{
		static::$route_info = array();
		
		// Seperate the method arguments from the route
		$bits = explode('/', $route[0]);
		static::$route_info['params'] = $route['params'];
		static::$route_info['args'] = array_slice($bits, 1);
		
		// Check if there's a namespace specified
		$bits = explode('::', $bits[0]);
		if (count($bits) == 3) {
			static::$route_info['namespace'] = $bits[0];
			static::$route_info['controller'] = $bits[1];
			static::$route_info['method'] = $bits[2];
		} else {
			static::$route_info['controller'] = $bits[0];
			static::$route_info['method'] = $bits[1];
		}
	}
	
	/**
	 * Private function used to route the reuqest to the controller
	 */
	private static function route()
	{
		// Prefix a forward slash to the request.
		$request = '/' . static::$request;
		
		// Are we on the front page?
		if ($request == '/') {
			static::set_request(static::$routes['/']);
			return true;
		}
		
		// Check if we have an exact match
		if (isset(static::$routes[$request])) {
			static::set_request(static::$routes[$request]);
			return true;
		}
		
		// Loop through routes and find a regex match
		foreach (static::$routes as $route => $args) {
			$route = '#^' . $route . '$#';
			if (preg_match($route, $request, $params)) {
				unset($params[0]);
				$args['params'] = array_merge($args['params'], $params);
				$args[0] = preg_replace($route, $args[0], $request);
				static::set_request($args);
				return true;
			}
		}
		
		// No match, error controller, make it so.
		static::set_request(array('Error::404', 'params' => array()));
		return false;
	}
	
	/**
	 * Private function used to get the request URI.
	 */
	private static function get_request()
	{
		// Check if there is a PATH_INFO variable
		// Note: some servers seem to have trouble with getenv()
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		if (trim($path, '/') != '' && $path != "/index.php") {
			return $path;
		}
		
		// Check if ORIG_PATH_INFO exists
		$path = str_replace($_SERVER['SCRIPT_NAME'], '', (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO'));
		if (trim($path, '/') != '' && $path != "/index.php") {
			return $path;
		}
		
		// Check for ?uri=x/y/z
		if (isset($_REQUEST['url'])) {
			return $_REQUEST['url'];
		}
		
		// Check the _GET variable
		if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '') {
			return key($_GET);
		}
		
		// Check for QUERY_STRING
		$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if (trim($path, '/') != '') {
			return $path;
		}
		
		// Check for REQUEST_URI
		$path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
		if (trim($path, '/') != '' && $path != "/index.php") {
			return str_replace(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '', $path);
		}
		
		// I dont know what else to try, screw it..
		return '';
	}
	
	/**
	 * Private function used to display an error message.
	 */
	private static function halt($message)
	{
		echo "Ant halted with error:<br />";
		echo "<p>{$message}</p>";
		echo "<small>Ant " . self::version() . "</small>";
		exit;
	}
	
	/**
	 * Returns the version number.
	 */
	public static function version()
	{
		return self::$version;
	}
}