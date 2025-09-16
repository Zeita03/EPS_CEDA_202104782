<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Auth\Model;

/**
 * Description of AuthEntity
 *
 * @author victor
 */
class AuthEntity {

    private $id;
    private $rol;
    private $data;
    private $autenticado;

    function getId() {
        return $this->id;
    }

    function getData() {
        return $this->data;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setData($data) {
        $this->data = $data;
    }

    function getRol() {
        return $this->rol;
    }

    function setRol($rol) {
        $this->rol = $rol;
    }

    function isAutenticado() {
        return $this->autenticado;
    }

    function setAutenticado($autenticado) {
        $this->autenticado = $autenticado;
    }
    
    function __construct($id, $rol, $data) {
        $this->id = $id;
        $this->rol = $rol;
        $this->data = $data;
        $this->autenticado = true;
    }

}
