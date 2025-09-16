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
 * @author eliel
 */
class PuntosTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "puntos";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }


    public function getPuntosByUser($id_usuario){
        $year = date("Y");
        $select = $this->getSql()->select();
        $select->where->equalTo("id_usuario", $id_usuario);
        $select->where->equalTo("year", $year);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

}
