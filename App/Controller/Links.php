<?php

namespace App\Controller;

use App\Controller\Base\Database;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Links extends BaseController {
 
    private $db;
    public $request;
    public $jwt;

    public function __construct() {
        $this->jwt = validate_jwt_token(get_bearer_token());
        // var_dump($check);   
        $this->db = new Database();
    }

    /**
     * Retrieve all user links
     */
    public function get(Request $request, Response $response) {
        try {
            $my_links = $this->db->select('user_links', ['user_id' => $this->getUserId()]);
            if($my_links) {
                $response->getBody()->write(get_success_response($my_links));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(get_error_response(['error' => $th->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    /**
     * Store new link
     * @param mixed $data
     */
    public function store(Request $request, Response $response) 
    {
        try {
            if ($request->getHeaderLine('Content-Type') === 'application/json') {
                $request = json_decode($request->getBody(), true);
            } else {
                $request = $request->getParsedBody();
            }
            $data = (array)$request;
            $data['user_id'] = $this->jwt->sub ?? null;
            $save = $this->db->insert('user_links', $data);
            if($save) {
                $response->getBody()->write(get_success_response($save));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(get_error_response(['error' => $th->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    /**
     * Retrieve single user links
     * @param int $id
     */
    public function show(Response $response, $id) {
        try {
            $save = $this->db->select('user_links', ['id' => $id, 'user_id' => $this->getUserId()]);;
            if($save) {
                $response->getBody()->write(get_success_response($save));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(get_error_response(['error' => $th->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    /**
     * Update link
     * @param array $data
     * @param int $id
     */
    public function update(Request $request, Response $response, $linkId)
    {
        try {
            $id = $linkId['linkId'];
            if ($request->getHeaderLine('Content-Type') === 'application/json') {
                $request = json_decode($request->getBody(), true);
            } else {
                $request = $request->getParsedBody();
            }
            $data = (array)$request;
            $userId = $this->getUserId();
            $data['user_id'] = $userId ?? null;
            $checkIfExist = $this->db->select('user_links', ['id' => $id, 'user_id' => $userId]);
            if(!$checkIfExist) {
                $response->getBody()->write(get_error_response(['error' => "Record with the provided ID deos not exists"]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            $save = $this->db->update('user_links', $data, ['id' => $id, 'user_id' => $userId]);
            if($save) {
                $response->getBody()->write(get_success_response($save));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(get_error_response(['error' => $th->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    /**
     * Delete link
     * @param int $id
     */
    public function destroy(Request $request, Response $response, $linkId)
    {
        try {
            $id = $linkId['linkId'];
            $save = $this->db->delete('user_links', ['id' => $id, 'user_id' => $this->getUserId()]);
            if($save) {
                $response->getBody()->write(get_success_response($save));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(get_error_response(['error' => $th->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}
