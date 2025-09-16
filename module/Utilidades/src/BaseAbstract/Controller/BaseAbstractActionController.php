<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

namespace Utilidades\BaseAbstract\Controller;

use Laminas\Mvc\Controller\AbstractActionController;

class BaseAbstractActionController extends AbstractActionController {

    private $authService;
    private $adapter;
    private $lastGeneratedValue;
    
    
    /**
     * Agrega mensajes entre controladores
     * 
     * @param type $tipo Tipo 1 éxito, tipo 2 error
     * @param type $texto Mensaje a mostrar
     */
    public function agregarMensajeGeneral($tipo, $texto) {
        $sessionM = new \Laminas\Session\Container('mensajes');
        $sessionM->tipo = $tipo;
        $sessionM->mensaje = $texto;
    }

    public function getMensajePendiente() {
        $sessionM = new \Laminas\Session\Container('mensajes');
        if (isset($sessionM->mensaje) && !empty($sessionM->mensaje)) {
            $texto = $sessionM->mensaje;
            $sessionM->mensaje = null;
            return [$sessionM->tipo, $texto];
        }
        return null;
    }

}
