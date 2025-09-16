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
class PremiosTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "premios";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getPremios() {
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");

        $select->columns(['id_premio','institucion', 'reconocimiento', 'updated_at', 'url_file'])->join(['l' => 'usuario'], 'premios.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getPremiosByState($state) {
        $select = $this->getSql()->select();
        $select->where->equalTo("id_estado", $state);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getPremiosByUser($id_usuario){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }


    public function getPremiosById($id_usuario, $id_premio){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_premio", $id_premio);
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getSolicitud($id_solicitud){
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_premio", $id_solicitud);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

}
