<?php

$app['dbal'] = array(
    'db.options' => array(
        'dbname' => 'pastbook',
        'user' => 'USERNAME',
        'password' => 'PASSWORD',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ),
    'lock' => false,
);

$app['oauth'] = array(
    'instagram' => array(
        'key'    => 'KEY',
        'secret' => 'SECRET',
    ),
);

$app['uploader'] = array(
	'local_path' => 'LOCAL',
	'url_prefix' => 'URL',
);

