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
class CapacitacionProfesionalTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "capacitacion_profesional";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getCapacitacionProfesional() {
        /*$select = $this->getSql()->select();
        $data = $this->selectWith($select)->toArray();

        return $data;*/
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = capacitacion_profesional.id_estado");

        $select->columns(['id_capacitacion','institucion', 'area_capacitacion', 'updated_at', 'url_constancia'])->join(['l' => 'usuario'], 'capacitacion_profesional.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getCapacitacionProfesionalByState($state) {
        $select = $this->getSql()->select();
        $select->where->equalTo("id_estado", $state);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getCapacitacionProfesionalByUser($id_usuario){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = capacitacion_profesional.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    public function getCapacitacionById($id_usuario, $id_solicitud){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = capacitacion_profesional.id_estado");
        $select->where->equalTo("id_capacitacion", $id_solicitud);
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }


    public function getSolicitud($id_solicitud){
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = capacitacion_profesional.id_estado");
        $select->where->equalTo("id_capacitacion", $id_solicitud);
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

}
