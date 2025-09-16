<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DPPortada\Controller\Factory;

use Interop\Container\ContainerInterface;

/**
 * Description of IndexControllerFactory
 *
 * @author victor
 */
class IndexControllerFactory implements \Laminas\ServiceManager\Factory\FactoryInterface {

    //put your code here
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $authService = new \Laminas\Authentication\AuthenticationService();
        $adapter = $container->get('WriteAdapter');
        return new \DPPortada\Controller\IndexController($authService, $adapter);
    }

}
