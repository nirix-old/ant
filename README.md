Ant
======

Ant is a simple little or "micro" framework that basically routes requests to a controller and supports a namespace (Namespace/Controller/method for example).

Setup
------------

Create a directory called "app", inside that create another directory called "controllers", now inside
the controllers directory create a file called "welcome_controller.php" and put the following code into it.

	class WelcomeController
	{
		public function index()
		{
			echo "Welcome!";
		}
	}

Now outside of app directory create a file called "index.php" and put the following code into it.

	require_once "path/to/ant.php";
	Ant::init(array(
		'app_path' => dirname(__FILE__).'/app/',
		'routes' => array(
			'/' => 'Welcome/index'
		)
	));

Now open the path where index.php is in your browser, that is of course on a server, and you should see "Welcome!".

Routing
------------

The routing system in Ant is pretty cool as it allows you to use Regular Expression.

For example:

	$routes = array(
		'/admincp/(:any)' => "AdminCP/$1"
	);

If you wanted to use a Namespace, it would be:

	$routes = array(
		"AdminCP/(:any)/(:any)' => "AdminCP/$1/$2"
	);

Why
------------

I created Ant simply because of when I made simple little websites that dont require some big huge framework
but still want to route requests to controllers to keep page code tidy and organized.