<?php

mb_internal_encoding('UTF-8');
mb_http_input('UTF-8');

session_start();

require_once(__DIR__.'/../vendor/autoload.php');

$settings = require_once(__DIR__ . '/../App/Config/config.php');

$app = new \Slim\App($settings);
$container = $app->getContainer();

/**
 * Adds pdo for database connections
 *
 * @param \Slim\Container $container
 *
 * @return PDO
 */
$container['pdo'] = function($container) {
    $settings = $container->get('settings');

    $dns = 'mysql:dbname='.$settings['db']['name'].';host='.$settings['db']['host'];
    $pdo = new \PDO($dns, $settings['db']['user'], $settings['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
};

/**
 * Adds Twig for template rendering
 *
 * @param \Slim\Container $container
 *
 * @return \Slim\Views\Twig
 */
$container['view'] = function($container) {
    $view = new \Slim\Views\Twig(__DIR__.'/../App/Views');
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

/**
 * Add UserFactory to retrieve user models
 *
 * @param \Slim\Container $container
 *
 * @return \FabianGO\Assessment\Factories\UserFactory
 */
$container['UserFactory'] = function($container) {
    return new \FabianGO\Assessment\Factories\UserFactory($container);
};

/**
 * Set dependencies for WebController class
 *
 * @param \Slim\Container $container
 *
 * @return \FabianGO\Assessment\Controllers\WebController
 */
$container['FabianGO\Assessment\Controllers\WebController'] = function($container) {
    return new \FabianGO\Assessment\Controllers\WebController($container);
};

$app->get('/', '\\FabianGO\\Assessment\\Controllers\\WebController:index')->setName('index');
$app->post('/', '\\FabianGO\\Assessment\\Controllers\\WebController:processIndex')->setName('index_process');
$app->get('/user/{id}', '\\FabianGO\\Assessment\\Controllers\\WebController:user')->setName('user');

$app->run();