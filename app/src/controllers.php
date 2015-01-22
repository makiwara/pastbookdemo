<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));


/* ********
 *
 * Index page
 *
 */
$app->get('/', function () use ($app) {
    $auth_links = array();
    foreach ($app["OAuth"] as $provider => $api) {
        $auth_links[$provider] = $provider;
    }
    return $app['twig']->render('index.html', array(
        "auth_links" => $auth_links
    ));
})
->bind('homepage')
;


/* ********
 *
 * Registering user and forwarding for Instagram auth
 *
 */
$app->get('/auth', function () use ($app) {
    $user = $app["DBAL"]->getUserByEmail( $_GET["email"] );
    $app['session']->set('user', $user);
    $app['session']->set('range', explode( ":", $_GET["range"] ));
    return $app->redirect($app["OAuth"][$_GET["provider"]]->getLoginUrl());
});

/* ********
 *
 * Received OAuth token and store it. Fills in queue for import based on range.
 *
 */
$app->get('/auth/{provider}', function ($provider) use ($app) {
    $code = $_GET['code'];
    $success = "false";
    // check whether the user has granted access
    if (isset($code)) {
        $token = $app["OAuth"][$provider]->getOAuthToken($code);
        $success = "true";
        $app["DBAL"]->updateUserToken($app["session"]->get('user'), $provider, $token);
        // Enqueue photos
        $app["OAuth"][$provider]->setOAuthToken($token);
        $range = $app["session"]->get('range');
        switch ($range[0]) {
            case 'year': 
                $photos = $app["OAuth"][$provider]->getMedia(NULL, $range[1]);
                break;
            case 'month':
                $date = explode("/", $range[1]);
                $photos = $app["OAuth"][$provider]->getMedia(NULL, $date[1], $date[0]);
                break;
            default:
            case 'recent': 
                $photos = $app["OAuth"][$provider]->getMedia($range[1]);
                break;
        }
        $app["DBAL"]->enqueue($app["session"]->get('user'), $photos);
    }
    return $app['twig']->render('auth_done.html', array( "auth_success" => $success ));
});





// Controller stubs
$app->get('/status', function () use ($app) {
    return '{done: true}';
});

$app->get('/queue', function () use ($app) {
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
