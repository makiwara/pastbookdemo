<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();
$app->register(new RoutingServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return $app['request_stack']->getMasterRequest()->getBasepath().'/'.$asset;
    }));

    return $twig;
});


// Connect to Doctrine DBAL & bind to custom DBAL
require_once('../config/db.php');
require_once('dbal.php');
use \PastBookSocialApp\DBAL;
$app['DBAL'] = new \PastBookSocialApp\DBAL($app);

// Initialize oauth access secrets
require_once('../config/oauth.php');
require_once('oauth.php');
use \PastBookSocialApp\AbstractOAuth;
$auth = array();
foreach ($app['oauth'] as $provider => $config) {
	$auth[$provider] = \PastBookSocialApp\AbstractOAuth::Factory($provider, $config);
}
$app['OAuth'] = $auth;


return $app;
