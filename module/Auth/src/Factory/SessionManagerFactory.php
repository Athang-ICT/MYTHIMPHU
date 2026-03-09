<?php
/**
 * chophel@athang.com @2021
 */
namespace Auth\Factory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

class SessionManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['session_config'])) {
            $sessionManager = new SessionManager();
            Container::setDefaultManager($sessionManager);
            return $sessionManager;
        }
        
        // create session config if exists in global configuration
        $sessionConfig = null;
        if (isset($config['session_config'])) {
            $sessionConfig = new SessionConfig();
            $sessionConfig->setOptions($config['session_config']);
            
            // Explicitly set cookie lifetime and gc_maxlifetime using setter methods
            if (isset($config['session_config']['cookie_lifetime'])) {
                $sessionConfig->setCookieLifetime($config['session_config']['cookie_lifetime']);
            }
            if (isset($config['session_config']['gc_maxlifetime'])) {
                $sessionConfig->setGcMaxlifetime($config['session_config']['gc_maxlifetime']);
            }
        }
        // create session storage if exists in global configuration
        $sessionStorage = null;
        if (isset($config['session_storage'])) {
            $class = $config['session_storage']['type'];
            $sessionStorage = new $class('hpv');
        }
        
        // optional get a save handler and store it into SessionManager (currently null)
        $sessionManager = new SessionManager(
            $sessionConfig,
            $sessionStorage,
            null
        );
        Container::setDefaultManager($sessionManager);
        return $sessionManager;
    }
}
