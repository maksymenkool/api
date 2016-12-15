<?php
/**
 * Application configuration
 *
 * @var $app Api\Application
 */

use Api\Event\UpdatePicEvent;
use Api\Exception\ApiException;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Sorien\Provider\PimpleDumpProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app->error(function (Throwable $e) use ($app) {
    if ($e instanceof ApiException) {
        $map = [
            ApiException::VALIDATION => Response::HTTP_FORBIDDEN,
            ApiException::USER_EXISTS => Response::HTTP_FORBIDDEN,
            ApiException::AUTH_REQUIRED => Response::HTTP_UNAUTHORIZED,
            ApiException::INVALID_EMAIL_PASSWORD => Response::HTTP_UNAUTHORIZED,
            ApiException::INVALID_TOKEN => Response::HTTP_UNAUTHORIZED,
            ApiException::RESOURCE_NOT_FOUND => Response::HTTP_NOT_FOUND,
            ApiException::ACCESS_DENIED => Response::HTTP_FORBIDDEN,
        ];
        $code = $e->getCode();
        $message = $e->getMessage();
        $status = $map[$e->getCode()] ?? Response::HTTP_INTERNAL_SERVER_ERROR;
    } elseif ($e instanceof HttpExceptionInterface) {
        $code = 0;
        $message = $e->getMessage();
        $status = $e->getStatusCode();
    } else {
        error_log($e);
        $app['monolog']->emergency($e->getMessage());
        $code = 0;
        $message = 'Internal Server Error';
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
    }
    return $app->json(
        [
            'code' => $code,
            'error' => $message,
        ],
        $status
    );
});

// Register additional HTTP GET arguments
$app->before(function (Request $request) {
    if ($request->isMethod(Request::METHOD_GET)) {
        foreach ($request->query as $key => $val) {
            if (false === $request->attributes->has($key)) {
                $request->attributes->set($key, $val);
            }
        }
    }
});

// JSON Response
$app->view(function (array $response) use ($app) {
    return $app->json($response);
});

$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
});

// Twig
$app->register(new TwigServiceProvider, [
    'twig.path' => __DIR__ . '/../../view',
]);

// Monolog
$app->register(new MonologServiceProvider, [
    'monolog.logfile' => $app['config']['log']['main']['file'],
    'monolog.level' => $app['config']['log']['main']['level'],
    'monolog.name' => 'api',
]);

// Pimple dumper
if ($app['env'] === 'dev') {
    $app->register(new PimpleDumpProvider());
}
