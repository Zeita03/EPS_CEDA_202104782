<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ORM\Model\Entity;

/**
 * Description of AdminTable
 *
 * @author tobias
 */
class RolPermisoTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "rol_permiso";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getAllPermisos($rol) {
        return [];
    }

    public function getAllPermisosIndexados($rol) {
        $select = $this->getSql()->select();
        $select->where->equalTo("rol", $rol);
        $data = $this->selectWith($select);
        
        $arrayFinal = [];
        foreach ($data as $tmp) {
            $arrayFinal[$tmp["permiso"]] = $tmp["acceso"];
        }
        
        return $arrayFinal;
    }

}
