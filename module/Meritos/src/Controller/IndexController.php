<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

namespace Meritos\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use \Datetime;
use \DateTimeZone;

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
        
        if($nombreAccion == 'premios' || $nombreAccion == 'formacionAcademica' || $nombreAccion == 'cargos' || $nombreAccion == 'investigaciones' || $nombreAccion == 'capacitacionProfesional'){
            
            // Verificar si hay un período activo
            $periodoActivo = $this->obtenerPeriodoActivo();
            
            if (!$periodoActivo) {
                // No hay período activo, redirigir a la vista de período inactivo
                return $this->redirect()->toRoute("administracionHome/administracion", ["action" => "periodoInactivo"]);
            }
            
            // Si hay período activo, verificar que no haya expirado (verificación adicional por si acaso)
            date_default_timezone_set('America/Guatemala');
            $fechaHoraActual = new DateTime("now");
            $fechaExpiracion = new DateTime($periodoActivo['fecha_fin'] . " " . $periodoActivo['hora_fin']);
            
            if ($fechaHoraActual > $fechaExpiracion) {
                // El período ya expiró, redirigir a período inactivo
                return $this->redirect()->toRoute("administracionHome/administracion", ["action" => "periodoInactivo"]);
            }
        }

        if ($nombreAccion != 'accesoDenegado' && $nombreAccion != 'periodoInactivo' && $nombreAccion != 'premios' && $nombreAccion !='formacionAcademica' && $nombreAccion !='cargos' && $nombreAccion !='investigaciones' && $nombreAccion !='capacitacionProfesional' && $nombreAccion != 'solicitudes' && $nombreAccion != 'misSolicitudes' &&  $nombreAccion != 'configuracion' && $nombreAccion != 'reportes' && $nombreAccion != 'admSolicitudes' && $nombreAccion !='displayfile' && $authManager->verificarPermiso($nombreControlador, $nombreAccion) != true) {
            return $this->redirect()->toRoute("administracionHome/administracion", ["action" => "accesoDenegado"]);
        }

        if(!$authManager->verificarPermiso($nombreControlador, $nombreAccion)){
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



    public function displayfileAction(){
        $name_file = $this->params()->fromRoute('val1',0);
        $name = $name_file . ".pdf";
        //$pdf_file = "./archivos/" . $name;


        $file = getcwd() . "/archivos/" . $name;

        //$content = file_get_contents($pdf_file);
        header('Cache-Control: public' );
        header('Content-Description: File Transfer' );
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Content-type: application/pdf' );
        header('Content-Transfer-Encoding: binary' );
        header('Content-Length: ' . filesize($file));
        header('Accept-Ranges: bytes');
        @readfile($file);
        die;
    }


    public function sendEmail($email,$nameUser, $estado){
        $mailManager = new \Utilidades\Service\MailManager();

        $htmlMail = ('<!DOCTYPE html>
        <html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
        <head>
            <title></title>
            <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
            <meta content="width=device-width,initial-scale=1" name="viewport" />
            <link href="https://fonts.googleapis.com/css?family=Abril+Fatface" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Alegreya" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Arvo" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Bitter" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css" /><!--<![endif]-->
            <style>
                * {
                    box-sizing: border-box
                }

                body {
                    margin: 0;
                    padding: 0
                }

                a[x-apple-data-detectors] {
                    color: inherit !important;
                    text-decoration: inherit !important
                }

                #MessageViewBody a {
                    color: inherit;
                    text-decoration: none
                }

                p {
                    line-height: inherit
                }

                .desktop_hide,
                .desktop_hide table {
                    mso-hide: all;
                    display: none;
                    max-height: 0;
                    overflow: hidden
                }

                @media (max-width:520px) {
                    .desktop_hide table.icons-inner {
                        display: inline-block !important
                    }

                    .icons-inner {
                        text-align: center
                    }

                    .icons-inner td {
                        margin: 0 auto
                    }

                    .row-content {
                        width: 100% !important
                    }

                    .mobile_hide {
                        display: none
                    }

                    .stack .column {
                        width: 100%;
                        display: block
                    }

                    .mobile_hide {
                        min-height: 0;
                        max-height: 0;
                        max-width: 0;
                        overflow: hidden;
                        font-size: 0
                    }

                    .desktop_hide,
                    .desktop_hide table {
                        display: table !important;
                        max-height: none !important
                    }

                    .row-2 .column-1 .block-2.heading_block td.pad {
                        padding: 0 20px 20px !important
                    }
                }
            </style>
        </head>

        <body style="background-color:#fff;margin:0;padding:0;-webkit-text-size-adjust:none;text-size-adjust:none">
            <table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation"
                style="mso-table-lspace:0;mso-table-rspace:0;background-color:#fff" width="100%">
                <tbody>
                    <tr>
                        <td>
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1"
                                role="presentation" style="mso-table-lspace:0;mso-table-rspace:0;background:transparent linear-gradient(180deg, #003470 0%, #041d3c 100%) 0% 0% no-repeat padding-box"
                                width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;color:#000;width:500px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td class="column column-1"
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding-top:0;padding-bottom:5px;border-top:0;border-right:0;border-bottom:0;border-left:0"
                                                            width="100%">
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="image_block block-1" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0" width="100%">
                                                                <tr>
                                                                    <td class="pad"
                                                                        style="padding-bottom:30px;padding-top:20px;width:100%;padding-right:0;padding-left:0">
                                                                        <div align="center" class="alignment"
                                                                            style="line-height:10px"><img
                                                                                src="https://farusac.edu.gt/wp-content/uploads/2022/10/headerfarusaclogos.png"
                                                                                style="display:block;height:auto;border:0;width:410px;max-width:100%"
                                                                                width="410" /></div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2"
                                role="presentation" style="mso-table-lspace:0;mso-table-rspace:0;background-color:#f2f2f2"
                                width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;background-color:#f2f2f2;border-radius:40px 0;color:#000;width:500px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td class="column column-1"
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding-top:15px;padding-bottom:20px;border-top:0;border-right:0;border-bottom:0;border-left:0"
                                                            width="100%">
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="image_block block-1" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0" width="100%">
                                    
                                                            </table>
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="heading_block block-2" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0" width="100%">
                                                                <tr>
                                                                    <td class="pad"
                                                                        style="padding-bottom:20px;text-align:center;width:100%">
                                                                        <h1
                                                                            style="margin:0;color:#041d3c;direction:ltr;font-family:Nunito,Arial,Helvetica Neue,Helvetica,sans-serif;font-size:25px;font-weight:400;letter-spacing:normal;line-height:120%;text-align:center;margin-top:0;margin-bottom:0">
                                                                            <span class="tinyMce-placeholder">Estado de la solicitud</span>
                                                                        </h1>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="text_block block-3" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0;word-break:break-word"
                                                                width="100%">
                                                                <tr>
                                                                    <td class="pad"
                                                                        style="padding-bottom:20px;padding-left:20px;padding-right:20px;padding-top:10px">
                                                                        <div style="font-family:sans-serif">
                                                                            <div class="txtTinyMce-wrapper"
                                                                                style="font-size:12px;font-family:Nunito,Arial,Helvetica Neue,Helvetica,sans-serif;mso-line-height-alt:18px;color:#393d47;line-height:1.5">
                                                                                <p style="margin:0;font-size:16px"><span
                                                                                        style="font-size:16px;">Hola ' . $nameUser . ':</span></p>
                                                                                <p
                                                                                    style="margin:0;font-size:16px;mso-line-height-alt:18px">
                                                                                     </p>
                                                                                <p style="margin:0;font-size:16px">
                                                                                    Le informamos que su solicitud de mérito academico ha sido '. $estado . '.
                                                                                    
                                                                                </p>

                                                                                <p style="margin:0;font-size:16px">
                                                                                    Cualquier inconveniente no dudes en contactarnos.
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                      
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="text_block block-5" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0;word-break:break-word"
                                                                width="100%">
                                                                <tr>
                                                                    <td class="pad"
                                                                        style="padding-bottom:20px;padding-left:20px;padding-right:20px;padding-top:10px">
                                                                        <div style="font-family:sans-serif">
                                                                            <div class="txtTinyMce-wrapper"
                                                                                style="font-size:12px;font-family:Nunito,Arial,Helvetica Neue,Helvetica,sans-serif;mso-line-height-alt:14.399999999999999px;color:#393d47;line-height:1.2">
                                                                                <p style="margin:0;font-size:16px"><span
                                                                                        style="font-size:14px;">* Nota: este correo electrónico se envió desde una dirección de correo electrónico que no acepta correo entrante. No responda a este mensaje. </span></p>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-3"
                                role="presentation" style="mso-table-lspace:0;mso-table-rspace:0;background:transparent linear-gradient(180deg, #003470 0%, #041d3c 100%) 0% 0% no-repeat padding-box"
                                width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;color:#000;width:500px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td class="column column-1"
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding-top:5px;padding-bottom:5px;border-top:0;border-right:0;border-bottom:0;border-left:0"
                                                            width="100%">
                                                            <table border="0" cellpadding="15" cellspacing="0"
                                                                class="text_block block-1" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0;word-break:break-word"
                                                                width="100%">
                                                                <tr>
                                                                    <td class="pad">
                                                                        <div style="font-family:sans-serif">
                                                                            <div class="txtTinyMce-wrapper"
                                                                                style="font-size:12px;font-family:Nunito,Arial,Helvetica Neue,Helvetica,sans-serif;text-align:center;mso-line-height-alt:18px;color:#fff;line-height:1.5">
                                                                                <span style="font-size:16px;"> </span></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-4"
                                role="presentation" style="mso-table-lspace:0;mso-table-rspace:0" width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;color:#000;width:500px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td class="column column-1"
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding-top:5px;padding-bottom:5px;border-top:0;border-right:0;border-bottom:0;border-left:0"
                                                            width="100%">
                                                            <table border="0" cellpadding="0" cellspacing="0"
                                                                class="icons_block block-1" role="presentation"
                                                                style="mso-table-lspace:0;mso-table-rspace:0" width="100%">
                                                                <tr>
                                                                    <td class="pad"
                                                                        style="vertical-align:middle;color:#9d9d9d;font-family:inherit;font-size:15px;padding-bottom:5px;padding-top:5px;text-align:center">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                            role="presentation"
                                                                            style="mso-table-lspace:0;mso-table-rspace:0"
                                                                            width="100%">
                                                                            <tr>
                                                                                <td class="alignment"
                                                                                    style="vertical-align:middle;text-align:center">
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table><!-- End -->
        </body>

        </html>');
    
        $mailManager->sendGeneralMessage($email, "CEDA - Notificación estado solicitud", $htmlMail);
    }

    public function saveLog($id_usuario, $accion){
        $bitacoraTable = new \ORM\Model\Entity\BitacoraTable($this->adapter);
        $params = array( "id_usuario" => $id_usuario,
                "accion" => $accion );
        $result = $bitacoraTable->insert($params);
        return;
    }



    public function premiosAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());
        
        // Obtenemos el periodo activo para validaciones
        $periodoActivo = $this->obtenerPeriodoActivo();
   
        $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $idSolicitud = $this->params()->fromRoute('val2',0);
        
        if ($this->getRequest()->isPost()) {

            $params = $this->params()->fromPost();
    
            $file_name = $_FILES['subir_archivo']['name'];
            
            if($this->params()->fromPost("_method") == "put"){
                unset($params['_method']);

                if($file_name){
                    $encripted_name = $fileManager->uploadFile($file_name);
                    //No se logro subir el archivo al servidor
                    if(!$encripted_name){
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                        return;
                    }

                    $key = "url_file";
                    $params[$key] = $encripted_name;
                    
                }

                $result = $premiosTable->update($params, ["id_premio" => $idSolicitud]);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se edito la solicitud de premios con id '. $idSolicitud);
                    $this->flashMessenger()->addSuccessMessage('Solicitud editada correctamente');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }


            }else{
                $encripted_name = $fileManager->uploadFile($file_name);
                //No se logro subir el archivo al servidor
                if(!$encripted_name){
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                    return;
                }
    
                $key = "url_file";
                $params[$key] = $encripted_name;
    
                $result = $premiosTable->insert($params);
        
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se creo una nueva solicitud de la categoria: premios');
                    $this->flashMessenger()->addSuccessMessage('Solicitud creada con exito');
                    
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.'); 
                }
            }
        }


        if($idSolicitud == 0){
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "periodoActivo" => $periodoActivo
            ]);
        }else{
            $solicitud = $premiosTable->getPremiosById($id_usuario, $idSolicitud);
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "dataedit" => $solicitud, 
                "periodoActivo" => $periodoActivo
            ]);
        }
        
    }


    public function formacionAcademicaAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Obtenemos el periodo activo para validaciones
        $periodoActivo = $this->obtenerPeriodoActivo();

        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $idSolicitud = $this->params()->fromRoute('val2',0);

        if ($this->getRequest()->isPost()) {

            $params = $this->params()->fromPost();
            $file_name = $_FILES['subir_archivo']['name'];

            if($this->params()->fromPost("_method") == "put"){
                unset($params['_method']);

                if($file_name){
                    $encripted_name = $fileManager->uploadFile($file_name);
                    //No se logro subir el archivo al servidor
                    if(!$encripted_name){
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                        return;
                    }
                    $key = "url_constancia";
                    $params[$key] = $encripted_name; 
                }

                if($params['categoria'] == 'graduado'){
                    $params['anio_graduacion'] = $params['fecha_obtencion'];
                }else if($params['categoria'] == 'pensum'){
                    $params['anio_cierre_pensum'] = $params['fecha_obtencion'];
                }
        
                $puntos = floatval($params["puntos"]);
                $params["puntos"] = $puntos;
    
                unset($params['categoria']);
                unset($params['fecha_obtencion']);

                $result = $formacionTable->update($params, ["id_formacion_academica" => $idSolicitud]);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se edito la solicitud de formación academica con id '. $idSolicitud);
                    $this->flashMessenger()->addSuccessMessage('Solicitud editada correctamente');
                } else {
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }

            }else{
                $encripted_name = $fileManager->uploadFile($file_name);
                //No se logro subir el archivo al servidor
                if(!$encripted_name){
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                    return;
                }
                
    
                if($params['categoria'] == 'graduado'){
                    $params['anio_graduacion'] = $params['fecha_obtencion'];
                }else if($params['categoria'] == 'pensum'){
                    $params['anio_cierre_pensum'] = $params['fecha_obtencion'];
                }
    
                $key = "url_constancia";
                $params[$key] = $encripted_name;
    
                $puntos = floatval($params["puntos"]);
                $params["puntos"] = $puntos;
    
                unset($params['categoria']);
                unset($params['fecha_obtencion']);
    
                $result = $formacionTable->insert($params);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se creo una nueva solicitud de la categoria de formación académica');
                    $this->flashMessenger()->addSuccessMessage('Solicitud creada con exito');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                }
                
            } 

        }

        if($idSolicitud == 0){
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "periodoActivo" => $periodoActivo
            ]);
        }else{
            $solicitud = $formacionTable->getFormacionAcademicaById($id_usuario, $idSolicitud);
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "dataedit" => $solicitud, 
                "periodoActivo" => $periodoActivo
            ]);
        }
    }

    public function cargosAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Obtenemos el periodo activo para validaciones
        $periodoActivo = $this->obtenerPeriodoActivo();

        $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $idSolicitud = $this->params()->fromRoute('val2',0);

        if ($this->getRequest()->isPost()) {

            $params = $this->params()->fromPost();
            $file_name = $_FILES['subir_archivo']['name'];

            if($this->params()->fromPost("_method") == "put"){
                unset($params['_method']);

                try {
                    if($file_name){
                        $encripted_name = $fileManager->uploadFile($file_name);
                        //No se logro subir el archivo al servidor
                        if(!$encripted_name){
                            $this->saveLog($id_usuario, 'Error al editar la solicitud');
                            $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.');
                            return;
                        }
                        $key = "url_constancia";
                        $params[$key] = $encripted_name; 
                    }
    
                    $result = $cargosTable->update($params, ["id_cargo" => $idSolicitud]);
                    if ($result->getAffectedRows() > 0) {
                        $this->saveLog($id_usuario, 'Se edito la solicitud de cargos desempeñados con id '. $idSolicitud);
                        $this->flashMessenger()->addSuccessMessage('Solicitud editada correctamente');
                    } else {
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                    }
                } catch (\Exception $exc) {
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }
                

            }else{

                try {
                    $encripted_name = $fileManager->uploadFile($file_name);
                    //No se logro subir el archivo al servidor
                    if(!$encripted_name){
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                        return;
                    }
                    
                    $key = "url_constancia";
                    $params[$key] = $encripted_name;
        
                    $result = $cargosTable->insert($params);
                    if ($result->getAffectedRows() > 0) {
                        $this->saveLog($id_usuario, 'Se creo una nueva solicitud de la categoria: cargos desempeñados');
                        $this->flashMessenger()->addSuccessMessage('Solicitud creada con exito');
                    } else {
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                    }
                } catch (\Exception $exc) {
                    //throw $th;
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }
                
            }

        }

        if($idSolicitud == 0){
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "periodoActivo" => $periodoActivo
            ]);
        }else{
            $solicitud = $cargosTable->getCargoById($id_usuario, $idSolicitud);
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "dataedit" => $solicitud, 
                "periodoActivo" => $periodoActivo
            ]);
        }
    }

    public function investigacionesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Obtenemos el periodo activo para validaciones
        $periodoActivo = $this->obtenerPeriodoActivo();

        $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $idSolicitud = $this->params()->fromRoute('val2',0);

        if ($this->getRequest()->isPost()) {

            $params = $this->params()->fromPost();
            $file_name = $_FILES['subir_archivo']['name'];
            if($this->params()->fromPost("_method") == "put"){
                unset($params['_method']);

                if($file_name){
                    $encripted_name = $fileManager->uploadFile($file_name);
                    //No se logro subir el archivo al servidor
                    if(!$encripted_name){
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                        return;
                    }
                    $key = "url_constancia";
                    $params[$key] = $encripted_name; 
                }

                $result = $investigacionesTable->update($params, ["id_investigacion" => $idSolicitud]);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se edito la solicitud de investigaciones/publicaciones con id '. $idSolicitud);
                    $this->flashMessenger()->addSuccessMessage('Solicitud editada correctamente');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }

            }else{
                $encripted_name = $fileManager->uploadFile($file_name);
                //No se logro subir el archivo al servidor
                if(!$encripted_name){
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                    return;
                }
                
                $key = "url_constancia";
                $params[$key] = $encripted_name;
    
                $result = $investigacionesTable->insert($params);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se creo una nueva solicitud de la categoria: Investigaciones/publicaciones');
                    $this->flashMessenger()->addSuccessMessage('Solicitud creada con exito');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                }
            }

        }

        if($idSolicitud == 0){
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "periodoActivo" => $periodoActivo
            ]);
        }else{
            $solicitud = $investigacionesTable->getInvestigacionesById($id_usuario, $idSolicitud);
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "dataedit" => $solicitud, 
                "periodoActivo" => $periodoActivo
            ]);
        }
    }

    public function capacitacionProfesionalAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Obtenemos el periodo activo para validaciones
        $periodoActivo = $this->obtenerPeriodoActivo();

        $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $idSolicitud = $this->params()->fromRoute('val2',0);

        if ($this->getRequest()->isPost()) {

            $params = $this->params()->fromPost();
            $file_name = $_FILES['subir_archivo']['name'];

            if($this->params()->fromPost("_method") == "put"){
                unset($params['_method']);
                if($file_name){
                    $encripted_name = $fileManager->uploadFile($file_name);
                    //No se logro subir el archivo al servidor
                    if(!$encripted_name){
                        $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                        $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                        return;
                    }
                    $key = "url_constancia";
                    $params[$key] = $encripted_name; 
                }

                $result = $capacitacionTable->update($params, ["id_capacitacion" => $idSolicitud]);
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se edito la solicitud de capacitación profesional con id '. $idSolicitud);
                    $this->flashMessenger()->addSuccessMessage('Solicitud editada correctamente');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar editar la solicitud.'); 
                }

            }else{
                $encripted_name = $fileManager->uploadFile($file_name);
                //No se logro subir el archivo al servidor
                if(!$encripted_name){
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                    return;
                }
    
                $key = "url_constancia";
                $params[$key] = $encripted_name;
    
                $result = $capacitacionTable->insert($params);
                
                if ($result->getAffectedRows() > 0) {
                    $this->saveLog($id_usuario, 'Se creo una nueva solicitud de la categoria: capacitación profesional');
                    $this->flashMessenger()->addSuccessMessage('Solicitud creada con exito');
                } else {
                    $this->saveLog($id_usuario, 'Error al realizar la solicitud');
                    $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                }
            }

        }

        if($idSolicitud == 0){
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "periodoActivo" => $periodoActivo
            ]);
        }else{
            $solicitud = $capacitacionTable->getCapacitacionById($id_usuario, $idSolicitud);
            return new ViewModel([
                "data" => $this->authService->getIdentity()->getData(), 
                "dataedit" => $solicitud, 
                "periodoActivo" => $periodoActivo
            ]);
        }
    }

    //Administracion de las solicitudes
    public function solicitudesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Verificar período activo
        $periodosTable = new \ORM\Model\Entity\PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        
        if (empty($periodoActivo)) {
            $this->flashMessenger()->addErrorMessage('No hay un período activo configurado.');
            return $this->redirect()->toRoute("administracionHome/administracion", ["action" => "configuracion"]);
        }

        // Obtener filtro de categoría de la URL
        $categoriaFiltro = $this->params()->fromRoute('val1', 'todas'); 

        // Inicializar arrays vacíos
        $premios = [];
        $cargos = [];
        $capacitacionList = [];
        $formacionList = [];
        $investigaciones = [];

        // Obtener datos del PERÍODO ACTIVO según el filtro de categoría
        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'premios') {
            $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
            $premios = $premiosTable->getPremiosPeriodoActual();
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'cargos') {
            $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
            $cargos = $cargosTable->getCargosPeriodoActual();
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'capacitacion') {
            $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
            $capacitacionList = $capacitacionTable->getCapacitacionProfesionalPeriodoActual();
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'formacion') {
            $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
            $formacionList = $formacionTable->getFormacionAcademicaPeriodoActual(); 
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'investigaciones') {
            $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
            $investigaciones = $investigacionesTable->getInvestigacionesPeriodoActual();
        }

        return new ViewModel([
            "data" => $this->authService->getIdentity()->getData(), 
            "premios" => $premios, 
            "cargos" => $cargos, 
            "capacitacion" => $capacitacionList, 
            "formacion" => $formacionList, 
            "investigaciones" => $investigaciones,
            "categoriaActual" => $categoriaFiltro,
            "periodoActivo" => $periodoActivo[0]
        ]);
    }


    public function misSolicitudesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $usuario = $this->authService->getIdentity()->getData()["usuario"];

        $periodosTable = new \ORM\Model\Entity\PeriodosTable($this->adapter);
        $periodoActivo = $periodosTable->getPeriodoActivo();
        $puedeSubir = $periodosTable->isPeriodoActivo();

        //Obtenemos todos los méritos académicos por usuario del PERÍODO ACTIVO
        $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
        $misPremios = $premiosTable->getPremiosByUserPeriodoActual($usuario);

        $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
        $misCargos = $cargosTable->getCargosByUserPeriodoActual($usuario);

        $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
        $misCapacitaciones = $capacitacionTable->getCapacitacionProfesionalByUserPeriodoActual($usuario);
        
        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
        $miformacionacademica = $formacionTable->getFormacionAcademicaByUserPeriodoActual($usuario);
        
        $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
        $misInvestigaciones = $investigacionesTable->getInvestigacionesByUserPeriodoActual($usuario);
        
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $misPuntos = $puntosTable->getPuntosByUser($usuario);
        
        $puntajeActual = $misPuntos ? floatval($misPuntos[0]["capacitacion_profesional"]) + floatval($misPuntos[0]["formacion_academica"]) + floatval($misPuntos[0]["premios"]) + floatval($misPuntos[0]["investigaciones"]) + floatval($misPuntos[0]["cargos"]) : 0;
        
        if(!$misPuntos){
            $misPuntos = array( ["premios" => "0",
                "investigaciones" => "0",
                "cargos" => "0",
                "capacitacion_profesional" => "0",
                "formacion_academica" => "0" ]);
        }

        return new ViewModel([
            "data" => $this->authService->getIdentity()->getData(),  
            "premios"=> $misPremios, 
            "cargos"=> $misCargos, 
            "formacionAcademica" => $miformacionacademica, 
            "capacitacionProfesional"=> $misCapacitaciones, 
            "investigaciones"=>  $misInvestigaciones, 
            "miPuntaje" =>$puntajeActual, 
            "puntos" => $misPuntos,
            "periodoActivo" => !empty($periodoActivo) ? $periodoActivo[0] : null,
            "puedeSubir" => $puedeSubir
        ]);
    }


    public function admCargosAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $id_admin = $this->authService->getIdentity()->getData()["usuario"];
        $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        
        $id_solicitud = $this->params()->fromRoute('val2',0);
        $solicitud = $cargosTable->getSolicitud($id_solicitud);

        if ($this->params()->fromPost("action") == "editarEstado") {
            $nuevoEstado = $this->params()->fromPost("nuevo_estado");
            $motivoCambio = $this->params()->fromPost("motivo_cambio");
            $id_usuario = $this->params()->fromPost("id_usuario");
            $puntosActuales = floatval($this->params()->fromPost("puntos_actuales"));
            
            $estadoAnterior = $solicitud[0]["id_estado"];
            $user = $userTable->getUserById($id_usuario);
            
            // Actualizar estado de la solicitud
            $params = array(
                "id_estado" => $nuevoEstado,
                "mensaje" => $motivoCambio
            );
            
            $result = $cargosTable->update($params, ["id_cargo" => $id_solicitud]);
            
            if ($result > 0) {
                // Manejar puntos según el cambio de estado
                $this->manejarCambioPuntosCargos($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
                // Registrar en log
                $estadoTexto = $this->getEstadoTexto($nuevoEstado);
                $estadoAnteriorTexto = $this->getEstadoTexto($estadoAnterior);
                
                $logMessage = "Se cambió el estado de la solicitud de cargos desempeñados ID: {$id_solicitud} " .
                            "de '{$estadoAnteriorTexto}' a '{$estadoTexto}'. " .
                            "Usuario afectado: {$user[0]['nombre']}. Motivo: {$motivoCambio}";
                
                $this->saveLog($id_admin, $logMessage);
                $this->flashMessenger()->addSuccessMessage("Estado de solicitud cambiado exitosamente a: {$estadoTexto}");
                
                return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Error al cambiar el estado de la solicitud.');
            }
        }

        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';
            
            $user = $userTable->getUserById($params['id_usuario']);
            
            unset($params['id_usuario']);
            unset($params['action']);

            $result = $cargosTable->update($params, ["id_cargo" => $id_solicitud]);
                
            if ($result > 0) {
                //$this->sendEmail($user[0]['email'],$user[0]['nombre'], 'rechazada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de cargos desempeñados con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud rechazada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
           
        }

        if ($this->params()->fromPost("action") == "aceptar") {
            $params = array( "mensaje" => "Solicitud aceptada con éxito",
                "id_estado" => '2' );

            $resultado = $cargosTable->update($params, ["id_cargo" => $id_solicitud]);
            
            
            if ($resultado > 0) {
                $id_usuario = $this->params()->fromPost("id_usuario");
                $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
                $puntosActuales = $misPuntos ? $misPuntos[0]["cargos"] : 0;
                $year = date("Y");
                $auxPts = $this->params()->fromPost("puntos");
                if($misPuntos){
                    //Ya hay puntos 
                    $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                    if($nuevoPuntaje >= 4){
                        $nuevoPuntaje = 4;
                    }
                    $params = array( "cargos" => $nuevoPuntaje,
                    "year"=>$year);
                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }else{
                    //No hay puntos
                    $params = array( "cargos" => $auxPts,
                                     "id_usuario" => $id_usuario,
                                     "year"=>$year);
                    $result = $puntosTable->insert($params);
                }

                $user = $userTable->getUserById($id_usuario);
                //$this->sendEmail($user[0]['email'], $user[0]['nombre'], 'aceptada');
                $this->saveLog($id_admin, 'Se acepto la solicitud de cargos desempeñados con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud aceptada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }

        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "solicitudData" => $solicitud]);

    }

    public function admPremiosAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());


        $id_admin = $this->authService->getIdentity()->getData()["usuario"];
        $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        
        $id_solicitud = $this->params()->fromRoute('val2',0);
        $solicitud = $premiosTable->getSolicitud($id_solicitud);

        if ($this->params()->fromPost("action") == "editarEstado") {
            $nuevoEstado = $this->params()->fromPost("nuevo_estado");
            $motivoCambio = $this->params()->fromPost("motivo_cambio");
            $id_usuario = $this->params()->fromPost("id_usuario");
            $puntosActuales = floatval($this->params()->fromPost("puntos_actuales"));
            
            $estadoAnterior = $solicitud[0]["id_estado"];
            $user = $userTable->getUserById($id_usuario);
            
            // Actualizar estado de la solicitud
            $params = array(
                "id_estado" => $nuevoEstado,
                "mensaje" => $motivoCambio
            );
            
            $result = $premiosTable->update($params, ["id_premio" => $id_solicitud]);
            
            if ($result > 0) {
                // Manejar puntos según el cambio de estado
                $this->manejarCambioPuntosPremios($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
                // Registrar en log
                $estadoTexto = $this->getEstadoTexto($nuevoEstado);
                $estadoAnteriorTexto = $this->getEstadoTexto($estadoAnterior);
                
                $logMessage = "Se cambió el estado de la solicitud de premios ID: {$id_solicitud} " .
                            "de '{$estadoAnteriorTexto}' a '{$estadoTexto}'. " .
                            "Usuario afectado: {$user[0]['nombre']}. Motivo: {$motivoCambio}";
                
                $this->saveLog($id_admin, $logMessage);
                $this->flashMessenger()->addSuccessMessage("Estado de solicitud cambiado exitosamente a: {$estadoTexto}");
                
                return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Error al cambiar el estado de la solicitud.');
            }
        }

        //var_dump($solicitud);
        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';

            $user = $userTable->getUserById($params['id_usuario']);

            unset($params['id_usuario']);
            unset($params['action']);

            $result = $premiosTable->update($params, ["id_premio" => $id_solicitud]);
                
            if ($result > 0) {
                // $this->sendEmail($user[0]['email'],$user[0]['nombre'], 'rechazada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de premios con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud rechazada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
           
        }

        if ($this->params()->fromPost("action") == "aceptar") {
            $params = array( "mensaje" => "Solicitud aceptada con éxito",
                "id_estado" => '2' );

            $resultado = $premiosTable->update($params, ["id_premio" => $id_solicitud]);
            
            
            if ($resultado > 0) {
                $id_usuario = $this->params()->fromPost("id_usuario");
                $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
                $puntosActuales = $misPuntos ? $misPuntos[0]["premios"] : 0;
                $year = date("Y");
                $auxPts = $this->params()->fromPost("puntos");
                if($misPuntos){
                    //Ya hay puntos 
                    $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                    if($nuevoPuntaje >= 2){
                        $nuevoPuntaje = 2;
                    }else{
                        $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                    }

                    $params = array( "premios" => $nuevoPuntaje,
                    "year"=>$year);
                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }else{
                    //No hay puntos
                    $params = array( "premios" => $auxPts,
                                     "id_usuario" => $id_usuario,
                                     "year"=>$year);
                    $result = $puntosTable->insert($params);
                }

                $user = $userTable->getUserById($id_usuario);
               //$this->sendEmail($user[0]['email'], $user[0]['nombre'], 'aceptada');
                $this->saveLog($id_admin, 'Se acepto la solicitud de premios con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud aceptada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
            
        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "solicitudData" => $solicitud]);

    }

    public function admCapacitacionProfesionalAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());
        $id_admin = $this->authService->getIdentity()->getData()["usuario"];

        $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        
        $id_solicitud = $this->params()->fromRoute('val2',0);
        $solicitud = $capacitacionTable->getSolicitud($id_solicitud);

        if ($this->params()->fromPost("action") == "editarEstado") {
            $nuevoEstado = $this->params()->fromPost("nuevo_estado");
            $motivoCambio = $this->params()->fromPost("motivo_cambio");
            $id_usuario = $this->params()->fromPost("id_usuario");
            $puntosActuales = floatval($this->params()->fromPost("puntos_actuales"));
            
            $estadoAnterior = $solicitud[0]["id_estado"];
            $user = $userTable->getUserById($id_usuario);
            
            // Actualizar estado de la solicitud
            $params = array(
                "id_estado" => $nuevoEstado,
                "mensaje" => $motivoCambio
            );
            
            $result = $capacitacionTable->update($params, ["id_capacitacion" => $id_solicitud]);
            
            if ($result > 0) {
                // Manejar puntos según el cambio de estado
                $this->manejarCambioPuntosCapacitacion($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
                // Registrar en log
                $estadoTexto = $this->getEstadoTexto($nuevoEstado);
                $estadoAnteriorTexto = $this->getEstadoTexto($estadoAnterior);
                
                $logMessage = "Se cambió el estado de la solicitud de capacitación profesional ID: {$id_solicitud} " .
                            "de '{$estadoAnteriorTexto}' a '{$estadoTexto}'. " .
                            "Usuario afectado: {$user[0]['nombre']}. Motivo: {$motivoCambio}";
                
                $this->saveLog($id_admin, $logMessage);
                $this->flashMessenger()->addSuccessMessage("Estado de solicitud cambiado exitosamente a: {$estadoTexto}");
                
                return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Error al cambiar el estado de la solicitud.');
            }
        }

        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';

            $user = $userTable->getUserById($params['id_usuario']);

            unset($params['id_usuario']);
            unset($params['action']);

            $result = $capacitacionTable->update($params, ["id_capacitacion" => $id_solicitud]);
                
            if ($result > 0) {
                //$this->sendEmail($user[0]['email'],$user[0]['nombre'], 'rechazada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de capacitación profesional con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud rechazada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
           
        }

        if ($this->params()->fromPost("action") == "aceptar") {
            $params = array( "mensaje" => "Solicitud aceptada con éxito",
                "id_estado" => '2' ); //cambiar a estado 2

            $resultado = $capacitacionTable->update($params, ["id_capacitacion" => $id_solicitud]);
            
       // print_r($resultado);die;
            if ($resultado > 0) {
                $id_usuario = $this->params()->fromPost("id_usuario");
                $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
                $puntosActuales = $misPuntos ? $misPuntos[0]["capacitacion_profesional"] : 0;
                $year = date("Y");
                $auxPts = $this->params()->fromPost("puntos");
                
                if($misPuntos){
                    
                    //Ya hay puntos 
                    $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                  /*
                    print_r('<br>puntosActuales = '.$puntosActuales);
                    print_r('<br>auxPts = '.$auxPts);
                    print_r('<br>nuevoPuntaje1 = '.$nuevoPuntaje);*/
                    if($nuevoPuntaje >= 8){
                        $nuevoPuntaje = 8;
                    }else{
                        $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);;
                    }
                    /* //modificado a solicitud de CEDA 2025-02-18 13:00
                    if($auxPts >= floatval($puntosActuales)){
                        $nuevoPuntaje = $auxPts;
                    }else{
                        $nuevoPuntaje = $puntosActuales;
                    }*/
                    //print_r('<br>nuevoPuntaje2 = '.$nuevoPuntaje);
                    //die;
                    $params = array( "capacitacion_profesional" => $nuevoPuntaje,
                    "year"=>$year);

                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }else{
                    //No hay puntos
                    $params = array( "capacitacion_profesional" => $auxPts,
                                    "id_usuario" => $id_usuario,
                                    "year"=>$year);
                    $result = $puntosTable->insert($params);
                }

                $user = $userTable->getUserById($id_usuario);
                // $this->sendEmail($user[0]['email'], $user[0]['nombre'], 'aceptada');
                $this->saveLog($id_admin, 'Se acepto la solicitud de capacitación profesional con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud aceptada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }

        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "solicitudData" => $solicitud]);

    }

    public function admFormacionAcademicaAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $id_admin = $this->authService->getIdentity()->getData()["usuario"];
        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);

        $id_solicitud = $this->params()->fromRoute('val2',0);
        $solicitud = $formacionTable->getSolicitud($id_solicitud);

        if ($this->params()->fromPost("action") == "editarEstado") {
            $nuevoEstado = $this->params()->fromPost("nuevo_estado");
            $motivoCambio = $this->params()->fromPost("motivo_cambio");
            $id_usuario = $this->params()->fromPost("id_usuario");
            $puntosActuales = floatval($this->params()->fromPost("puntos_actuales"));
            
            $estadoAnterior = $solicitud[0]["id_estado"];
            $user = $userTable->getUserById($id_usuario);
            
            // Actualizar estado de la solicitud
            $params = array(
                "id_estado" => $nuevoEstado,
                "mensaje" => $motivoCambio
            );
            
            $result = $formacionTable->update($params, ["id_formacion_academica" => $id_solicitud]);
            
            if ($result > 0) {
                // Manejar puntos según el cambio de estado
                $this->manejarCambioPuntos($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
                // Registrar en log
                $estadoTexto = $this->getEstadoTexto($nuevoEstado);
                $estadoAnteriorTexto = $this->getEstadoTexto($estadoAnterior);
                
                $logMessage = "Se cambió el estado de la solicitud de formación académica ID: {$id_solicitud} " .
                            "de '{$estadoAnteriorTexto}' a '{$estadoTexto}'. " .
                            "Usuario afectado: {$user[0]['nombre']}. Motivo: {$motivoCambio}";
                
                $this->saveLog($id_admin, $logMessage);
                $this->flashMessenger()->addSuccessMessage("Estado de solicitud cambiado exitosamente a: {$estadoTexto}");
                
                return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Error al cambiar el estado de la solicitud.');
            }
        }

        // ... resto del código existente para "rechazar" y "aceptar" ...

        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';

            $user = $userTable->getUserById($params['id_usuario']);

            unset($params['id_usuario']);
            unset($params['action']);

            $result = $formacionTable->update($params, ["id_formacion_academica" => $id_solicitud]);
                
            if ($result > 0) {
                //$this->sendEmail($user[0]['email'],$user[0]['nombre'], 'rechazada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de formación profesional con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud rechazada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
        }

        if ($this->params()->fromPost("action") == "aceptar") {
            $params = array( "mensaje" => "Solicitud aceptada con éxito",
                "id_estado" => '2' );

            $resultado = $formacionTable->update($params, ["id_formacion_academica" => $id_solicitud]);
            
            if ($resultado > 0) {
                $id_usuario = $this->params()->fromPost("id_usuario");
                $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
                
                $puntosActuales = $misPuntos ? $misPuntos[0]["formacion_academica"] : 0;
                $year = date("Y");
                $auxPts = $this->params()->fromPost("puntos");
                if($misPuntos){
                    if($auxPts >= floatval($puntosActuales)){
                        $nuevoPuntaje = $auxPts;
                    }else{
                        $nuevoPuntaje = $puntosActuales;
                    }
                    $params = array( "formacion_academica" => $nuevoPuntaje,
                    "year"=>$year);
                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }else{
                    $params = array( "formacion_academica" => $auxPts,
                                    "id_usuario" => $id_usuario,
                                    "year"=>$year);
                    $result = $puntosTable->insert($params);
                }

                $user = $userTable->getUserById($id_usuario);
                //$this->sendEmail($user[0]['email'], $user[0]['nombre'], 'aceptada');
                $this->saveLog($id_admin, 'Se acepto la solicitud de formación académica con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud aceptada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "solicitudData" => $solicitud]);
    }

    // Método auxiliar para manejar puntos de PREMIOS
    private function manejarCambioPuntosPremios($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado) {
        $year = date("Y");
        $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
        $puntosUsuario = $misPuntos ? floatval($misPuntos[0]["premios"]) : 0;

        // Si cambia de Aceptada (2) a Rechazada (3) o Ingresada (1) -> QUITAR puntos
        if ($estadoAnterior == 2 && in_array($nuevoEstado, [1, 3])) {
            $nuevosPuntos = max(0, $puntosUsuario - $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["premios" => $nuevosPuntos, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            }
        }
        
        // Si cambia de Rechazada (3) o Ingresada (1) a Aceptada (2) -> AGREGAR puntos
        if (in_array($estadoAnterior, [1, 3]) && $nuevoEstado == 2) {
            $nuevoPuntaje = $puntosUsuario + $puntosActuales;
            if ($nuevoPuntaje >= 2) {
                $nuevoPuntaje = 2; // Máximo para premios
            }
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["premios" => $nuevoPuntaje, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            } else {
                $puntosTable->insert([
                    "premios" => $puntosActuales,
                    "id_usuario" => $id_usuario,
                    "year" => $year
                ]);
            }
        }
    }

    // Método auxiliar para manejar puntos de CARGOS
    private function manejarCambioPuntosCargos($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado) {
        $year = date("Y");
        $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
        $puntosUsuario = $misPuntos ? floatval($misPuntos[0]["cargos"]) : 0;

        // Si cambia de Aceptada (2) a Rechazada (3) o Ingresada (1) -> QUITAR puntos
        if ($estadoAnterior == 2 && in_array($nuevoEstado, [1, 3])) {
            $nuevosPuntos = max(0, $puntosUsuario - $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["cargos" => $nuevosPuntos, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            }
        }
        
        // Si cambia de Rechazada (3) o Ingresada (1) a Aceptada (2) -> AGREGAR puntos
        if (in_array($estadoAnterior, [1, 3]) && $nuevoEstado == 2) {
            $nuevoPuntaje = $puntosUsuario + $puntosActuales;
            if ($nuevoPuntaje >= 4) {
                $nuevoPuntaje = 4; // Máximo para cargos
            }
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["cargos" => $nuevoPuntaje, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            } else {
                $puntosTable->insert([
                    "cargos" => $puntosActuales,
                    "id_usuario" => $id_usuario,
                    "year" => $year
                ]);
            }
        }
    }

    // Método auxiliar para manejar puntos de CAPACITACIÓN
    private function manejarCambioPuntosCapacitacion($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado) {
        $year = date("Y");
        $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
        $puntosUsuario = $misPuntos ? floatval($misPuntos[0]["capacitacion_profesional"]) : 0;

        // Si cambia de Aceptada (2) a Rechazada (3) o Ingresada (1) -> QUITAR puntos
        if ($estadoAnterior == 2 && in_array($nuevoEstado, [1, 3])) {
            $nuevosPuntos = max(0, $puntosUsuario - $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["capacitacion_profesional" => $nuevosPuntos, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            }
        }
        
        // Si cambia de Rechazada (3) o Ingresada (1) a Aceptada (2) -> AGREGAR puntos
        if (in_array($estadoAnterior, [1, 3]) && $nuevoEstado == 2) {
            $nuevoPuntaje = $puntosUsuario + $puntosActuales;
            if ($nuevoPuntaje >= 8) {
                $nuevoPuntaje = 8; // Máximo para capacitación
            }
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["capacitacion_profesional" => $nuevoPuntaje, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            } else {
                $puntosTable->insert([
                    "capacitacion_profesional" => $puntosActuales,
                    "id_usuario" => $id_usuario,
                    "year" => $year
                ]);
            }
        }
    }

    // Método auxiliar para manejar puntos de INVESTIGACIONES
    private function manejarCambioPuntosInvestigaciones($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado) {
        $year = date("Y");
        $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
        $puntosUsuario = $misPuntos ? floatval($misPuntos[0]["investigaciones"]) : 0;

        // Si cambia de Aceptada (2) a Rechazada (3) o Ingresada (1) -> QUITAR puntos
        if ($estadoAnterior == 2 && in_array($nuevoEstado, [1, 3])) {
            $nuevosPuntos = max(0, $puntosUsuario - $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["investigaciones" => $nuevosPuntos, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            }
        }
        
        // Si cambia de Rechazada (3) o Ingresada (1) a Aceptada (2) -> AGREGAR puntos
        if (in_array($estadoAnterior, [1, 3]) && $nuevoEstado == 2) {
            $nuevoPuntaje = $puntosUsuario + $puntosActuales;
            if ($nuevoPuntaje >= 6) {
                $nuevoPuntaje = 6; // Máximo para investigaciones
            }
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["investigaciones" => $nuevoPuntaje, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            } else {
                $puntosTable->insert([
                    "investigaciones" => $puntosActuales,
                    "id_usuario" => $id_usuario,
                    "year" => $year
                ]);
            }
        }
    }

    private function getEstadoTexto($idEstado) {
        switch($idEstado) {
            case 1: return 'Ingresada';
            case 2: return 'Aceptada';
            case 3: return 'Rechazada';
            default: return 'Desconocido';
        }
    }

    public function admInvestigacionesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $id_admin = $this->authService->getIdentity()->getData()["usuario"];
        $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
        $puntosTable = new \ORM\Model\Entity\PuntosTable($this->adapter);
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        
        $id_solicitud = $this->params()->fromRoute('val2',0);
        $solicitud = $investigacionesTable->getSolicitud($id_solicitud);

        if ($this->params()->fromPost("action") == "editarEstado") {
            $nuevoEstado = $this->params()->fromPost("nuevo_estado");
            $motivoCambio = $this->params()->fromPost("motivo_cambio");
            $id_usuario = $this->params()->fromPost("id_usuario");
            $puntosActuales = floatval($this->params()->fromPost("puntos_actuales"));
            
            $estadoAnterior = $solicitud[0]["id_estado"];
            $user = $userTable->getUserById($id_usuario);
            
            // Actualizar estado de la solicitud
            $params = array(
                "id_estado" => $nuevoEstado,
                "mensaje" => $motivoCambio
            );
            
            $result = $investigacionesTable->update($params, ["id_investigacion" => $id_solicitud]);
            
            if ($result > 0) {
                // Manejar puntos según el cambio de estado
                $this->manejarCambioPuntosInvestigaciones($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
                // Registrar en log
                $estadoTexto = $this->getEstadoTexto($nuevoEstado);
                $estadoAnteriorTexto = $this->getEstadoTexto($estadoAnterior);
                
                $logMessage = "Se cambió el estado de la solicitud de investigaciones/publicaciones ID: {$id_solicitud} " .
                            "de '{$estadoAnteriorTexto}' a '{$estadoTexto}'. " .
                            "Usuario afectado: {$user[0]['nombre']}. Motivo: {$motivoCambio}";
                
                $this->saveLog($id_admin, $logMessage);
                $this->flashMessenger()->addSuccessMessage("Estado de solicitud cambiado exitosamente a: {$estadoTexto}");
                
                return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Error al cambiar el estado de la solicitud.');
            }
        }
        
        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';

            $user = $userTable->getUserById($params['id_usuario']);

            unset($params['id_usuario']);
            unset($params['action']);

            $result = $investigacionesTable->update($params, ["id_investigacion" => $id_solicitud]);
                
            if ($result > 0) {
                //$this->sendEmail($user[0]['email'],$user[0]['nombre'], 'rechazada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de investigaciones/publicaciones con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud rechazada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }
           
        }

        if ($this->params()->fromPost("action") == "aceptar") {
            $params = array( "mensaje" => "Solicitud aceptada con éxito",
                "id_estado" => '2' );

            $resultado = $investigacionesTable->update($params, ["id_investigacion" => $id_solicitud]);

            if ($resultado > 0) {
                $id_usuario = $this->params()->fromPost("id_usuario");
                $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
                $puntosActuales = $misPuntos ? $misPuntos[0]["investigaciones"] : 0;
                $year = date("Y");
                $auxPts = $this->params()->fromPost("puntos");
                
                if($misPuntos){
                    
                    //Ya hay puntos 
                    $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                  /*
                    print_r('<br>puntosActuales = '.$puntosActuales);
                    print_r('<br>auxPts = '.$auxPts);
                    print_r('<br>nuevoPuntaje1 = '.$nuevoPuntaje);*/
                    if($nuevoPuntaje >= 6){
                        $nuevoPuntaje = 6;
                    }else{
                        $nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);;
                    }
                     //modificado a solicitud de CEDA 2025-02-18 13:00*
                    
                    $params = array( "investigaciones" => $nuevoPuntaje,
                    "year"=>$year);

                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }
                
                /*
                if($misPuntos){
                    //Ya hay puntos 
                    //$nuevoPuntaje = floatval($puntosActuales) + floatval($auxPts);
                    // if($nuevoPuntaje >= floatval($puntosActuales)){
                    //     $nuevoPuntaje = $nuevoPuntaje;
                    // }
                    if($auxPts >= floatval($puntosActuales)){
                        $nuevoPuntaje = $auxPts;
                    }else{
                        $nuevoPuntaje = $puntosActuales;
                    }
                    $params = array( "investigaciones" => $nuevoPuntaje,
                    "year"=>$year);
                    $result = $puntosTable->update($params,  ["id_usuario" => $id_usuario]);
                    
                }*/
                else{
                    //No hay puntos
                    $params = array( "investigaciones" => $auxPts,
                                     "id_usuario" => $id_usuario,
                                     "year"=>$year);
                    $result = $puntosTable->insert($params);
                }

                $user = $userTable->getUserById($id_usuario);
                //$this->sendEmail($user[0]['email'], $user[0]['nombre'], 'aceptada');
                $this->saveLog($id_admin, 'Se rechazo la solicitud de investigaciones/publicaciones con id: ' . $id_solicitud);
                $this->flashMessenger()->addSuccessMessage('Solicitud aceptada con éxito.');
                $this->redirect()->toRoute("meritosHome/meritos", ["action" => "solicitudes"]);
            } else {
                $this->flashMessenger()->addErrorMessage('Hubo un error al procesar su solicitud, por favor, intente de nuevo.');
            }

        }

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "solicitudData" => $solicitud]);

    }

    private function obtenerPeriodoActivo() {
        $periodosTable = new \ORM\Model\Entity\PeriodosTable($this->adapter);
        
        // Primero verificar períodos expirados
        $this->verificarYActualizarPeriodosExpirados();
        
        $sql = $periodosTable->getSql();
        $select = $sql->select();
        $select->where(['estado' => 'activo']);
        $select->order('fecha_fin DESC');
        $select->limit(1);
        
        $periodoActivo = $periodosTable->selectWith($select)->toArray();
        
        return !empty($periodoActivo) ? $periodoActivo[0] : null;
    }

    // Verificar y actualizar períodos expirados
    private function verificarYActualizarPeriodosExpirados() {
        $periodosTable = new \ORM\Model\Entity\PeriodosTable($this->adapter);
        
        date_default_timezone_set('America/Guatemala');
        $fechaHoraActual = new DateTime("now");
        
        // Obtener períodos activos que podrían haber expirado
        $sql = $periodosTable->getSql();
        $select = $sql->select();
        $select->where(['estado' => 'activo']);
        
        $periodosActivos = $periodosTable->selectWith($select)->toArray();
        
        $periodosCerradosAutomaticamente = 0;
        
        foreach ($periodosActivos as $periodo) {
            // Construir fecha y hora de expiración
            $fechaFin = $periodo['fecha_fin'] . " " . $periodo['hora_fin'];
            $fechaExpiracion = new DateTime($fechaFin);
            
            // Si el período ha expirado, cambiarlo a cerrado
            if ($fechaHoraActual > $fechaExpiracion) {
                try {
                    // Actualizar el estado a cerrado
                    $sqlUpdate = $periodosTable->getSql();
                    $update = $sqlUpdate->update();
                    $update->set(['estado' => 'cerrado']);
                    $update->where(['id_periodo' => $periodo['id_periodo']]);
                    $statement = $sqlUpdate->prepareStatementForSqlObject($update);
                    $statement->execute();
                    
                    // Log del cambio automático
                    $usuario = $this->authService->hasIdentity() && $this->authService->getIdentity()->getData() 
                            ? $this->authService->getIdentity()->getData()["usuario"] 
                            : 'SYSTEM';
                    
                    $this->saveLog(
                        $usuario, 
                        "Se cerró automáticamente el período '{$periodo['nombre']}' (ID: {$periodo['id_periodo']}) por expiración de fecha/hora. Expiró: {$fechaFin}"
                    );
                    
                    $periodosCerradosAutomaticamente++;
                    
                } catch (\Exception $e) {
                    // Log del error si algo falla
                    error_log("Error al cerrar período automáticamente ID {$periodo['id_periodo']}: " . $e->getMessage());
                }
            }
        }
        
        // Si se cerraron períodos automáticamente, mostrar mensaje informativo
        if ($periodosCerradosAutomaticamente > 0) {
            $mensaje = $periodosCerradosAutomaticamente == 1 
                    ? "Se cerró automáticamente 1 período por expiración de fecha/hora."
                    : "Se cerraron automáticamente {$periodosCerradosAutomaticamente} períodos por expiración de fecha/hora.";
            
            $this->flashMessenger()->addInfoMessage($mensaje);
        }
        
        return $periodosCerradosAutomaticamente;
    }

    //Configuracion 
    public function configuracionAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        
        // VERIFICAR PERÍODOS EXPIRADOS AL CARGAR LA PÁGINA
        $this->verificarYActualizarPeriodosExpirados();
        
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $id_admin = $this->authService->getIdentity()->getData()["usuario"];
        
        // MANEJO DE PERÍODOS
        $periodosTable = new \ORM\Model\Entity\PeriodosTable($this->adapter);

        if ($this->getRequest()->isPost()) {
            $action = $this->getRequest()->getPost('action');
            
            try {
                switch ($action) {
                    case 'crear_periodo':
                        $data = [
                            'nombre' => $this->getRequest()->getPost('nombre'),
                            'descripcion' => $this->getRequest()->getPost('descripcion'),
                            'fecha_inicio' => $this->getRequest()->getPost('fecha_inicio'),
                            'fecha_fin' => $this->getRequest()->getPost('fecha_fin'),
                            'hora_inicio' => $this->getRequest()->getPost('hora_inicio') ?: '00:00:00',
                            'hora_fin' => $this->getRequest()->getPost('hora_fin') ?: '23:59:59',
                            'estado' => $this->getRequest()->getPost('activar_ahora') ? 'activo' : 'inactivo'
                        ];
                        
                        // Validar fechas
                        if (strtotime($data['fecha_inicio']) >= strtotime($data['fecha_fin'])) {
                            throw new \Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
                        }
                        
                        // Si se va a activar, desactivar solo períodos activos (no los cerrados)
                        if ($data['estado'] === 'activo') {
                            $sql = $periodosTable->getSql();
                            $update = $sql->update();
                            $update->set(['estado' => 'inactivo']);
                            $update->where(['estado' => 'activo']);
                            $statement = $sql->prepareStatementForSqlObject($update);
                            $statement->execute();
                        }
                        
                        $periodosTable->insert($data);
                        $this->saveLog($id_admin, 'Se creó un nuevo período: ' . $data['nombre']);
                        $this->flashMessenger()->addSuccessMessage('Período creado exitosamente.');
                        break;
                        
                    case 'activar_periodo':
                        $periodo_id = $this->getRequest()->getPost('periodo_id');
                        
                        // Solo desactivar períodos activos
                        $sql = $periodosTable->getSql();
                        $update = $sql->update();
                        $update->set(['estado' => 'inactivo']);
                        $update->where(['estado' => 'activo']);
                        $statement = $sql->prepareStatementForSqlObject($update);
                        $statement->execute();
                        
                        // Activar el período seleccionado
                        $sql2 = $periodosTable->getSql();
                        $update2 = $sql2->update();
                        $update2->set(['estado' => 'activo']);
                        $update2->where(['id_periodo' => $periodo_id]);
                        $statement2 = $sql2->prepareStatementForSqlObject($update2);
                        $statement2->execute();
                        
                        $this->saveLog($id_admin, 'Se activó el período ID: ' . $periodo_id);
                        $this->flashMessenger()->addSuccessMessage('Período activado exitosamente.');
                        break;
                        
                    case 'editar_periodo':
                        $periodo_id = $this->getRequest()->getPost('periodo_id');
                        $data = [
                            'nombre' => $this->getRequest()->getPost('nombre'),
                            'descripcion' => $this->getRequest()->getPost('descripcion'),
                            'fecha_inicio' => $this->getRequest()->getPost('fecha_inicio'),
                            'fecha_fin' => $this->getRequest()->getPost('fecha_fin'),
                            'hora_inicio' => $this->getRequest()->getPost('hora_inicio') ?: '00:00:00',
                            'hora_fin' => $this->getRequest()->getPost('hora_fin') ?: '23:59:59'
                        ];
                        
                        if (strtotime($data['fecha_inicio']) >= strtotime($data['fecha_fin'])) {
                            throw new \Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
                        }
                        
                        // Actualizar usando SQL directo
                        $sql = $periodosTable->getSql();
                        $update = $sql->update();
                        $update->set($data);
                        $update->where(['id_periodo' => $periodo_id]);
                        $statement = $sql->prepareStatementForSqlObject($update);
                        $statement->execute();
                        
                        $this->saveLog($id_admin, 'Se editó el período ID: ' . $periodo_id);
                        $this->flashMessenger()->addSuccessMessage('Período actualizado exitosamente.');
                        break;
                        
                    case 'cerrar_periodo':
                        $periodo_id = $this->getRequest()->getPost('periodo_id');
                        
                        $sql = $periodosTable->getSql();
                        $update = $sql->update();
                        $update->set(['estado' => 'cerrado']);
                        $update->where(['id_periodo' => $periodo_id]);
                        $statement = $sql->prepareStatementForSqlObject($update);
                        $statement->execute();
                        
                        $this->saveLog($id_admin, 'Se cerró el período ID: ' . $periodo_id);
                        $this->flashMessenger()->addSuccessMessage('Período cerrado exitosamente.');
                        break;
                        
                    case 'eliminar_periodo':
                        $periodo_id = $this->getRequest()->getPost('periodo_id');
                        
                        // Verificar que no tenga méritos asociados
                        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
                        $select = $formacionTable->getSql()->select();
                        $select->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
                        $select->where(['id_periodo' => $periodo_id]);
                        $result = $formacionTable->selectWith($select)->toArray();
                        
                        if ($result[0]['count'] > 0) {
                            throw new \Exception("No se puede eliminar un período que tiene méritos asociados.");
                        }
                        
                        // Eliminar período
                        $sql = $periodosTable->getSql();
                        $delete = $sql->delete();
                        $delete->where(['id_periodo' => $periodo_id]);
                        $statement = $sql->prepareStatementForSqlObject($delete);
                        $statement->execute();
                        
                        $this->saveLog($id_admin, 'Se eliminó el período ID: ' . $periodo_id);
                        $this->flashMessenger()->addSuccessMessage('Período eliminado exitosamente.');
                        break;
                        
                    default:
                        throw new \Exception("Acción no válida.");
                }
                
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
            
            return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "configuracion"]);
        }

        // Obtener todos los períodos (después de la verificación automática)
        $periodos = $periodosTable->getAllPeriodos();

        return new ViewModel([
            "data" => $this->authService->getIdentity()->getData(), 
            "periodos" => $periodos
        ]);
    }

    public function reportesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());


        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);

        $reporte1 = $userTable->getReporte1and2();
        // var_dump($reporte1);

        //Obtenemos la lista de solicitudes  para el reporte 3
        $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
        $premios = $premiosTable->getPremios();
        $premiospendientes = $premiosTable->getPremiosByState('1');
        $premiosAceptados = $premiosTable->getPremiosByState('2');
        $premiosRechazados = $premiosTable->getPremiosByState('3');

        $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
        $cargos = $cargosTable->getCargos();
        $cargosPendientes = $cargosTable->getCargosByState('1');
        $cargosAceptados = $cargosTable->getCargosByState('2');
        $cargosRechazados = $cargosTable->getCargosByState('3');

        $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
        $capacitacionList = $capacitacionTable->getCapacitacionProfesional();
        $capacitacionesPendientes = $capacitacionTable->getCapacitacionProfesionalByState('1');
        $capacitacionesAceptados = $capacitacionTable->getCapacitacionProfesionalByState('2');
        $capacitacionesRechazados = $capacitacionTable->getCapacitacionProfesionalByState('3');
        
        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
        $formacionList = $formacionTable->getFormacionAcademica();
        $formacionesPendientes = $formacionTable->getFormacionAcademicaByState('1');
        $formacionesAceptados = $formacionTable->getFormacionAcademicaByState('2');
        $formacionesRechazados = $formacionTable->getFormacionAcademicaByState('3');
        
        $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
        $investigaciones = $investigacionesTable->getInvestigaciones();
        $investigacionesPendientes = $investigacionesTable->getInvestigacionesByState('1');
        $investigacionesAceptados = $investigacionesTable->getInvestigacionesByState('2');
        $investigacionesRechazados = $investigacionesTable->getInvestigacionesByState('3');

        $reporte3 = array( "premios" => count($premios),
                "cargos" => count($cargos),
                "capacitacion" => count($capacitacionList),
                "formacion" => count($formacionList),
                "investigaciones" => count($investigaciones)
        );

        $contadores = array(
            "ingresadas" => count($premiospendientes) + count($cargosPendientes) + count($capacitacionesPendientes) + count($formacionesPendientes) + count($investigacionesPendientes),
            "aceptadas" => count($premiosAceptados) + count($cargosAceptados) + count($capacitacionesAceptados) + count($formacionesAceptados) + count($investigacionesAceptados),
            "rechazadas" => count($premiosRechazados) + count($cargosRechazados) + count($capacitacionesRechazados)+ count($formacionesRechazados) + count($investigacionesRechazados)
        );

        //var_dump($contadores);

        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "dataR1" => $reporte1, "dataR3" => $reporte3, "contadores" => $contadores]);
    }


    public function bitacoraAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }
        //cambiar al layout administrativo...
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        $logsTable = new \ORM\Model\Entity\BitacoraTable($this->adapter);

        $logs = $logsTable->getAllLogs();


        //var_dump($logs);
        return new ViewModel(["data" => $this->authService->getIdentity()->getData(), "logs"=> $logs]);
    }
    

    public function informesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());
        $id = $this->authService->getIdentity()->getData()["usuario"];
        $informesTable = new \ORM\Model\Entity\InformeTable($this->adapter);
        $fileManager = new \Utilidades\Service\FileManager();

        if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            $file_name = $_FILES['subir_archivo']['name'];

            $encripted_name = $fileManager->uploadFile($file_name);
            //No se logro subir el archivo al servidor
            if(!$encripted_name){
                $this->saveLog($id, 'Error al realizar la solicitud');
                $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar realizar la solicitud.');
                return;
            }

            $key = "url_informe";
            $params[$key] = $encripted_name;

            $result = $informesTable->insert($params);
            
            if ($result > 0) {
                $this->saveLog($id, 'Se agrego un nuevo informe de actividades');
                $this->flashMessenger()->addSuccessMessage('Informe cargado correctamente');
            } else {
                $this->saveLog($id, 'Error al realizar la solicitud');
                $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar cargar el informe.');
            }


        }

        $informes = $informesTable->getInformesByUser($id);
        return new ViewModel(["informes" => $informes, "acceso" => $this->acceso, "data" => $this->authService->getIdentity()->getData()]);
    }


    public function admInformesAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('home');
        }

        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());
   
        $informesTable = new \ORM\Model\Entity\InformeTable($this->adapter);

        $data = $informesTable->getInformes();
        return new ViewModel(["informes" => $data, "acceso" => $this->acceso, "data" => $this->authService->getIdentity()->getData()]);
    }

}
