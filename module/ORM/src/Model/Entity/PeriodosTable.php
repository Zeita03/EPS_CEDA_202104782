<?php

namespace ORM\Model\Entity;

/**
 * Description of PeriodosTable
 *
 * @author samuelzea
 */
class PeriodosTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM periodos p ...
    protected $table = "periodos";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getPeriodoActivo()
    {
        $sql = $this->getSql();
        $select = $sql->select();
        $select->where(['estado' => 'activo']);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        
        $result = [];
        foreach ($resultSet as $row) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtener el último período creado (independientemente del estado)
     */
    public function getUltimoPeriodo()
    {
        $sql = $this->getSql();
        $select = $sql->select();
        $select->where(['estado != ?' => 'eliminado'])
            ->order(['fecha_creacion DESC', 'id_periodo DESC'])
            ->limit(1);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        
        $result = $resultSet->current();
        return $result ? $result : null;
    }

    public function getAllPeriodos()
    {
        $sql = $this->getSql();
        $select = $sql->select();
        $select->where(['estado != ?' => 'eliminado'])
            ->order(['fecha_creacion' => 'DESC']);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        
        $result = [];
        foreach ($resultSet as $row) {
            $result[] = $row;
        }
        return $result;
    }

    public function createPeriodo($data)
    {
        // Desactivar período anterior
        $sqlUpdate = $this->getSql();
        $update = $sqlUpdate->update();
        $update->set(['estado' => 'inactivo']);
        $update->where(['estado' => 'activo']);
        
        $statement = $sqlUpdate->prepareStatementForSqlObject($update);
        $statement->execute();
        
        // Crear nuevo período
        $sqlInsert = $this->getSql();
        $insert = $sqlInsert->insert();
        $insert->values($data);
        
        $statement = $sqlInsert->prepareStatementForSqlObject($insert);
        return $statement->execute();
    }

    public function updatePeriodo($id, $data)
    {
        $sql = $this->getSql();
        $update = $sql->update();
        $update->set($data);
        $update->where(['id_periodo' => $id]);
        
        $statement = $sql->prepareStatementForSqlObject($update);
        return $statement->execute();
    }

    public function extenderPeriodo($id, $nuevaFechaFin)
    {
        $data = [
            'fecha_fin' => $nuevaFechaFin,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ];
        
        return $this->updatePeriodo($id, $data);
    }

    public function cerrarPeriodo($id)
    {
        $data = [
            'estado' => 'cerrado',
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ];
        
        return $this->updatePeriodo($id, $data);
    }

    public function isPeriodoActivo()
    {
        $periodo = $this->getPeriodoActivo();
        if (empty($periodo)) {
            return false;
        }

        $periodo = $periodo[0];
        $now = new \DateTime();
        $inicio = new \DateTime($periodo['fecha_inicio'] . ' ' . $periodo['hora_inicio']);
        $fin = new \DateTime($periodo['fecha_fin'] . ' ' . $periodo['hora_fin']);

        return ($now >= $inicio && $now <= $fin);
    }

    public function getPeriodoByFecha($fecha)
    {
        $sql = $this->getSql();
        $select = $sql->select();
        $select->where([
            'fecha_inicio <= ?' => $fecha,
            'fecha_fin >= ?' => $fecha
        ]);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        
        $result = [];
        foreach ($resultSet as $row) {
            $result[] = $row;
        }
        return $result;
    }

    public function marcarComoEliminado($idPeriodo)
    {
        $sql = $this->getSql();
        $update = $sql->update();
        $update->set(['estado' => 'eliminado'])
            ->where(['id_periodo' => $idPeriodo]);
        
        $statement = $sql->prepareStatementForSqlObject($update);
        return $statement->execute();
    }
}