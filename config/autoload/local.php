<?php

/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * NOTE: This file is ignored from Git by default with the .gitignore included
 * in laminas-mvc-skeleton. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */
use Laminas\Db\Adapter;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'db'=>[
		'driver'=>'Pdo',
        'dsn'=>'mysql:dbname=my_thimphu_portal;hostname=127.0.0.1;charset=utf8mb4',
		'driver-options'=>[],
        'username'=> 'root',
        'password' =>'',//'BhutanPost2022*/*'//
		'options' => [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
	],
    'rma_api' => [
        'base_url' => 'https://apipg.athang.com:8080/api/v1',
        // Replace with actual credentials issued by Athang/RMA.
        'merchant_id' => '',
        'jwt_secret' => '',
    ],
];