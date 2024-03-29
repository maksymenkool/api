<?php
/**
 * Mail service config for dev environments
 * @var $app Api\Application
 */
require __DIR__ . '/email.php';

use Api\Test\Mailer;

$app['mailer'] = function ($app) {
    return new Mailer($app['config']['email']['message_log']);
};
