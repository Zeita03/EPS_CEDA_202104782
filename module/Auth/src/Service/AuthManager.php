<?php

namespace Auth\Service;

use Laminas\Authentication\Result;

/**
 * The AuthManager service is responsible for user's login/logout and simple access 
 * filtering. The access filtering feature checks whether the current visitor 
 * is allowed to see the given page or not.  
 */
class AuthManager {

    private $authService;
    private $adapter;
    public $acceso;

    function __construct($authService, $adapter) {
        $this->authService = $authService;
        $this->adapter = $adapter;
    }

    public function login($id, $passwd, $rememberMe, $rol) {
        // verificando si ya se habia autenticado...
        if ($this->authService->getIdentity() != null) {
            //throw new \Exception('Already logged in');
            return false;
        }
        
        // Authenticate with login/password.
        $authAdapter = new \Auth\Service\AuthAdapter($this->authService, $this->adapter);
        $authAdapter->setId($id);
        $authAdapter->setPasswd($passwd);
        $authAdapter->setRol($rol);
        $result = $this->authService->authenticate($authAdapter);
        // If user wants to "remember him", we will make session to expire in 
        // one month. By default session expires in 1 hour (as specified in our 
        // config/global.php file).
        if ($result->getCode() == Result::SUCCESS && $rememberMe) {
            // Session cookie will expire in 1 month (30 days).
            $this->sessionManager->rememberMe(60 * 60 * 24 * 30);
        }

        return $result;
    }

    /**
     * Performs user logout.
     */
    public function logout() {
        // Allow to log out only when user is logged in.
        if ($this->authService->getIdentity() == null) {
            throw new \Exception('The user is not logged in');
        }

        // Remove identity from session.
        $this->authService->clearIdentity();
    }

    /**
     * Verificar accesos a la plataforma...
     * Sí tiene acceso, entonces se guarda el registro de acceso otorgado por el 
     * conjunto de permisos heredados por los roles (el modelo permite multirol)
     */
    public function verificarPermiso($controlador, $accion) {
        $usuarioTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        $this->acceso = $usuarioTable->getPermisosParaAccion($this->authService->getIdentity()->getId(), $controlador, $accion);
        return (!empty($this->acceso)) ? true : false;
    }

}
