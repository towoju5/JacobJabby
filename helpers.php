<?php
use App\Controller\Base\Auth;
use App\Controller\Base\Database;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;


if (!function_exists('env')) {
    function env($key, $default = null): ?string
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('get_bearer_token')) {
    function get_bearer_token() : string
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $bearerToken = '';
        if (preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
            $bearerToken = $matches[1];
        }
        return $bearerToken;
    }
}

if (!function_exists('get_user')) {
    function get_user($user_id)
    {
        $auth = new Database();
        $user = $auth->select('users', ['id' => $user_id]);
        $u = $user[0];
        unset($u['password']);
        return $u;
    }
}


if (!function_exists('generate_jwt_token')) {
    function generate_jwt_token($user_id)
    {
        $secret_key = env('JWT_SECRET', "5f2f7f6b328995e05214e4892c7e5093359931e13503bcc1b55a26f2ab8e4e7f");
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60); // valid for 1 hour

        $payload = array(
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'sub' => $user_id,
            'user' => get_user($user_id)
        );

        // Pass the correct arguments to JWT::encode()
        $token = JWT::encode($payload, $secret_key, 'HS256');
        // array_push($payload, $token);
        return $token;
    }
}



if (!function_exists('validate_jwt_token')) {

    function validate_jwt_token($jwt_token)
    {
        try {
            $secret_key = env('JWT_SECRET', "5f2f7f6b328995e05214e4892c7e5093359931e13503bcc1b55a26f2ab8e4e7f");
            $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));
            return $decoded;
        } catch (ExpiredException $e) {
            return get_error_response(['error' => 'Token expired']);
        } catch (SignatureInvalidException $e) {
            return get_error_response(['error' => 'Invalid token signature']);
        } catch (BeforeValidException $e) {
            return get_error_response(['error' => 'Token not valid yet']);
        } catch (Exception $e) {
            return get_error_response(['error' => 'Invalid token']);
        }
    }

}

if (!function_exists('get_success_response')) {
    function get_success_response($data)
    {
        $response = [
            'status' => 'success',
            'message' => 'Request successful',
            'data' => $data
        ];
        return json_encode($response, JSON_PRETTY_PRINT);
    }
}

if (!function_exists('get_error_response')) {
    function get_error_response($data)
    {
        $response = [
            'status' => 'failed',
            'message' => 'Request failed',
            'data' => $data
        ];
        return json_encode($response, JSON_PRETTY_PRINT);
    }
}

if (!function_exists('save_image')) {

    function save_image($file)
    {
        $uploadDirectory = 'uploads';
        if (!file_exists($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if ($file['error'] === UPLOAD_ERR_OK) {
            // Get file details
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];

            // Generate a unique filename to prevent overwriting existing files
            $uniqueFilename = uniqid() . '_' . $fileName;

            // Validate file type
            $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedFormats)) {
                return ['success' => false, 'message' => 'Invalid file format.'];
            }

            // Validate file size (5 MB)
            if ($fileSize > 5 * 1024 * 1024) {
                return ['success' => false, 'message' => 'File is too large.'];
            }

            // Validate image content
            if (!getimagesize($fileTmpName)) {
                return ['success' => false, 'message' => 'Invalid image file.'];
            }

            // Move the uploaded file to the desired directory
            $uploadPath = $uploadDirectory . '/' . $uniqueFilename;
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                return $uploadPath;
            }

            return false;
        } else {
            error_log($file['error']);
            return false;
        }
    }

}