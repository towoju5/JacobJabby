<?php


use App\Controller\AuthController;
use App\Controller\Links;
use App\Middleware\JwtAuthMiddleware;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

// require __DIR__ . '/../vendor/autoload.php';

require 'vendor/autoload.php';


try {

    /**
     * Instantiate App
     *
     * In order for the factory to work you need to ensure you have installed
     * a supported PSR-7 implementation of your choice e.g.: Slim PSR-7 and a supported
     * ServerRequest creator (included with Slim PSR-7)
     */
    $app = AppFactory::create();

    /**
     * The routing middleware should be added earlier than the ErrorMiddleware
     * Otherwise exceptions thrown from it will not be handled by the middleware
     */
    $app->addRoutingMiddleware();

    /**
     * Add Error Middleware
     *
     * @param bool                  $displayErrorDetails -> Should be set to false in production
     * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
     * @param bool                  $logErrorDetails -> Display error details in error log
     * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger  
     *
     * Note: This middleware should be added last. It will not handle any exceptions/errors
     * for middleware added after it.
     */
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    // Define app routes
    $app->post('/login', [AuthController::class, 'login']);


    $app->post('/register', [AuthController::class, 'register']);


    $app->post('/link',         [Links::class, 'store']);
    $app->get('/links',         [Links::class, 'get']);
    $app->get('/link/{linkId}', [Links::class, 'show']);
    $app->put('/link/{linkId}', [Links::class, 'update']);
    $app->delete('/link/{linkId}', [Links::class, 'destroy']);


    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    // Set the Not Found Handler
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('404 NOT FOUND');

            return $response->withStatus(404);
        }
    );

    // Set the Not Allowed Handler
    $errorMiddleware->setErrorHandler(
        HttpMethodNotAllowedException::class,
        function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('405 NOT ALLOWED');

            return $response->withStatus(405);
        }
    );


    // Run app
    $app->run();
} catch (\Throwable $th) {
    return get_error_response(['error' => $th->getMessage()]);
}