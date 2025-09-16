<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Meritos\Controller\Factory;

use Interop\Container\ContainerInterface;

/**
 * Description of IndexControllerFactory
 *
 * @author eliel
 */
class IndexControllerFactory implements \Laminas\ServiceManager\Factory\FactoryInterface {

    //put your code here
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $authService = new \Laminas\Authentication\AuthenticationService();
        $adapter = $container->get('WriteAdapter');
        return new \Meritos\Controller\IndexController($authService, $adapter);
    }

}
