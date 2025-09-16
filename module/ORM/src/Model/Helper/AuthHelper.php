<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ORM\Model\Helper;

/**
 * Description of AuthHelper
 *
 * @author tobias
 */
class AuthHelper {

    private $adapter;

    function __construct($adapter) {
        $this->adapter = $adapter;
    }

    public function autenticar($data) {
        $table = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        if ($table) {
            //obtener el usuario...
            //Se agrego la validacion de estado
            $dataUsuario = $table->select(["email" => $data["id"], "id_estado" => "1"])->toArray();
            if (!empty($dataUsuario)) {
                $bcrypt = new \Laminas\Crypt\Password\Bcrypt();
                if ($bcrypt->verify($data["passwd"], $dataUsuario[0]["passwd"])) {
                    //contraseña correcta
                    unset($dataUsuario[0]["passwd"]);
                    //obtener los roles del usuario...
                    $rolTable = new \ORM\Model\Entity\RolTable($this->adapter);
                    $roles = [];
                    $dataRoles = $rolTable->getRolesUsuario($dataUsuario[0]["usuario"]);
                    foreach ($dataRoles as $o) {
                        $roles[] = $o["rol"];
                    }
                    $dataUsuario[0]["roles"] = $roles;
                    return ["error" => false, "auth" => true, "data" => $dataUsuario[0]];
                }
            }
        }
        return ["error" => false, "auth" => false, "data" => "Hubo un error al iniciar sesión, revise sus credenciales o asegurese tener una cuenta activa"];
    }

}
