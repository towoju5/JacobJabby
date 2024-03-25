<?php

namespace App\Controller;

use App\Controller\Base\Auth;

use App\Controller\Base\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class AuthController
{

    public function __construct()
    {
        //
    }

    public function login(Request $data, Response $response, array $args)
    {
        try {
            $request = json_decode($data->getBody()->getContents());
    
            if ($request === null && json_last_error() !== JSON_ERROR_NONE) {
                $response_data = $response->getBody()->write(get_error_response(["error" => 'Invalid JSON data in request body']));
                $response->getBody()->write(json_encode($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
    
            if(!isset($request->username) OR empty($request->username)) {
                $response_data = ["error" => 'Username is required'];
                $response->getBody()->write(get_error_response($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if(!isset($request->password) OR empty($request->password)) {
                $response_data = ["error" => 'Password is required'];
                $response->getBody()->write(get_error_response($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
    
            $auth = new Auth();
            $user = (object)$auth->login($request->username, $request->password);
    
            if ($user) {
                $user_id = $user->id;
                $jwt_token = generate_jwt_token($user_id);
                $response_data = ['token' => $jwt_token];
                $response->getBody()->write(get_success_response($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response_data = ['error' => 'Invalid credentials'];
                $response->getBody()->write(get_error_response($response_data));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
        } catch (Throwable $th) {
            $response_data = ['error' => $th->getMessage()];
            $response->getBody()->write(get_error_response($response_data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }


    public function register(Request $request, Response $response)
    {
        try {
            $auth = new Auth();
            $database = new Database();
            $profileImage = null;
            if ($request->getHeaderLine('Content-Type') === 'application/json') {
                $requestData = json_decode($request->getBody(), true);
            } else {
                $requestData = $request->getParsedBody();
            }
            
            if($database->select('users', ['email' => $requestData['email']])) {
                $response_data = ["error" => 'User with email already exists'];
                $response->getBody()->write(json_encode($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            if($database->select('users', ['username' => $requestData['username']])) {
                $response_data = ["error" => 'User with username already exists'];
                $response->getBody()->write(json_encode($response_data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (isset ($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $profileImage = save_image($_FILES['profile_image']);
            }

            $additionalData = [
                "linkedin" => $requestData['linkedin'] ?? null,
                "github" => $requestData['github'] ?? null,
                "twitter" => $requestData['twitter'] ?? null,
                "portfolio" => $requestData['portfolio'] ?? null,
                "profile_image" => $profileImage ?? null,
                "description" => $requestData['profile_description'] ?? null
            ];

            $check = $auth->register($requestData['username'], $requestData['password'], $requestData['email'], $additionalData);
            if ($check) {
                $user = (object)$auth->login($requestData['username'], $requestData['password']);

                if ($user) {
                    $user_id = $user->id;
                    $jwt_token = generate_jwt_token($user_id);
                    $response_data = ['token' => $jwt_token];
                    $response->getBody()->write(get_success_response($response_data));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
                } else {
                    $response_data = ['error' => 'Invalid credentials'];
                    $response->getBody()->write(get_error_response($response_data));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
                }
            }
        } catch (Throwable $th) {
            $response_data = ['error' => $th->getMessage(), 'trace' => $th->getTrace()];
            $response->getBody()->write(get_error_response($response_data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }



    public function logout()
    {
        //
    }
}