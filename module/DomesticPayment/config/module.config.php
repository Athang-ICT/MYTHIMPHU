<?php
declare(strict_types=1);

use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use DomesticPayment\Controller\DomesticPaymentController;
use DomesticPayment\Factory\DomesticPaymentControllerFactory;
use DomesticPayment\Service\RmaPaymentService;
use DomesticPayment\Factory\RmaPaymentServiceFactory;
use DomesticPayment\Model\PaymentTransactionTable;
use DomesticPayment\Factory\PaymentTransactionTableFactory;

return [
    'router' => [
        'routes' => [
            'domestic-payment' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/payment[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => DomesticPaymentController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'rma-payment-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/payment[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => DomesticPaymentController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            DomesticPaymentController::class => DomesticPaymentControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            RmaPaymentService::class => RmaPaymentServiceFactory::class,
            PaymentTransactionTable::class => PaymentTransactionTableFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'domestic-payment' => __DIR__ . '/../view',
        ],
    ],
];
