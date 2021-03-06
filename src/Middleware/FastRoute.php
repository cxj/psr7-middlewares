<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher;

class FastRoute
{
    use Utils\RouterTrait;
    use Utils\ArgumentsTrait;
    use Utils\ContainerTrait;

    /**
     * @var Dispatcher|null FastRoute dispatcher
     */
    protected $router;

    /**
     * Constructor. Set Dispatcher instance.
     *
     * @param Dispatcher|null $router
     */
    public function __construct(Dispatcher $router = null)
    {
        if ($router !== null) {
            $this->router($router);
        }
    }

    /**
     * Extra arguments passed to the controller.
     *
     * @param Dispatcher $router
     *
     * @return self
     */
    public function router(Dispatcher $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $router = $this->router ?: $this->getFromContainer(Dispatcher::CLASS);
        $route = $router->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($route[0] === Dispatcher::NOT_FOUND) {
            return $response->withStatus(404);
        }

        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $response->withStatus(405);
        }

        foreach ($route[2] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = self::executeTarget($route[1], $this->arguments, $request, $response);

        return $next($request, $response);
    }
}
