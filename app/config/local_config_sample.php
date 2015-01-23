<?php

$app['dbal'] = array(
    'db.options' => array(
        'dbname' => 'pastbook',
        'user' => 'TODO USERNAME',
        'password' => 'TODO PASSWORD',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ),
    'lock' => false,
    'salt' => "TODO SOME RANDOM STRING",
);

$app['oauth'] = array(
    'instagram' => array(
        'key'    => 'TODO KEY',
        'secret' => 'TODO SECRET',
    ),
);

$app['uploader'] = array(
	'local_path' => 'TODO LOCAL/',
	'url_prefix' => 'TODO URL/',
);

$app['swiftmailer.options'] = array(
    'host'       => 'smtp.gmail.com',
    'port'       => 465,
    'username'   => 'TODO YOUR EMAIL',
    'password'   => 'TODO YOUR EMAIL PASSWORD',
    'encryption' => 'ssl',
    'auth_mode'  => 'login',
);
