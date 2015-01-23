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
->bind('homepage');


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

    // Check whether the user has granted access
    if (isset($code)) {
        $success = "true";

        // Store token for future use.
        $token = $app["OAuth"][$provider]->getOAuthToken($code);
        $app["DBAL"]->updateUserToken($app["session"]->get('user'), $provider, $token);

        // Gather metadata and enqueue photos.
        // Upload process is handled separately.
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
            case 'recent': 
                $photos = $app["OAuth"][$provider]->getMedia($range[1]);
                break;
        }
        $app["DBAL"]->enqueue($app["session"]->get('user'), $photos);
    }
    return $app['twig']->render('auth_done.html', array( "auth_success" => $success ));
})
->bind('auth');


/* ********
 *
 * Return current queue state for sessioned user in JSON.
 *
 */
$app->get('/progress', function () use ($app) {

    // We will process a single upload each time progress is called. 
    // This allows for simple testing of prototype without crontab.
    process_upload($app); 

    $result = array("done" => true);
    if ($app["session"]->get('user')) {
        $photos = $app["DBAL"]->getPhotos($app["session"]->get('user'));
        $result["photos"] = $photos;
        foreach ($photos as $photo) {
            if ($photo["state"] !== "done") { 
                $result["done"]=false; break; 
        }}
    }
    return json_encode($result);
});



/* ********
 *
 * Process upload
 *
 */
$app->get('/upload', function () use ($app) {
    for ($i=0; $i < 10; $i++)
        process_upload($app);
    return "DONE.";
});
function process_upload($app) {
    $photo = $app["DBAL"]->dequeue();
    if ($photo) {

        // upload original picture and thumbnail
        $updates = array(
            "url" => $app["Uploader"]->upload($photo["url_original"], $photo["id"]),
            "url_thumbnail" => $app["Uploader"]->upload($photo["url_thumbnail_original"], $photo["id"]."-"),
            "state" => "done",
        );
        $app["DBAL"]->updatePhoto($photo, $updates);

        // if this photo was the last one in user’s queue — send her an email.
        if ($app["DBAL"]->isQueueComplete($photo)) {
            $user = $app["DBAL"]->getUserById($photo["user_id"]);
            $app['mailer']->send(\Swift_Message::newInstance()
                ->setSubject("Your photos are ready!")
                ->setFrom(array($app['swiftmailer.options']['username']))
                ->setTo(array($user["email"]))   
                ->setBody($app['twig']->render('email.txt', array(
                        "url" => $app["url_generator"]->generate('photos', array("hash" => $user["hash"]), true)
                    )
            )));
        }       
    }
}


/* ********
 *
 * Display user page
 *
 */
$app->get('/photos/{hash}', function ($hash) use ($app) {
    $user = $app["DBAL"]->getUserByHash( $hash );
    if (!$user) return $app->redirect("/");
    $photos = $app["DBAL"]->getPhotos( $user );
    return $app['twig']->render('photos.html', array(
        "user" => $user,
        "photos" => $photos,
    ));
})->bind('photos');


/* ********
 *
 * Setup and demolish databases (set 'lock' in 'dbal' configuration to block).
 *
 */
$app->get('/init', function () use ($app) {
    if ($app["DBAL"]->isInit()) 
        return "ALREADY COMPLETE.";
    else {
        $app["DBAL"]->init();
        return 'COMPLETE.';
    }
});
$app->get('/drop', function () use ($app) {
    if (!$app["dbal"]["lock"])
        $app["DBAL"]->drop();
    return 'DROPPED.';
});

/* ********
 *
 * Default error handlers from silex skeleton. Untouched.
 *
 */
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
