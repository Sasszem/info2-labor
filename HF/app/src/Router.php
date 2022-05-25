<?php
/**
 * Advanced router with regex matching and various action types
 * Only used in index.php
 */

namespace Routing;

use Closure;

require_once 'lib.php';
require_once 'db.php';

enum RouteMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
}

/**
 * abstract base class for all actions
 */
abstract class RouteAction
{
    /**
     * run the action of the route
     * @param string $route the requested URI
     * @return ?string result as text. Might return null, or exit() (possibly after redirect)
     */
    abstract public function run(string $route): ?string;
}

/**
 * Send static file as-is
 */
class RouteActionStaticFile extends RouteAction
{
    protected string $filename = '';

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function run(string $route): ?string
    {
        /**
         * Use nginx's headers to send file
         */
        $ct = mime_content_type(($_ENV['APP_ROOT'] ?? '/app/') . $this->filename);
        header("X-Accel-Redirect: $this->filename");
        header("Content-type: $ct");
        //header('Content-Disposition: attachment; filename="' . basename($this->filename) . '"');
        return null;
    }
}

/**
 * Render static file in main view (include navbars & stuff)
 */
class RouteActionRender extends RouteAction
{
    protected string $filename = '';

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function run(string $route): ?string
    {
        $filepath = ($_ENV['APP_ROOT'] ?? '/app/') . $this->filename;
        if (!file_exists($filepath)) {
            return "Error: file $filepath not found!";
        }
        return file_get_contents($filepath);
    }
}

/**
 * Call function to get result
 */
class RouteActionCallback extends RouteAction
{
    protected Closure $function;

    public function __construct(Closure $function)
    {
        $this->function = $function;
    }

    public function run(string $route): ?string
    {
        $fun = $this->function;
        return $fun($route);
    }
}

/**
 * Run controller action
 *
 * Create db connection, make model instances, dependency inject them into the controller and call action
 */
class RouteActionController extends RouteAction
{
    protected string $class = '';
    protected string $method = '';

    /**
     * @param string $class class of the controller, for example \User\UserController::class
     * @param string $method static method to call on the controller. It's model parameters will get dependency injected
     */
    public function __construct(string $class, string $method)
    {
        // only allow static methods
        assert((new \ReflectionMethod($class, $method))->isStatic(), 'Controller method must be static!');
        $this->class = $class;
        $this->method = $method;
    }

    public function run(string $route): ?string
    {
        // use reflection to find out the types of the parameters
        $reflection = new \ReflectionMethod($this->class, $this->method);

        $db = \db\getConnection();

        // assemble args by creating each argumet from the db
        $args = array_map(function ($m) use ($db) {
            $typename = $m->getType()->getName();
            return new $typename($db);
        }, $reflection->getParameters());

        $class = $this->class;
        $method = $this->method;

        $controller = new $class();

        return $controller->{$method}(...$args);
    }
}

/**
 * Single route matching on some pattern & rules and executing an action
 */
class Route
{
    protected RouteMethod $method = RouteMethod::GET;
    protected ?string $route = '';
    /**
     * These two can protect a route as a middleware
     */
    protected bool $requireLogin = false;
    protected bool $requireDev = false;

    protected ?RouteAction $action = null;

    public function get_route()
    {
        return $this->route;
    }

    public function __construct(RouteMethod $method, string $route)
    {
        $this->method = $method;
        // have to magic the regex a bit because PGP uses some crazy notation
        $this->route = '/' . str_replace('/', '\/', $route) . '/i';
    }

    /**
     * check if a route is matched by us
     */
    public function match(string $route, string $method)
    {
        return (
            $this->isValid() &&
            ($method === $this->method->value) &&
            (preg_match($this->route, $route)===1)
        );
    }

    /**
     * Run the action with the route and return result
     */
    public function run(string $route): ?string
    {
        /**
         * Check guards first
         */
        if ($this->requireLogin && !\User\LoggedInUser::isLoggedIn()) {
            redirect('/user/login');
        }
        if ($this->requireDev && !\User\LoggedInUser::isDev()) {
            redirect('/');
        }

        // everything is ok, can go on
        // null chaining ensures that we don't error out
        return ($this?->action->run($route)) ?? '';
    }

    /**
     * setup functions for the different types of actions
     */


    public function static(string $file)
    {
        $this->action = new RouteActionStaticFile($file);
        return $this;
    }

    public function action(Closure $callback)
    {
        $this->action = new RouteActionCallback($callback);
        return $this;
    }

    public function render(string $file)
    {
        $this->action = new RouteActionRender($file);
        return $this;
    }

    public function controller(string $class, string $method)
    {
        $this->action = new RouteActionController($class, $method);
        return $this;
    }


    /**
     * Set guards
     */
    public function requireLogin(): Route
    {
        $this->requireLogin = true;
        return $this;
    }

    public function requireDev(): Route
    {
        $this->requireDev = true;
        return $this;
    }

    // helper
    public function isValid()
    {
        return !(is_null($this->action));
    }
}

/**
 * Router class collects (and creates) routes and dispatches actions to them
 */
class Router
{
    protected array $routes = array();

    /**
     * Default route is called when no route matches (404)
     */
    protected Route $default;

    public function __construct()
    {
        $this->default = new Route(RouteMethod::GET, 'DEFAULT');
    }

    /**
     * Helper: add a route with path
     */
    protected function _add_route(string $path, RouteMethod $method)
    {
        $route = new Route($method, $path);
        array_push($this->routes, $route);
        return $route;
    }

    /**
     * convinient way of creating routes with GET or POST methods
     */
    public function get(string $path)
    {
        return $this->_add_route($path, RouteMethod::GET);
    }

    public function post(string $path)
    {
        return $this->_add_route($path, RouteMethod::POST);
    }


    /**
     * Get default route for modification
     */
    public function default()
    {
        return $this->default;
    }

    /**
     * Run routing and pass the result to $callback
     */
    public function run(Closure $callback)
    {
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $method = $_SERVER['REQUEST_METHOD'];
        foreach ($this->routes as $route) {
            if ($route->match($uri, $method)) {
                $result = $route->run($uri, $callback);
                $callback($result);
                return;
            }
        }
        $result = $this->default()->run($uri);
        $callback($result);
    }
}
