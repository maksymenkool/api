#!/usr/bin/php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = \Api\Application::createByEnvironment();
$app['doctrine.migrations.app.main']->run();
