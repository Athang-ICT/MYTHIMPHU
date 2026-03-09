<?php
/*
  * chophel@athang.com @2021
 */
namespace Auth;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Db\Adapter\Adapter;

class Module
{
    const VERSION = '3.0.3-dev';
    private $auth;

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        //$this->bootstrapSession($mvcEvent);
        $config = $e->getApplication()->getServiceManager()->get('config');
        
        // Apply session configuration BEFORE creating SessionManager
        // This MUST happen before session_start() is called anywhere
        if (isset($config['session_config'])) {
            // Set session cookie parameters - must be called before session_start()
            $lifetime = $config['session_config']['cookie_lifetime'] ?? 0;
            $path = '/';
            $domain = '';
            $secure = $config['session_config']['cookie_secure'] ?? false;
            $httponly = $config['session_config']['cookie_httponly'] ?? true;
            
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
            
            // Set garbage collection max lifetime
            if (isset($config['session_config']['gc_maxlifetime'])) {
                ini_set('session.gc_maxlifetime', (string)$config['session_config']['gc_maxlifetime']);
            }
            
            // Set session name
            if (isset($config['session_config']['name'])) {
                session_name($config['session_config']['name']);
            }
        }
        
        // get the database section
        $dbAdapter = new Adapter($config['db']);
    
        // crate the TableGateway object specifying the table name
        $sessionTableGateway = new TableGateway('session', $dbAdapter);
        // create your saveHandler object
        $saveHandler = new DbTableGateway($sessionTableGateway, new DbTableGatewayOptions());
				
        // pass the saveHandler to the sessionManager and start the session
        $sessionManager = $e->getApplication()->getServiceManager()->get(SessionManager::class);;
        
        $sessionManager->setSaveHandler($saveHandler);
    }
}
