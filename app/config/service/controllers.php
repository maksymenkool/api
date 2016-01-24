<?php
/**
 * Controllers
 * @var $app Application
 */

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app['controller.user'] = $app->share(function($app) {
    return new Controller\UserController(
        $app['mapper.db.user'],
        $app['email.mailer'],
        $app['storage.expirable_storage'],
        $app['security.session_manager'],
        $app['facebook'],
        $app['password_generator']
    );
});

$app['controller.travel'] = $app->share(function($app) {
    return new Controller\TravelController(
        $app['mapper.db.travel']
    );
});

$app['controller.travel_comments'] = $app->share(function($app) {
    return new Controller\TravelCommentController(
        $app['mapper.db.travel_comment'],
        $app['mapper.json.travel_comment']
    );
});
