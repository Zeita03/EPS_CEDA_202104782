<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Meritos;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'meritosHome' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/Meritos',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'meritos' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/admon/:action[/:val1][/:val2]',
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z]*',
                                'action' => '[a-zA-Z][a-zA-Z]*',
                                'val1' => '[a-zA-Z0-9]([a-zA-Z0-9_-]|(%20)|\.)*',
                                'val2' => '[a-zA-Z0-9]([a-zA-Z0-9_-]|(%20)|\.)*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'=> 'meritos'
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'template_map' => [
            'layout/layoutAdmon' => __DIR__ . '/../view/layout/layoutAdmon.phtml',
        ],
    ],
];
