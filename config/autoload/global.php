<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
use Laminas\Db\Adapter\AdapterAbstractServiceFactory;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Validator\RemoteAddr;
use Laminas\Session\Validator\HttpUserAgent;
    
return [
    'session_validators' => [
        RemoteAddr::class,
        HttpUserAgent::class,
    ],
    'session_config' => [
        'remember_me_seconds' => 1800, // 30 minutes
        'gc_maxlifetime' => 1800, // 30 minutes
        'cookie_lifetime' => 1800, // 30 minutes
		//'cache_expire' =>5,
        'use_cookies' => true,
        'cookie_secure' => false, // Set to true if using HTTPS
        'cookie_httponly' => true,
        'name' => 'MythimphuSession',
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class,
    ],
    'static_salt' => 'Mythimphu*BTN@2026(TPHU)#A-ICT',
    'ditt_api_census'    => 'http://api.censusditt.bt/final_DCRC_API/',
    
    // RMA Domestic Payment API Configuration
    'rma_api' => [
        'base_url' => 'https://apipg.athang.com:8080/api/v1',
        'jwt_secret' => getenv('RMA_JWT_TOKEN') ?: '',
        'merchant_id' => getenv('RMA_MERCHANT_ID') ?: '',
    ],
    
];