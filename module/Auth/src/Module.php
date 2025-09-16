<?php

/**
 * @link      http://github.com/zendframework/LaminasSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Laminas Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Auth;

class Module {

    const VERSION = '3.0.3-dev';

    public function getConfig() {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * configuracion posterior al MVC bootstrapping. 
     */
    public function onBootstrap(\Laminas\Mvc\MvcEvent $event) {
        $application = $event->getApplication();
        $serviceManager = $application->getServiceManager();

        // The following line instantiates the SessionManager and automatically
        // makes the SessionManager the 'default' one.
        $sessionManager = $serviceManager->get(\Laminas\Session\SessionManager::class);
    }

}
