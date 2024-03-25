<?php
use Firebase\JWT\JWT;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;

class authorization
{
    protected $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function __invoke($request, $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        if (empty ($route)) {
            throw new HttpNotFoundException($request, "AUTHENTICATION FAILED");
        }
        $routeName = $route->getName();
        $publicRoutesArray = array('f401');

        $token = $request->getHeaderLine('Authorization');

        if ($token) {
            try {
                $decoded = JWT::decode($token, $this->secretKey, array('HS256'));
                $request = $request->withAttribute('user', $decoded);
            } catch (\Exception $e) {
                $response_data = ['error' => $e->getMessage()];
                $response->getBody()->write(get_error_response($response_data));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
        } else {
            $response_data = ['error' => 'Token not provided'];
            $response->getBody()->write(get_error_response($response_data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        $response = $handler->handle($request);
        // $response = $next($request, $response);
        return $response;
    }
}