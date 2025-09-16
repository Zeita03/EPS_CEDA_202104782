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
class BitacoraTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "bitacora";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }
    
    public function getAllLogs() {
        $select = $this->getSql()->select();
        // $select->join(["e" => "usuario"], "e.usuario = bitacora.id_usuario");
        $select->columns(['created_at','accion', 'id_bitacora'])->join(['u' => 'usuario'], 'bitacora.id_usuario = u.usuario', ['nombre', 'email']);
        return $this->selectWith($select)->toArray();
    }

}
