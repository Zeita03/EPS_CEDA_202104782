<?php

namespace Auth\Service;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result as AuthResult;

class AuthAdapter implements AdapterInterface {

    private $id;
    private $passwd;
    private $rol;
    private $adapter;
    private $authService;

    function __construct($authService, $adapter) {
        $this->authService = $authService;
        $this->adapter = $adapter;
    }

    function getId() {
        return $this->id;
    }

    function getPasswd() {
        return $this->passwd;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setPasswd($passwd) {
        $this->passwd = $passwd;
    }

    function getRol() {
        return $this->rol;
    }

    function setRol($rol) {
        $this->rol = $rol;
    }

    public function authenticate() {
        $authHelper = new \ORM\Model\Helper\AuthHelper($this->adapter);
        $result = $authHelper->autenticar(["id" => $this->id, "passwd" => $this->passwd, "modulo" => $this->rol]);
        if ($result["error"] == false && $result["auth"] == true) {
            return new AuthResult(AuthResult::SUCCESS, new \Auth\Model\AuthEntity($this->id, $this->rol, $result["data"]));
        } else {
            return new AuthResult(AuthResult::FAILURE_CREDENTIAL_INVALID, null);
        }
    }

}
