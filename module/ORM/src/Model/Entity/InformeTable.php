<?php

namespace ORM\Model\Entity;

class InformeTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    protected $table = "informes";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    /**
     * Obtener todos los informes (método original)
     */
    public function getInformes() {
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at', 'id_periodo'])
               ->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre'])
               ->join(['p' => 'periodos'], 'informes.id_periodo = p.id_periodo', ['nombre_periodo' => 'nombre'], 'left');
        $data = $this->selectWith($select)->toArray();

        return $data;
    }

    /**
     * Obtener informes por usuario (método original - mantenido para compatibilidad)
     */
    public function getInformesByUser($id_usuario){
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at', 'id_periodo'])
               ->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre'])
               ->join(['p' => 'periodos'], 'informes.id_periodo = p.id_periodo', ['nombre_periodo' => 'nombre'], 'left');
        $select->where->equalTo("informes.id_usuario", $id_usuario);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener informes por usuario y período específico
     */
    public function getInformesByUserPeriodo($id_usuario, $periodo_id){
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at', 'id_periodo'])
               ->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre'])
               ->join(['p' => 'periodos'], 'informes.id_periodo = p.id_periodo', ['nombre_periodo' => 'nombre'], 'left');
        $select->where([
            'informes.id_usuario' => $id_usuario,
            'informes.id_periodo' => $periodo_id
        ]);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener todos los informes de un período específico
     */
    public function getInformesByPeriodo($periodo_id){
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at', 'id_periodo'])
               ->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre'])
               ->join(['p' => 'periodos'], 'informes.id_periodo = p.id_periodo', ['nombre_periodo' => 'nombre'], 'left');
        $select->where(['informes.id_periodo' => $periodo_id]);
        $select->order('informes.created_at DESC');
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    /**
     * Obtener informes del período activo para un usuario específico
     */
    public function getInformesByUserPeriodoActual($id_usuario) {
        $select = $this->getSql()->select();
        $select->columns(['id_informe','url_informe', 'created_at', 'updated_at', 'id_periodo'])
               ->join(['l' => 'usuario'], 'informes.id_usuario = l.usuario', ['nombre'])
               ->join(['p' => 'periodos'], 'informes.id_periodo = p.id_periodo', ['nombre_periodo' => 'nombre'], 'left');
        $select->where([
            'informes.id_usuario' => $id_usuario,
            'p.estado' => 'activo'
        ]);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }
}