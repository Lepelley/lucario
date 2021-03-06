<?php

namespace Lucario\Core;

use FastRoute;
use FastRoute\Dispatcher;
use Lucario\Controller\HttpErrorController;

class Router
{
    /**
     * Contains routes of your application
     *
     * @var string|mixed
     */
    private $routes;

    /**
     * Router constructor.
     *
     * @param string|mixed $routes
     */
    public function __construct($routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param string $uri
     * @param string $method
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function dispatch(string $uri, string $method)
    {
        $dispatcher = FastRoute\simpleDispatcher($this->routes);

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $routeInfo = $dispatcher->dispatch($method, rawurldecode($uri));
        $method = [];
        $params = [];
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $method = [new HttpErrorController(), 'notFound'];
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $method = [new HttpErrorController(), 'methodNotAllowed'];
                break;

            case Dispatcher::FOUND:
                // Je vérifie si mon parametre est une chaine de caractere
                $method = [];
                $params = $routeInfo[2];

                if (is_string($routeInfo[1])) {
                    // si dans la chaine reçu on trouve les ::
                    if (strpos($routeInfo[1], '::') !== false) {
                        //on coupe sur l'operateur de resolution de portée (::)
                        // qui est symbolique ici dans notre chaine de caractere.
                        $route = explode('::', $routeInfo[1]);
                        $method = [new $route[0], $route[1]];
                    } else {
                        // sinon c'est directement la chaine qui nous interesse
                        $method = $routeInfo[1];
                    }
                } elseif(is_callable($routeInfo[1])) {
                    // dans le cas ou c'est appelable (closure (fonction anonyme) par exemple)
                    $method = $routeInfo[1];
                }
        }

        if (is_callable($method)) {
            return call_user_func_array($method, $params);
        }

        throw new \Exception(sprintf('Not callable'));
    }
}
