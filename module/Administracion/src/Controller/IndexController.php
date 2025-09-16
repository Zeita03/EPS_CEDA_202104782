<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

namespace Administracion\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends \Utilidades\BaseAbstract\Controller\BaseAbstractActionController {

    private $authService;
    private $adapter;
    private $lastGeneratedValue;
    private $acceso;

    function __construct($authService, $adapter) {
        $this->authService = $authService;
        $this->adapter = $adapter;
    }

    /**
     * Este método se ejecuta antes que las acciones, en este lugar se valida
     * la sessión del usuario y los permisos que tiene sobre el action a ejecutar
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e) {
        //verificar que el usuario tenga permisos de acceso al sitio, sino redireccionar al login..
        if ($this->authService->hasIdentity() && $this->authService->getIdentity() instanceof \Auth\Model\AuthEntity && $this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            //inyectando la información del usuario autenticado
            $this->layout()->setTemplate('layout/layoutAdmon');
            $this->layout()->setVariable('userAuth', $this->authService->getIdentity());
        } else {
            return $this->redirect()->toRoute('home');
        }

        //extraer la información de la ruta de acceso del usuario
        $routeMatch = $e->getRouteMatch();
        $nombreControlador = $routeMatch->getParam('controller');
        $nombreAccion = $routeMatch->getParam('action');
        //verificar si tiene el permiso para ingresar a esta acción...
        $authManager = new \Auth\Service\AuthManager($this->authService, $this->adapter);
        if ($nombreAccion != 'accesoDenegado' &&  $nombreAccion != 'periodoInactivo' && $nombreAccion != 'perfil' && $nombreAccion != 'premios' && $nombreAccion !='formacionAcademica' && $nombreAccion !='cargos' && $nombreAccion !='investigaciones' && $nombreAccion !='capacitacionProfesional' && $nombreAccion != 'solicitudes' && $nombreAccion != 'misSolicitudes' &&  $nombreAccion != 'configuracion' && $nombreAccion != 'reportes' && $nombreAccion != 'admSolicitudes' && $authManager->verificarPermiso($nombreControlador, $nombreAccion) != true) {
            return $this->redirect()->toRoute("administracionHome/administracion", ["action" => "accesoDenegado"]);
        }
        $this->acceso = $authManager->acceso;

        //agregar mensajes pendientes...
        $textoMensaje = $this->getMensajePendiente();
        if (!empty($textoMensaje)) {
            if ($textoMensaje[0] == 1) {
                $this->flashMessenger()->addSuccessMessage($textoMensaje[1]);
            } else {
                $this->flashMessenger()->addErrorMessage($textoMensaje[1]);
            }
        }

        //extraer permisos y construir la variable de menu...
        $usuarioTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        $menuData = $usuarioTable->getMenuUsuario($this->authService->getIdentity()->getId());
        $this->layout()->setVariable('menuData', $menuData);

        return parent::onDispatch($e);
    }

    public function saveLog($id_usuario, $accion){
        $bitacoraTable = new \ORM\Model\Entity\BitacoraTable($this->adapter);
        $params = array( "id_usuario" => $id_usuario,
                "accion" => $accion );
        $result = $bitacoraTable->insert($params);
        return;
    }

    public function accesoDenegadoAction() {
        return new ViewModel([]);
    }

    public function periodoInactivoAction() {
        return new ViewModel([]);
    }

    
    public function perfilAction() {
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        //actualizar información...
        if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            if (!empty($params["passwd"]) || !empty($params["passwd2"])) {
                if ($params["passwd"] != $params["passwd2"]) {
                    $this->flashMessenger()->addErrorMessage('Las contraseñas no coinciden.');
                    return new ViewModel(["data" => $this->authService->getIdentity()->getData()]);
                } else {
                    $bcrypt = new \Laminas\Crypt\Password\Bcrypt();
                    $params["passwd"] = $bcrypt->create($params["passwd"]);
                }
            } else {
                unset($params["passwd"]);
            }
            unset($params["passwd2"]);
            $usuarioTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
            $result = $usuarioTable->update($params, ["usuario" => $this->authService->getIdentity()->getData()["usuario"]]);
            if ($result > 0) {
                $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Edito la información de su perfil');
                $this->flashMessenger()->addSuccessMessage('Información actualizada con éxito');
                $data = $usuarioTable->select(["usuario" => $this->authService->getIdentity()->getData()["usuario"]])->toArray();
                if (!empty($data)) {
                    $this->authService->getIdentity()->setData($data[0]);
                }
            } else {
                $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar actualizar la información.');
            }
        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData()]);
    }

    public function indexAction() {
        return $this->redirect()->toRoute('administracionHome/administracion', ["dashboard"]);
    }

    public function dashboardAction() {
        return new ViewModel([]);
    }

    public function usuariosAction() {
        $adminTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        $roleTable = new \ORM\Model\Entity\RolTable($this->adapter);
        $tableUsuarioRol = new \ORM\Model\Entity\UsuarioRolTable($this->adapter);
        //operaciones
        if ($this->params()->fromPost("action") == "eliminar") {
            try {
                //$result = $adminTable->delete(["usuario" => $this->params()->fromPost("id")]);

                $result = $adminTable->update(array('id_estado' => '0'), ["usuario" => $this->params()->fromPost("id")]);
                
                if ($result > 0) {
                    $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Desactivo al usuario con id '. $this->params()->fromPost("id"));
                    $this->flashMessenger()->addSuccessMessage('Usuario inhabilitado con éxito.');
                } else {
                    $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
                }
            } catch (\Exception $exc) {
                $this->flashMessenger()->addErrorMessage('No es posible eliminar el registro porque existe información relacionada.');
            }
        } else if ($this->params()->fromPost("action") == "activaruser") {
            try {
                //code...
                $result = $adminTable->update(array('id_estado' => '1'), ["usuario" => $this->params()->fromPost("id")]);
                
                if ($result > 0) {
                    $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Activo al usuario con id '. $this->params()->fromPost("id"));
                    $this->flashMessenger()->addSuccessMessage('Usuario activado con éxito.');
                } else {
                    $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
                }
            } catch (\Exception $exc) {
                $this->flashMessenger()->addErrorMessage('No fue posible activar al usuario.');
            }

        } else if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            //var_dump($params);
            if (isset($params["action"]) && $params["action"] == "updateRoles") {
                $arrayDetalle = [];
                /*foreach ($params["role"] as $key => $value) {
                    
                    $arrayDetalle[] = $key;
                }*/
                $arrayDetalle[] = $params["role"];
                $result = $tableUsuarioRol->actualiarAsociados($params["usuario"], $arrayDetalle, $this->authService->getIdentity()->getData()["usuario"]);
                $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Se actualizo el rol del usuario: '. $params["usuario"] . ' a rol ' . $params['role']);
                
            } else {
                if (empty($params["passwd"])) {
                    unset($params["passwd"]);
                } else {
                    $bcrypt = new \Laminas\Crypt\Password\Bcrypt();
                    $params["passwd"] = $bcrypt->create($params["passwd"]);
                }
                if (empty($params["usuario"])) {
                    $params["usuario"] = null;
                }
                //verificar si ya existe...
                $id = $params["usuario"];
                $mail = $params["email"];
                unset($params["usuario"]);
                if ($adminTable->select(["usuario" => $id])->count() > 0) {
                    $result = $adminTable->update($params, ["usuario" => $id]);
                    $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Actualizo la información del usuario con id:'. $this->params()->fromPost("id"));
                
                } else {
                    if($adminTable->select(["email" => $mail])->count() > 0){
                        $result = 0;
            
                    }else{
                        $result = $adminTable->insert($params);
                        $this->saveLog($this->authService->getIdentity()->getData()["usuario"], 'Se agrego un nuevo usuario: '. $this->params()->fromPost("email"));
                    }
                }
            }
            if ($result > 0) {
                $this->flashMessenger()->addSuccessMessage('Información actualizada con éxito.');
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
        }
        $data = $adminTable->getAdmons();
        $dataRoles = $roleTable->getAll();
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["roles"] = $tableUsuarioRol->getArray($data[$i]["usuario"]);
        }
        return new ViewModel(["data" => $data, "dataRoles" => $dataRoles, "acceso" => $this->acceso]);
    }

    public function rolesAction() {
        $table = new \ORM\Model\Entity\RolTable($this->adapter);
        //operaciones
        if ($this->params()->fromPost("action") == "eliminar") {
            try {
                $result = $table->delete(["rol" => $this->params()->fromPost("id")]);
                if ($result > 0) {
                    $this->flashMessenger()->addSuccessMessage('Información eliminada con éxito.');
                } else {
                    $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
                }
            } catch (\Exception $exc) {
                $this->flashMessenger()->addErrorMessage('No es posible eliminar el registro porque existe información relacionada.');
            }
        } else if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            //verificar si ya existe...
            $id = $params["rol"];
            if ($table->select(["rol" => $id])->count() > 0) {
                $result = $table->update($params, ["rol" => $id]);
            } else {
                $result = $table->insert($params);
            }
            if ($result > 0) {
                $this->flashMessenger()->addSuccessMessage('Información actualizada con éxito.');
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
        }
        $data = $table->getAll();
        return new ViewModel(["data" => $data, "acceso" => $this->acceso]);
    }

    public function rolpermisosAction() {
        $rol = $this->params()->fromRoute('val1');
        $table = new \ORM\Model\Entity\RolPermisoTable($this->adapter);
        $tablePermiso = new \ORM\Model\Entity\PermisoTable($this->adapter);
        //operaciones
        if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            $arrayKeysPermisos = array_keys($params["check0"]);
            $table->delete(["rol" => $rol]);
            foreach ($arrayKeysPermisos as $permiso) {
                $acceso = "00000000";
                for ($i = 1; $i < 8; $i++) {
                    if (isset($params["check" . $i][$permiso])) {
                        $acceso[$i - 1] = "1";
                    }
                }
                $table->insert(["rol" => $rol, "permiso" => $permiso, "acceso" => $acceso]);
            }
            $this->flashMessenger()->addSuccessMessage('Información actualizada con éxito.');
            //extraer permisos y construir la variable de menu...
            $usuarioTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
            $menuData = $usuarioTable->getMenuUsuario($this->authService->getIdentity()->getId());
            $this->layout()->setVariable('menuData', $menuData);
        }
        $data = $table->getAllPermisosIndexados($rol);
        $dataPermisos = $tablePermiso->getOrdenadosAgrupados();
        return new ViewModel(["data" => $data, "dataPermisos" => $dataPermisos, "acceso" => $this->acceso]);
    }


}
