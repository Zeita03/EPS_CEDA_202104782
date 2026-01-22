<?php

namespace ORM\Model\Entity;

/**
 * Description of PremiosTable
 *
 * @author eliel
 */
class PremiosTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM premios p ...
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
        $select->columns(['id_premio','nombre_premio', 'institucion', 'updated_at', 'url_constancia'])->join(['l' => 'usuario'], 'premios.id_usuario = l.usuario', ['nombre', 'grado_academico']);
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
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getPremiosById($id_usuario, $id_solicitud){
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_premio", $id_solicitud);
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
     * Obtener premios del período activo solamente
     */
    public function getPremiosPeriodoActual()
    {
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            return [];
        }

        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->join(['l' => 'usuario'], 'premios.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $select->where->equalTo("premios.id_periodo", $periodoActivo[0]['id_periodo']);
        $select->order(['premios.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener premios de un período específico
     */
    public function getPremiosPorPeriodo($idPeriodo)
    {
        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->join(['l' => 'usuario'], 'premios.id_usuario = l.usuario', ['nombre', 'grado_academico']);
        $select->where->equalTo("premios.id_periodo", $idPeriodo);
        $select->order(['premios.created_at' => 'DESC']);
        
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener premios del usuario en el período activo
     */
    public function getPremiosByUserPeriodoActual($id_usuario)
    {
        $periodosTable = new PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            return [];
        }

        $select = $this->getSql()->select();
        $select->join(["e" => "estado"], "e.id_estado = premios.id_estado");
        $select->where->equalTo("id_usuario", $id_usuario);
        $select->where->equalTo("premios.id_periodo", $periodoActivo[0]['id_periodo']);
        $select->order(['premios.created_at' => 'DESC']);
        
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