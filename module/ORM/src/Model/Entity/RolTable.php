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
class RolTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "rol";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getRolesUsuario($usuario) {
        $select = $this->getSql()->select();
        $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->where->equalTo("u.usuario", $usuario);
        return $this->selectWith($select)->toArray();
    }
    
    public function getRolesUsuarioArray($usuario) {
        $roles = $this->getRolesUsuario($usuario);
        $arrayFinal = [];
        foreach ($roles as $tmp) {
            $arrayFinal[] = $tmp["rol"];
        }
        return $arrayFinal;
    }

    public function getAll() {
        $select = $this->getSql()->select();
        $select->order(["rol" => "ASC"]);
        return $this->selectWith($select)->toArray();
    }

    public function getRolesArray($id) {
        $select = $this->getSql()->select();
        $select->columns(["rol"]);
        $select->where->equalTo("usuario", $id);
        $data = $this->selectWith($select)->toArray();
        $arrayFinal = [];
        foreach ($data as $tmp) {
            $arrayFinal[] = $tmp["rol"];
            //var_dump($arrayFinal); die;
        }
        return $arrayFinal;
    }


}
