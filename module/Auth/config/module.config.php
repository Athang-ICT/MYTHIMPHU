<?php
namespace Auth;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Session\SessionManager;
use Auth\Factory\SessionManagerFactory;
use Auth\Storage\AuthStorage;
use Auth\Factory\AuthenticationServiceFactory;

return [
    'router' => [
        'routes' => [
            'auth' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/auth[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        // Allow numeric news IDs passed via route segment
                        'id'     => '[a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'logout-callback' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/logout-callback',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'logoutCallback',
                    ],
                ],
            ],
            'verify-2fa' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/verify-2fa',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'verify-2fa',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'controllers'     => [
        'factories' => [
            Controller\AuthController::class  => function ($container) {
                return new Controller\AuthController(
                    $container,
                    $container->has(\DomesticPayment\Service\RmaPaymentService::class)
                        ? $container->get(\DomesticPayment\Service\RmaPaymentService::class)
                        : null,
                    $container->has(\DomesticPayment\Model\PaymentTransactionTable::class)
                        ? $container->get(\DomesticPayment\Model\PaymentTransactionTable::class)
                        : null
                );
            },
        ],
    ],
    'service_manager' => [
        'factories' => [
            AuthStorage::class => InvokableFactory::class,
            SessionManager::class => SessionManagerFactory::class,
            \Auth\Service\SSOService::class => function ($container) {
                $config = $container->get('config') ?? [];
                return new \Auth\Service\SSOService($config);
            },
        ],
    ],
];
