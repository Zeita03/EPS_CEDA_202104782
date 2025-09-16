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
class UsuarioRolTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "usuario_rol";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }

    public function getArray($id) {
        $select = $this->getSql()->select();
        $select->columns(["rol"]);
        $select->where->equalTo("usuario", $id);
        $data = $this->selectWith($select)->toArray();
        $arrayFinal = [];
        foreach ($data as $tmp) {
            $arrayFinal[] = $tmp["rol"];
        }
        return $arrayFinal;
    }

    public function actualiarAsociados($id, $arrayDetalle, $usuario) {
        $delete = $this->getSql()->delete();
        $delete->where->equalTo("usuario", $id)
                ->notIn("rol", $arrayDetalle);
        $alteraciones = $this->deleteWith($delete);

        foreach ($arrayDetalle as $tmp) {
            $dataExiste = $this->select(["usuario" => $id, "rol" => $tmp]);
            if ($dataExiste->count() > 0) {
                $alteraciones += $this->update([
                    "updated_at" => new \Laminas\Db\Sql\Expression('NOW()'),
                        ], ["usuario" => $id, "rol" => $tmp]);
            } else {
                $alteraciones += $this->insert(["usuario" => $id,
                    "rol" => $tmp,
                ]);
            }
        }
        return $alteraciones;
    }

}
