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
class FormacionAcademicaTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "formacion_academica";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getFormacionAcademica() {
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->columns(['id_formacion_academica','institucion', 'titulo', 'updated_at', 'url_constancia'])->join(['l' => 'usuario'], 'formacion_academica.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getFormacionAcademicaByState($state) {
        $select = $this->getSql()->select();
        $select->where->equalTo("id_estado", $state);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getFormacionAcademicaByUser($id_usuario){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getFormacionAcademicaById($id_usuario, $id_solicitud){
        $select = $this->getSql()->select();
        // $select->join(["u" => "usuario_rol"], "u.rol = rol.rol");
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->where->equalTo("id_formacion_academica", $id_solicitud);
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getSolicitud($id_solicitud){
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->where->equalTo("id_formacion_academica", $id_solicitud);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Override del método insert para asignar período activo automáticamente
     */
    public function insert($set)
    {
        // Obtener período activo
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (!empty($periodoActivo)) {
            $set['id_periodo'] = $periodoActivo[0]['id_periodo'];
        }
        
        // Llamar al insert original
        $sql = $this->getSql();
        $insert = $sql->insert();
        $insert->values($set);
        
        $statement = $sql->prepareStatementForSqlObject($insert);
        return $statement->execute();
    }

    /**
     * Obtener formación académica del período activo solamente
     */
    public function getFormacionAcademicaPeriodoActual()
    {
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            return [];
        }

        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->join(['l' => 'usuario'], 'formacion_academica.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $select->where->equalTo("formacion_academica.id_periodo", $periodoActivo[0]['id_periodo']);
        $select->order(['formacion_academica.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener formación académica de un año específico con datos del usuario
     */
    public function getFormacionAcademicaAño($año, $estado = null)
    {
        $select = $this->getSql()->select();
        $select->join(['u' => 'usuario'], 'formacion_academica.id_usuario = u.usuario', ['nombre', 'grado_academico']);
        $select->join(['e' => 'estado'], 'e.id_estado = formacion_academica.id_estado');
        $select->where->expression('YEAR(formacion_academica.created_at) = ?', [$año]);
        
        if ($estado !== null && $estado !== 'todos') {
            $select->where->equalTo('formacion_academica.id_estado', $estado);
        }
        
        $select->order(['formacion_academica.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener formación académica de un período específico
     */
    public function getFormacionAcademicaPorPeriodo($idPeriodo)
    {
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->join(['l' => 'usuario'], 'formacion_academica.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $select->where->equalTo("formacion_academica.id_periodo", $idPeriodo);
        $select->order(['formacion_academica.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener formación académica del usuario en el período activo
     */
    public function getFormacionAcademicaByUserPeriodoActual($id_usuario)
    {
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            return [];
        }

        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = formacion_academica.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $select->where->equalTo("formacion_academica.id_periodo", $periodoActivo[0]['id_periodo']);
        $select->order(['formacion_academica.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Verificar si el usuario puede subir méritos (período activo)
     */
    public function puedeSubirMerito()
    {
        $periodosTable = new PeriodosTable($this->adapter);
        return $periodosTable->isPeriodoActivo();
    }

    /**
     * Obtener estadísticas del período activo
     */
    public function getEstadisticasPeriodoActual()
    {
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            return [
                'total' => 0,
                'pendientes' => 0,
                'aprobados' => 0,
                'rechazados' => 0
            ];
        }

        $select = $this->getSql()->select();
        $select->columns([
            'total' => new \Laminas\Db\Sql\Expression('COUNT(*)'),
            'pendientes' => new \Laminas\Db\Sql\Expression('SUM(CASE WHEN id_estado = 1 THEN 1 ELSE 0 END)'),
            'aprobados' => new \Laminas\Db\Sql\Expression('SUM(CASE WHEN id_estado = 2 THEN 1 ELSE 0 END)'),
            'rechazados' => new \Laminas\Db\Sql\Expression('SUM(CASE WHEN id_estado = 3 THEN 1 ELSE 0 END)')
        ]);
        $select->where->equalTo("id_periodo", $periodoActivo[0]['id_periodo']);
        
        $data = $this->selectWith($select)->toArray();
        return !empty($data) ? $data[0] : [
            'total' => 0,
            'pendientes' => 0,
            'aprobados' => 0,
            'rechazados' => 0
        ];
    }
}