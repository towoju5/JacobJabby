<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;

class JwtAuthMiddleware
{
    protected $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function __invoke($request, $handler): Response
    {
        // $routeContext = RouteContext::fromRequest($request);
        // $route = $routeContext->getRoute();
        // if (empty ($route)) {
        //     throw new HttpNotFoundException($request, "AUTHENTICATION FAILED");
        // }

        $token = $request->getHeaderLine('Authorization');

        if ($token) {
            try {
                $decoded = JWT::decode($token, $this->secretKey);
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
