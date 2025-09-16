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
class UsuariaTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "usuarias";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }
    
    public function getAll() {
        $select = $this->getSql()->select();
        //$select->columns(["usuaria", "nombre", "email"]);
        return $this->selectWith($select)->toArray();
    }
    
    public function getUsuariaByHashId($val) {
        $select = $this->getSql()->select();
        $select->where(["hash_id" => $val]);
        return $this->selectWith($select)->toArray();
    }

    public function getPermisosParaAccion($id, $controlador, $accion) {
        $select = $this->getSql()->select()->columns([]);
        $select->join(["ur" => "usuario_rol"], "ur.usuario = usuario.usuario", [])
                ->join(["rp" => "rol_permiso"], "rp.rol = ur.rol")
                ->join(["p" => "permiso"], "p.permiso = rp.permiso", [])
        ;
        $select->where->equalTo("usuario.email", $id)
        ->AND->equalTo("p.controller", $controlador)
        ->AND->equalTo("p.action", $accion)
        ;
        $data = $this->selectWith($select)->toArray();
        //Si no hay tuplas, retornar false para que se vaya a acceso denegado...
        if (empty($data)) {
            return false;
        }
        $acceso = "00000000";
        //hacer un OR con todos los accesos (para herencia de permisos)
        foreach ($data as $accesoTmp) {
            //se tiene 8 posibilidades aunque no se usen todas..
            for ($i = 0; $i < 8; $i++) {
                //si el acceso de base de datos tiene el largo necesario para comparar.. se compara.. sino el array queda como estaba...
                if ($i < strlen($accesoTmp["acceso"])) {
                    //si alguno de los dos es 1, dejar 1, sino es 0
                    $acceso[$i] = ($acceso[$i] == "1" || $accesoTmp["acceso"][$i] == "1") ? "1" : "0";
                }
            }
        }
        return $acceso;
    }
}
