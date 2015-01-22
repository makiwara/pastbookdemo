<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));



$app->get('/', function () use ($app) {
    $auth_links = array();
    foreach ($app["OAuth"] as $provider => $api) {
        $auth_links[$provider] = $api->getLoginUrl();
    }
    return $app['twig']->render('index.html', array(
        "auth_links" => $auth_links
    ));
})
->bind('homepage')
;


$app->get('/auth/{provider}', function ($provider) use ($app) {
    $code = $_GET['code'];
    $success = "false";
    // check whether the user has granted access
    if (isset($code)) {
        $token = $app["OAuth"][$provider]->getOAuthToken($code);
        $success = "true";
        // TODO store token in database
    }
    return $app['twig']->render('auth_done.html', array( "auth_success" => $success, "auth_hash" => $token ));
    // todo auth_hash from user
});


// Controller stubs
$app->get('/status', function () use ($app) {
    return '{done: true}';
});

$app->get('/queue', function () use ($app) {
    return '{done: true}';
});

$app->get('/process', function () use ($app) {
    return '{done: true}';
});

$app->get('/init', function () use ($app) {
    if ($app["DBAL"]->isInit())
        return "ALREADY COMPLETE.";
    else {
        $app["DBAL"]->init();
        return 'COMPLETE.';
    }
});
$app->get('/drop', function () use ($app) {
    $app["DBAL"]->drop();
    return 'DROPPED.';
});


// Default error handlers from silex skeleton. Untouched.
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }
    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );
    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
