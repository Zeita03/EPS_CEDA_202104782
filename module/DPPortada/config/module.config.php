<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace DPPortada;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'welcome' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'homepage',
                    ],
                ],
            ],
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'perfil' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/perfil',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'perfil',
                    ],
                ],
            ],
            'recuperarpass' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/recuperarpass',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'recuperarpass',
                    ],
                ],
            ],
            'register' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/register',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'register',
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
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
