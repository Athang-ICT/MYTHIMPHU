<?php
namespace News;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
	
    'router' => [
        'routes' => [      
            'n' => [
				'type'    => Segment::class,
				'options' => [ 
						'route'    => '/n[/:action[/:id]]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],
						'defaults' => [
								'controller' => Controller\IndexController::class,
								'action'   => 'index',
						],
				],
            ],
			'newspaper' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/n/newspaper[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\NewspaperController::class,
								'action'     => 'pdfnews',
						],
				],
				'may_terminate' => true,
				'child_routes'  => [
					'defaults' => [
						'type'      => Segment::class,
						'options'   => [
							'route' => '/[:controller[/:action][/:id]]',
							'constraints' => [
								'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'      => '[0-9]*',
							],
							'defaults' => [
							],
						],
					],
					'paginator' => [
						'type' => Segment::class,
						'options' => [
							'route' => '/[page/:page]',
							'constraints' => [
								'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'      => '[0-9]*', 
							],
							'defaults' => [
								'__NAMESPACE__' => 'News\Controller',
								'controller' => Controller\NewspaperController::class,
							],
						],
					],
				],
			],
		],
	],	
	'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view/',
        ],
		'display_exceptions' => true,
    ],
];
