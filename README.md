Ant
======

Ant is a simple little or "micro" framework that basically routes requests to a controller and supports a namespace (Namespace/Controller/method for example).

Setup
------------

Create a directory where you want to keep your controllers, create a file called `welcome_controller.php`
and paste the code below into it and save the file.

	class WelcomeController
	{
		public function action_index()
		{
			echo "Welcome!";
		}
	}

Now at the root of your site create a called `index.php` and paste the code below into it and save the file.

	require_once "ant.php";
	Ant::init(array(
		'controller_path' => dirname(__FILE__).'/controllers/',
		'routes' => array(
			'/' => 'Welcome::index',
		)
	));
	Ant::run();

Now open the path where index.php is in your browser, that is of course on a server, and you should see "Welcome!".

Routing
------------

The routing system in Ant is pretty cool as it allows you to use Regular Expression.

For example:

	$routes = array(
		'/admincp/(.*)' => 'AdminCP/$1',
		'/news/(?P<news_id>[0-9]+)' => 'News::show/$1'
	);

If you wanted to use a Namespace, it would be:

	$routes = array(
		'/admincp/(.*)/(.*)' => 'Admin/$1/$2',
		'/forum' => 'Forum::Home::index'
	);

Why
------------

I created Ant simply because of when I needed to make small websites but wanted the power of a frameworks routing
and nothing more.