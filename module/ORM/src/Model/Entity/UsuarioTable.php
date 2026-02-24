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
class UsuarioTable extends \Laminas\Db\TableGateway\AbstractTableGateway {

    // generar el alias para la tabla: SELECT * FROM user u ...
    protected $table = "usuario";
    protected $adapter;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        if (!$this->isInitialized()) {
            $this->initialize();
        }
    }
    
    public function getAdmons() {
        $select = $this->getSql()->select();
        $select->columns(["usuario", "nombre", "email", "dpi", "registro_docente", "grado_academico" , "id_estado", "phone", "direccion"]);
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
        //var_dump($data);
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

    public function getMenuUsuario($id) {
        $select = $this->getSql()->select()->columns([]);
        $select->join(["ur" => "usuario_rol"], "ur.usuario = usuario.usuario", [])
                ->join(["rp" => "rol_permiso"], "rp.rol = ur.rol", [])
                ->join(["p" => "permiso"], "p.permiso = rp.permiso", ["menu", "action", "prioridad", "nombre", "controller"])
                ;
        $select->where->equalTo("usuario.email", $id)
                ->greaterThan("prioridad", 0)
                ->equalTo("mostrar", 1)
                ;
        $select->order(["prioridad" => "asc"]);
        $select->group(["controller", "action"]);
        
        $data = $this->selectWith($select)->toArray();
                
        $menuData = [];
        
        foreach ($data as $tmp) {
            $menuArray = explode("|", $tmp["menu"]);
            if (!isset($menuData[$menuArray[0]])) {
                $menuData[$menuArray[0]] = [];
            }
            $tmp["menu"] = $menuArray[1];
            //obtener la base de la URL
            $paquete = strtolower(explode("\\",$tmp["controller"])[0]);
            $tmp["route"] = $paquete . "Home/" . $paquete;
            if(isset($menuArray[2])) {
                $tmp['icon'] = $menuArray[2];
            } else {
                $tmp['icon'] = 'fa-bars';
            }
            //var_dump($tmp["route"], $tmp["controller"]); die;
            $menuData[$menuArray[0]][] = $tmp;
        }
                        
        return $menuData;
    }

    public function getUserByEmail($email){
        $select = $this->getSql()->select();
        $select->where->equalTo("email", $email);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }

    public function getUserById($id){
        $select = $this->getSql()->select();
        $select->where->equalTo("usuario", $id);
        $data = $this->selectWith($select)->toArray();
        return $data;
    }



    public function getReporte1and2($id_periodo = null) {
        $select = $this->getSql()->select();
        //$select->columns(["usuario", "nombre", "email", "dpi", "registro_docente", "grado_academico" , "id_estado"]);
        $select->columns(["usuario", "nombre", "email", "dpi", "registro_docente", "grado_academico" , "id_estado"])->join(['p' => 'puntos'], 'p.id_usuario = usuario.usuario', ['premios', 'investigaciones', 'formacion_academica', 'cargos',  'capacitacion_profesional', 'year', 'id_periodo']);
        $select->where->equalTo("usuario.id_estado", 1);
        
        if ($id_periodo) {
            $select->where->equalTo("p.id_periodo", $id_periodo);
        }
        
        $data = $this->selectWith($select)->toArray();

        $dataReporte1 = [];

        foreach ($data as $tmp) {
            $puntosTotales = floatval($tmp["capacitacion_profesional"]) + floatval($tmp["formacion_academica"]) + floatval($tmp["premios"]) + floatval($tmp["investigaciones"]) + floatval($tmp["cargos"]);
            $tmp['puntos'] = $puntosTotales;
            $dataReporte1[] = $tmp;

        }
        return $dataReporte1;
    }


}
