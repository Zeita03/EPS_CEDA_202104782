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
class PermisoTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "permiso";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getOrdenadosAgrupados() {
        $select = $this->getSql()->select();
        $select->order(["prioridad" => "ASC"]);
        $data = $this->selectWith($select)->toArray();
        $select->order(["menu" => "ASC", "prioridad" => "ASC"]);
        
        $arrayFinal = [];
        
        foreach ($data as $tmp) {
            $menuSeparado = explode("|", $tmp["menu"]);
            $arrayFinal[$menuSeparado[0]][] = $tmp; 
        }
        
        return $arrayFinal;
    }

}
