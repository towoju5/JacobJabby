<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

class BaseController
{
    public $request;
    public $jwt;

    public function __construct(Request $request, Response $response)
    {
        $this->jwt = validate_jwt_token(get_bearer_token());
        $this->request = $request;
    }

    /**
     * Get currently logged in user ID
     */
    public function getUserId()
    {
        return $this->jwt->sub ?? null;
    }

    public function request() {
        return $this->request;
    }
}