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
class ConfiguracionTable extends \Laminas\Db\TableGateway\AbstractTableGateway {
    
    protected $table = "configuracion";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getConfiguracion() {
        $select = $this->getSql()->select();
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    

    

}
