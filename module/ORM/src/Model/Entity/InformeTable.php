<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ORM\Model\Entity;

/**
 * Description of InformesTable
 *
 * @author eliel
 */
class InformeTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "informes";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getInformes() {
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at'])->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre']);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getInformesByUser($id_usuario){
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at'])->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre']);
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }


}
