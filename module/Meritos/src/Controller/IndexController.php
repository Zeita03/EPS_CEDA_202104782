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

        if (
            $nombreAccion != 'accesoDenegado' && 
            $nombreAccion != 'periodoInactivo' && 
            $nombreAccion != 'premios' && 
            $nombreAccion !='formacionAcademica' && 
            $nombreAccion !='cargos' && 
            $nombreAccion !='investigaciones' && 
            $nombreAccion !='capacitacionProfesional' && 
            $nombreAccion != 'solicitudes' && 
            $nombreAccion != 'misSolicitudes' &&  
            $nombreAccion != 'configuracion' && 
            $nombreAccion != 'reportes' && 
            $nombreAccion != 'historial' && 
            $nombreAccion != 'admSolicitudes' && 
            $nombreAccion !='displayfile' &&
            $nombreAccion !='generarConstancia' &&
            $authManager->verificarPermiso($nombreControlador, $nombreAccion) != true
        ) {
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


    /**
     * Enviar constancia de méritos completados
     */
    public function enviarConstanciaAcademicos($email, $nombreUsuario, $datosConstancia) {
        $mailManager = new \Utilidades\Service\MailManager();

        // Construir tabla de méritos
        $filasMeritos = '';
        $totalMeritos = 0;
        
        $meritos = [
            'Premios' => $datosConstancia['premios'] ?? 0,
            'Cargos Desempeñados' => $datosConstancia['cargos'] ?? 0,
            'Formación Académica' => $datosConstancia['formacion'] ?? 0,
            'Capacitación Profesional' => $datosConstancia['capacitacion'] ?? 0,
            'Investigaciones/Publicaciones' => $datosConstancia['investigaciones'] ?? 0
        ];

        foreach ($meritos as $tipo => $cantidad) {
            $totalMeritos += $cantidad;
            $filasMeritos .= "
            <tr>
                <td style='padding: 12px; border: 1px solid #ddd; text-align: left;'>{$tipo}</td>
                <td style='padding: 12px; border: 1px solid #ddd; text-align: center;'><strong>{$cantidad}</strong></td>
            </tr>";
        }

        $htmlMail = '<!DOCTYPE html>
        <html lang="es" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
        <head>
            <title>Constancia de Carga de Méritos</title>
            <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
            <meta content="width=device-width,initial-scale=1" name="viewport" />
            <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css" />
            <style>
                * {
                    box-sizing: border-box;
                }
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-text-size-adjust: none;
                    text-size-adjust: none;
                }
                a[x-apple-data-detectors] {
                    color: inherit !important;
                    text-decoration: inherit !important;
                }
                table {
                    border-collapse: collapse;
                }
            </style>
        </head>
        <body style="background-color:#fff;margin:0;padding:0;">
            <table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation"
                style="mso-table-lspace:0;mso-table-rspace:0;background-color:#fff" width="100%">
                <tbody>
                    <tr>
                        <td>
                            <!-- HEADER -->
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1"
                                role="presentation" 
                                style="mso-table-lspace:0;mso-table-rspace:0;background:linear-gradient(180deg, #003470 0%, #041d3c 100%)"
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
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding-top:20px;padding-bottom:20px"
                                                            width="100%">
                                                            <div align="center" style="line-height:10px">
                                                                <img src="https://farusac.edu.gt/wp-content/uploads/2022/10/headerfarusaclogos.png"
                                                                    style="display:block;height:auto;border:0;width:410px;max-width:100%"
                                                                    width="410" />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- CONTENIDO PRINCIPAL -->
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2"
                                role="presentation" style="mso-table-lspace:0;mso-table-rspace:0;background-color:#f2f2f2"
                                width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;background-color:#f2f2f2;border-radius:40px 0;color:#000;width:500px;padding:30px 20px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td class="column column-1"
                                                            style="mso-table-lspace:0;mso-table-rspace:0;font-weight:400;text-align:left;vertical-align:top;padding:0"
                                                            width="100%">
                                                            
                                                            <!-- TÍTULO -->
                                                            <h1 style="margin:20px 0 20px 0;color:#041d3c;font-family:Nunito,Arial,sans-serif;font-size:26px;font-weight:bold;text-align:center;line-height:120%;padding-top:15px">
                                                                Constancia de Carga de Méritos Académicos
                                                            </h1>

                                                            <!-- SALUDO -->
                                                            <p style="margin:0 0 15px 0;color:#333;font-family:Nunito,Arial,sans-serif;font-size:15px;line-height:160%">
                                                                Estimado(a) <strong>' . htmlspecialchars($nombreUsuario) . '</strong>,
                                                            </p>

                                                            <!-- MENSAJE PRINCIPAL -->
                                                            <p style="margin:0 0 15px 0;color:#555;font-family:Nunito,Arial,sans-serif;font-size:14px;line-height:160%">
                                                                Nos complace informarle que ha <strong>completado exitosamente</strong> la carga de méritos académicos en el sistema CEDA (Comisión de Evaluación Docente de Arquitectura).
                                                            </p>

                                                            <p style="margin:0 0 20px 0;color:#555;font-family:Nunito,Arial,sans-serif;font-size:14px;line-height:160%">
                                                                A continuación se muestra un resumen de los méritos cargados:
                                                            </p>

                                                            <!-- TABLA DE MÉRITOS -->
                                                            <table style="width:100%;border-collapse:collapse;margin:0 0 20px 0;background:#fff;border:1px solid #ddd;border-radius:5px;overflow:hidden">
                                                                <thead>
                                                                    <tr style="background-color:#041d3c;color:white">
                                                                        <th style="padding:12px;border:1px solid #ddd;text-align:left;font-weight:bold;font-family:Nunito,Arial,sans-serif;font-size:14px">
                                                                            Tipo de Mérito
                                                                        </th>
                                                                        <th style="padding:12px;border:1px solid #ddd;text-align:center;font-weight:bold;font-family:Nunito,Arial,sans-serif;font-size:14px">
                                                                            Cantidad
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    ' . $filasMeritos . '
                                                                    <tr style="background-color:#f5f5f5;font-weight:bold">
                                                                        <td style="padding:12px;border:1px solid #ddd;text-align:left;font-family:Nunito,Arial,sans-serif">
                                                                            Total de Méritos
                                                                        </td>
                                                                        <td style="padding:12px;border:1px solid #ddd;text-align:center;color:#041d3c;font-size:16px;font-family:Nunito,Arial,sans-serif">
                                                                            ' . $totalMeritos . '
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                            <!-- PRÓXIMOS PASOS -->
                                                            <div style="background-color:#e7f3ff;border-left:4px solid #2196F3;padding:15px;margin:20px 0;border-radius:4px">
                                                                <p style="margin:0 0 10px 0;color:#0c5ba7;font-family:Nunito,Arial,sans-serif;font-size:14px;font-weight:bold">
                                                                    📋 Próximos Pasos:
                                                                </p>
                                                                <ul style="margin:0;padding-left:20px;color:#0c5ba7;font-family:Nunito,Arial,sans-serif;font-size:13px;line-height:180%">
                                                                    <li>Permanezca atento a las notificaciones en su plataforma</li>
                                                                    <li>Sus méritos serán evaluados por el comité correspondiente</li>
                                                                    <li>Podrá visualizar el estado de cada mérito en tiempo real</li>
                                                                    <li>Las calificaciones estarán disponibles en su dashboard</li>
                                                                </ul>
                                                            </div>

                                                            <!-- RECORDATORIO -->
                                                            <div style="background-color:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:20px 0;border-radius:4px">
                                                                <p style="margin:0;color:#856404;font-family:Nunito,Arial,sans-serif;font-size:13px;line-height:160%">
                                                                    <strong>💡 Importante:</strong> Esta constancia verifica que ha completado la carga de al menos un mérito en cada categoría dentro del período activo del sistema.
                                                                </p>
                                                            </div>

                                                            <!-- CIERRE -->
                                                            <p style="margin:20px 0 0 0;color:#666;font-family:Nunito,Arial,sans-serif;font-size:13px;line-height:160%;text-align:center">
                                                                Cualquier duda o inconveniente, no dude en contactar al equipo de soporte.<br>
                                                                <strong>Gracias por participar en el sistema CEDA</strong>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- FOOTER -->
                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-3"
                                role="presentation" 
                                style="mso-table-lspace:0;mso-table-rspace:0;background:linear-gradient(180deg, #003470 0%, #041d3c 100%)"
                                width="100%">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                class="row-content stack" role="presentation"
                                                style="mso-table-lspace:0;mso-table-rspace:0;color:#000;width:500px;padding:20px"
                                                width="500">
                                                <tbody>
                                                    <tr>
                                                        <td style="text-align:center">
                                                            <p style="margin:0;color:#fff;font-family:Nunito,Arial,sans-serif;font-size:11px">
                                                                Este correo fue generado automáticamente. Por favor no responda a este mensaje.
                                                            </p>
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
            </table>
        </body>
        </html>';

        $mailManager->sendGeneralMessage($email, "CEDA - Constancia de Carga de Méritos Académicos", $htmlMail);
    }

    /**
     * Validar si el usuario ha cargado al menos 1 mérito en cada categoría
     */
    private function validarMeritosPorCategoria($id_usuario) {
        $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
        $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
        $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
        $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
        $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);

        // Obtener méritos del período actual para este usuario
        $premios = $premiosTable->getPremiosByUserPeriodoActual($id_usuario);
        $cargos = $cargosTable->getCargosByUserPeriodoActual($id_usuario);
        $capacitacion = $capacitacionTable->getCapacitacionProfesionalByUserPeriodoActual($id_usuario);
        $formacion = $formacionTable->getFormacionAcademicaByUserPeriodoActual($id_usuario);
        $investigaciones = $investigacionesTable->getInvestigacionesByUserPeriodoActual($id_usuario);

        // Validar que haya al menos 1 en cada categoría
        $validaciones = [
            'premios' => count($premios) > 0,
            'cargos' => count($cargos) > 0,
            'capacitacion' => count($capacitacion) > 0,
            'formacion' => count($formacion) > 0,
            'investigaciones' => count($investigaciones) > 0
        ];

        // Retornar datos de validación y conteos
        return [
            'completo' => array_reduce($validaciones, function($carry, $item) {
                return $carry && $item;
            }, true),
            'validaciones' => $validaciones,
            'conteos' => [
                'premios' => count($premios),
                'cargos' => count($cargos),
                'capacitacion' => count($capacitacion),
                'formacion' => count($formacion),
                'investigaciones' => count($investigaciones)
            ]
        ];
    }

    /**
     * Generar constancia de méritos (Acción directa - GET/POST)
     */
    public function generarConstanciaAction() {
        $id_usuario = $this->authService->getIdentity()->getData()["usuario"];
        $userTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
        
        // Validar méritos
        $validacion = $this->validarMeritosPorCategoria($id_usuario);

        if (!$validacion['completo']) {
            // Si falta algún mérito, mostrar error
            $this->flashMessenger()->addErrorMessage('Debe cargar al menos un mérito en cada categoría para generar la constancia.');
            return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "misSolicitudes"]);
        }

        // Obtener datos del usuario
        $usuario = $userTable->getUserById($id_usuario);
        
        if (empty($usuario)) {
            $this->flashMessenger()->addErrorMessage('Usuario no encontrado.');
            return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "misSolicitudes"]);
        }

        try {
            // Enviar correo con constancia
            $this->enviarConstanciaAcademicos(
                $usuario[0]['email'],
                $usuario[0]['nombre'],
                $validacion['conteos']
            );

            // Registrar en log
            $this->saveLog($id_usuario, 'Se generó y envió constancia de méritos académicos al correo: ' . $usuario[0]['email']);

            $this->flashMessenger()->addSuccessMessage(
                'Constancia enviada exitosamente a ' . $usuario[0]['email'] . '. ' .
                'Revise su bandeja de entrada y carpeta de spam.'
            );

        } catch (\Exception $e) {
            $this->saveLog($id_usuario, 'Error al generar constancia: ' . $e->getMessage());
            $this->flashMessenger()->addErrorMessage('Error al generar la constancia: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute("meritosHome/meritos", ["action" => "misSolicitudes"]);
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
        
        // Inicializar el servicio de validación de puntajes
        $puntajeValidator = new \Meritos\Services\PuntajeValidatorService($this->adapter);
        
        // Procesar todas las solicitudes para agregar información de límites
        $todasLasSolicitudes = [];
        
        // Procesar PREMIOS
        foreach ($premios as &$premio) {
            $categoria = 'premios';
            $premio['categoria'] = $categoria;
            $premio['tabla'] = 'premios';
            $premio['id_merito'] = $premio['id_premio']; // Normalizar ID
            
            // Verificar límites del usuario
            $premio['categoria_limite_alcanzado'] = $puntajeValidator->categoriaLimiteAlcanzado($premio['id_usuario'], $categoria);
            $premio['limite_total_alcanzado'] = $puntajeValidator->limiteTotalAlcanzado($premio['id_usuario']);
            $premio['puntaje_categoria_actual'] = $puntajeValidator->obtenerPuntajeActual($premio['id_usuario'], $categoria);
            $premio['puntaje_total_actual'] = $puntajeValidator->obtenerPuntajeTotal($premio['id_usuario']);
            
            // Determinar información adicional para tooltip y estado visual
            $premio = $this->agregarInformacionEstado($premio, $puntajeValidator, $categoria);
            
            $todasLasSolicitudes[] = $premio;
        }
        
        // Procesar CARGOS
        foreach ($cargos as &$cargo) {
            $categoria = 'cargos';
            $cargo['categoria'] = $categoria;
            $cargo['tabla'] = 'cargos';
            $cargo['id_merito'] = $cargo['id_cargo']; // Normalizar ID
            
            $cargo['categoria_limite_alcanzado'] = $puntajeValidator->categoriaLimiteAlcanzado($cargo['id_usuario'], $categoria);
            $cargo['limite_total_alcanzado'] = $puntajeValidator->limiteTotalAlcanzado($cargo['id_usuario']);
            $cargo['puntaje_categoria_actual'] = $puntajeValidator->obtenerPuntajeActual($cargo['id_usuario'], $categoria);
            $cargo['puntaje_total_actual'] = $puntajeValidator->obtenerPuntajeTotal($cargo['id_usuario']);
            
            $cargo = $this->agregarInformacionEstado($cargo, $puntajeValidator, $categoria);
            
            $todasLasSolicitudes[] = $cargo;
        }
        
        // Procesar CAPACITACIÓN PROFESIONAL
        foreach ($capacitacionList as &$capacitacion) {
            $categoria = 'capacitacion_profesional';
            $capacitacion['categoria'] = $categoria;
            $capacitacion['tabla'] = 'capacitacion_profesional';
            $capacitacion['id_merito'] = $capacitacion['id_capacitacion']; // Normalizar ID
            
            $capacitacion['categoria_limite_alcanzado'] = $puntajeValidator->categoriaLimiteAlcanzado($capacitacion['id_usuario'], $categoria);
            $capacitacion['limite_total_alcanzado'] = $puntajeValidator->limiteTotalAlcanzado($capacitacion['id_usuario']);
            $capacitacion['puntaje_categoria_actual'] = $puntajeValidator->obtenerPuntajeActual($capacitacion['id_usuario'], $categoria);
            $capacitacion['puntaje_total_actual'] = $puntajeValidator->obtenerPuntajeTotal($capacitacion['id_usuario']);
            
            $capacitacion = $this->agregarInformacionEstado($capacitacion, $puntajeValidator, $categoria);
            
            $todasLasSolicitudes[] = $capacitacion;
        }
        
        // Procesar FORMACIÓN ACADÉMICA
        foreach ($formacionList as &$formacion) {
            $categoria = 'formacion_academica';
            $formacion['categoria'] = $categoria;
            $formacion['tabla'] = 'formacion_academica';
            $formacion['id_merito'] = $formacion['id_formacion_academica']; // Normalizar ID
            
            $formacion['categoria_limite_alcanzado'] = $puntajeValidator->categoriaLimiteAlcanzado($formacion['id_usuario'], $categoria);
            $formacion['limite_total_alcanzado'] = $puntajeValidator->limiteTotalAlcanzado($formacion['id_usuario']);
            $formacion['puntaje_categoria_actual'] = $puntajeValidator->obtenerPuntajeActual($formacion['id_usuario'], $categoria);
            $formacion['puntaje_total_actual'] = $puntajeValidator->obtenerPuntajeTotal($formacion['id_usuario']);
            
            $formacion = $this->agregarInformacionEstado($formacion, $puntajeValidator, $categoria);
            
            $todasLasSolicitudes[] = $formacion;
        }
        
        // Procesar INVESTIGACIONES
        foreach ($investigaciones as &$investigacion) {
            $categoria = 'investigaciones';
            $investigacion['categoria'] = $categoria;
            $investigacion['tabla'] = 'investigaciones';
            $investigacion['id_merito'] = $investigacion['id_investigacion']; // Normalizar ID
            
            $investigacion['categoria_limite_alcanzado'] = $puntajeValidator->categoriaLimiteAlcanzado($investigacion['id_usuario'], $categoria);
            $investigacion['limite_total_alcanzado'] = $puntajeValidator->limiteTotalAlcanzado($investigacion['id_usuario']);
            $investigacion['puntaje_categoria_actual'] = $puntajeValidator->obtenerPuntajeActual($investigacion['id_usuario'], $categoria);
            $investigacion['puntaje_total_actual'] = $puntajeValidator->obtenerPuntajeTotal($investigacion['id_usuario']);
            
            $investigacion = $this->agregarInformacionEstado($investigacion, $puntajeValidator, $categoria);
            
            $todasLasSolicitudes[] = $investigacion;
        }

        return new ViewModel([
            "data" => $this->authService->getIdentity()->getData(), 
            "premios" => $premios, 
            "cargos" => $cargos, 
            "capacitacion" => $capacitacionList, 
            "formacion" => $formacionList, 
            "investigaciones" => $investigaciones,
            "categoriaActual" => $categoriaFiltro,
            "periodoActivo" => $periodoActivo[0],
            "puntajeValidator" => $puntajeValidator, // Para usar en la vista si es necesario
            "todasLasSolicitudes" => $todasLasSolicitudes // Array unificado con toda la información
        ]);
    }

    /**
     * Método auxiliar para agregar información de estado y tooltips
     */
    private function agregarInformacionEstado($solicitud, $puntajeValidator, $categoria) {
        // Determinar color del badge y tooltip según el estado
        switch($solicitud['nombre_estado']) {
            case 'Ingresada - Límite Alcanzado':
                $solicitud['color'] = 'secondary';
                $solicitud['tooltip'] = "Este docente ya alcanzó el límite de {$puntajeValidator::LIMITES[$categoria]} puntos en esta categoría";
                break;
            case 'Ingresada - Sin Efecto':
                $solicitud['color'] = 'dark';
                $solicitud['tooltip'] = 'Este docente ya alcanzó el límite total de 30 puntos';
                break;
            case 'Ingresada':
                $solicitud['color'] = 'info';
                $solicitud['tooltip'] = 'Solicitud pendiente de calificación';
                break;
            case 'Aceptada':
                $solicitud['color'] = 'success';
                $solicitud['tooltip'] = 'Solicitud aceptada y puntos otorgados';
                break;
            case 'Rechazada':
                $solicitud['color'] = 'danger';
                $solicitud['tooltip'] = 'Solicitud rechazada';
                break;
            default:
                $solicitud['color'] = $solicitud['color'] ?? 'secondary';
                $solicitud['tooltip'] = '';
        }
        
        // Agregar información adicional para mostrar en la vista
        $solicitud['limite_categoria'] = $puntajeValidator::LIMITES[$categoria];
        $solicitud['limite_total'] = $puntajeValidator::LIMITE_TOTAL;
        
        return $solicitud;
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
                $this->manejarCambioPuntosFormacion($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado);
                
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

        if ($this->params()->fromPost("action") == "rechazar") {
            $params = $this->params()->fromPost();
            $params['id_estado'] = '3';

            $user = $userTable->getUserById($params['id_usuario']);

            unset($params['id_usuario']);
            unset($params['action']);

            $result = $formacionTable->update($params, ["id_formacion_academica" => $id_solicitud]);
                
            if ($result > 0) {
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

    // Método auxiliar para manejar puntos de FORMACIÓN ACADÉMICA
    private function manejarCambioPuntosFormacion($puntosTable, $id_usuario, $puntosActuales, $estadoAnterior, $nuevoEstado) {
        $year = date("Y");
        $misPuntos = $puntosTable->getPuntosByUser($id_usuario);
        $puntosUsuario = $misPuntos ? floatval($misPuntos[0]["formacion_academica"]) : 0;

        // Si cambia de Aceptada (2) a Rechazada (3) o Ingresada (1) -> QUITAR puntos
        if ($estadoAnterior == 2 && in_array($nuevoEstado, [1, 3])) {
            $nuevosPuntos = max(0, $puntosUsuario - $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["formacion_academica" => $nuevosPuntos, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            }
        }
        
        // Si cambia de Rechazada (3) o Ingresada (1) a Aceptada (2) -> AGREGAR puntos
        if (in_array($estadoAnterior, [1, 3]) && $nuevoEstado == 2) {
            // Para formación académica se toma el mayor valor, no se suman
            $nuevoPuntaje = max($puntosUsuario, $puntosActuales);
            
            if ($misPuntos) {
                $puntosTable->update(
                    ["formacion_academica" => $nuevoPuntaje, "year" => $year],
                    ["id_usuario" => $id_usuario]
                );
            } else {
                $puntosTable->insert([
                    "formacion_academica" => $puntosActuales,
                    "id_usuario" => $id_usuario,
                    "year" => $year
                ]);
            }
        }
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

    // En IndexController.php
    public function cambiarEstadoMeritoAction() {
        $id_merito = $this->params()->fromPost('id_merito');
        $tabla = $this->params()->fromPost('tabla');
        $nuevo_estado = $this->params()->fromPost('estado');
        
        // Obtener datos del mérito
        $sql = "SELECT id_usuario FROM {$tabla} WHERE id = ?";
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_merito]);
        $merito = $result->current();
        
        if (!$merito) {
            return new \Laminas\View\Model\JsonModel(['exito' => false, 'mensaje' => 'Mérito no encontrado']);
        }
        
        // Obtener ID del nuevo estado
        $sqlEstado = "SELECT id_estado FROM estado WHERE nombre_estado = ?";
        $stmtEstado = $this->adapter->createStatement($sqlEstado);
        $resultEstado = $stmtEstado->execute([$nuevo_estado]);
        $estadoRow = $resultEstado->current();
        $idEstado = $estadoRow['id_estado'];
        
        // Actualizar el mérito
        $sqlUpdate = "UPDATE {$tabla} SET id_estado = ? WHERE id = ?";
        $stmtUpdate = $this->adapter->createStatement($sqlUpdate);
        $stmtUpdate->execute([$idEstado, $id_merito]);
        
        // IMPORTANTE: Recalcular todos los estados del docente después del cambio
        $puntajeValidator = new \Meritos\Services\PuntajeValidatorService($this->adapter);
        $puntajeValidator->recalcularEstadosDocente($merito['id_usuario']);
        
        return new \Laminas\View\Model\JsonModel(['exito' => true, 'mensaje' => 'Estado actualizado correctamente']);
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

    /**
     * Valida si un período tiene méritos asociados
     * @param int $periodoId
     * @return bool
     */
    private function validarMeritosPeriodo($periodoId)
    {
        $tablas = ['formacion_academica', 'premios', 'cargos', 'investigaciones', 'capacitacion_profesional'];
        
        foreach ($tablas as $tabla) {
            try {
                $sql = "SELECT 1 FROM {$tabla} WHERE id_periodo = ? LIMIT 1";
                $statement = $this->adapter->createStatement($sql);
                $result = $statement->execute([$periodoId]);
                
                // Si encuentra al menos 1 registro, tiene méritos
                if ($result->current()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Si hay error con una tabla, continúa con las demás
                continue;
            }
        }
        
        return false;
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
                        $tieneMeritos = false;
                        $detalleMeritos = [];
                        $totalMeritos = 0;
                        
                        // Verificar en todas las tablas de méritos
                        $tablas = [
                            'formacion_academica' => 'Formación Académica',
                            'premios' => 'Premios',
                            'cargos' => 'Cargos',
                            'investigaciones' => 'Investigaciones',
                            'capacitacion_profesional' => 'Capacitación Profesional'
                        ];
                        
                        foreach ($tablas as $tabla => $categoria) {
                            try {
                                // Crear la consulta para cada tabla
                                $sql = "SELECT COUNT(*) as count FROM {$tabla} WHERE id_periodo = ?";
                                $statement = $this->adapter->createStatement($sql);
                                $result = $statement->execute([$periodo_id]);
                                $row = $result->current();
                                
                                if ($row['count'] > 0) {
                                    $tieneMeritos = true;
                                    $detalleMeritos[] = "{$categoria}: {$row['count']} mérito(s)";
                                    $totalMeritos += $row['count'];
                                }
                            } catch (\Exception $e) {
                                // Continuar con la siguiente tabla si hay error
                                continue;
                            }
                        }
                        
                        if ($tieneMeritos) {
                            // Construir mensaje detallado
                            $mensajeDetalle = implode('<br>• ', $detalleMeritos);
                            $mensajeCompleto = "No se puede eliminar este período porque tiene <strong>{$totalMeritos} mérito(s)</strong> asociado(s):<br><br>• {$mensajeDetalle}<br><br>Solo se pueden eliminar períodos sin méritos asociados.";
                            
                            $this->flashMessenger()->addErrorMessage($mensajeCompleto);
                            $this->saveLog($id_admin, "Intento fallido de eliminar período ID: {$periodo_id} - Tiene {$totalMeritos} méritos asociados");
                        } else {
                            // Si no tiene méritos, proceder con la eliminación
                            $sql = $periodosTable->getSql();
                            $update = $sql->update();
                            $update->set(['estado' => 'eliminado']);
                            $update->where(['id_periodo' => $periodo_id]);
                            $statement = $sql->prepareStatementForSqlObject($update);
                            $result = $statement->execute();
                            
                            $this->saveLog($id_admin, 'Se eliminó el período ID: ' . $periodo_id);
                            $this->flashMessenger()->addSuccessMessage('Período eliminado exitosamente.');
                        }
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

        // Validar méritos asociados para cada período
        foreach ($periodos as &$periodo) {
            $periodo['tiene_meritos'] = $this->validarMeritosPeriodo($periodo['id_periodo']);
            $periodo['puede_eliminar'] = !$periodo['tiene_meritos'];
        }

        return new ViewModel([
            'data' => $this->authService->getIdentity()->getData(),
            'periodos' => $periodos,
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

    /**
     * Obtener años disponibles en el sistema
     */
    private function obtenerAñosDisponibles() {
        $tablas = ['premios', 'cargos', 'capacitacion_profesional', 'formacion_academica', 'investigaciones'];
        $años = [];

        foreach ($tablas as $tabla) {
            try {
                $sql = "SELECT DISTINCT YEAR(created_at) as año FROM {$tabla} WHERE created_at IS NOT NULL ORDER BY año DESC";
                $statement = $this->adapter->createStatement($sql);
                $result = $statement->execute();
                
                foreach ($result as $row) {
                    if ($row['año'] && !in_array($row['año'], $años)) {
                        $años[] = $row['año'];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        rsort($años);
        
        if (empty($años)) {
            $añoActual = date('Y');
            for ($i = $añoActual; $i >= 2024; $i--) {
                $años[] = $i;
            }
        }
        
        return $años;
    }

    /* Vista historial */
    public function historialAction(){
        if (!$this->authService->hasIdentity() || !$this->authService->getIdentity() instanceof \Auth\Model\AuthEntity || !$this->authService->getIdentity()->isAutenticado() || $this->authService->getIdentity()->getRol() != 'admin') {
            return $this->redirect()->toRoute('home');
        }
        
        $this->layout()->setTemplate('layout/layoutAdmon');
        $this->layout()->setVariable('userAuth', $this->authService->getIdentity());

        // Obtener filtros
        $añoFiltro = $this->params()->fromQuery('año', date('Y'));
        $estadoFiltro = $this->params()->fromQuery('estado', 'todos');
        $categoriaFiltro = $this->params()->fromQuery('categoria', 'todas');

        // Obtener años disponibles
        $añosDisponibles = $this->obtenerAñosDisponibles();

        // Convertir estado a ID
        $estadoId = null;
        if ($estadoFiltro !== 'todos') {
            switch(strtolower($estadoFiltro)) {
                case 'ingresada':
                    $estadoId = 1;
                    break;
                case 'aceptada':
                    $estadoId = 2;
                    break;
                case 'rechazada':
                    $estadoId = 3;
                    break;
            }
        }

        // Obtener méritos según filtros
        $premios = [];
        $cargos = [];
        $formacion = [];
        $capacitacion = [];
        $investigaciones = [];

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'premios') {
            $premiosTable = new \ORM\Model\Entity\PremiosTable($this->adapter);
            $premios = $premiosTable->getPremiosAño($añoFiltro, $estadoId);
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'cargos') {
            $cargosTable = new \ORM\Model\Entity\CargosTable($this->adapter);
            $cargos = $cargosTable->getCargosAño($añoFiltro, $estadoId);
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'formacion') {
            $formacionTable = new \ORM\Model\Entity\FormacionAcademicaTable($this->adapter);
            $formacion = $formacionTable->getFormacionAcademicaAño($añoFiltro, $estadoId);
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'capacitacion') {
            $capacitacionTable = new \ORM\Model\Entity\CapacitacionProfesionalTable($this->adapter);
            $capacitacion = $capacitacionTable->getCapacitacionAño($añoFiltro, $estadoId);
        }

        if ($categoriaFiltro === 'todas' || $categoriaFiltro === 'investigaciones') {
            $investigacionesTable = new \ORM\Model\Entity\InvestigacionesTable($this->adapter);
            $investigaciones = $investigacionesTable->getInvestigacionesAño($añoFiltro, $estadoId);
        }

        return new ViewModel([
            "data" => $this->authService->getIdentity()->getData(),
            "premios" => $premios,
            "cargos" => $cargos,
            "formacion" => $formacion,
            "capacitacion" => $capacitacion,
            "investigaciones" => $investigaciones,
            "añoActual" => $añoFiltro,
            "estadoActual" => $estadoFiltro,
            "categoriaActual" => $categoriaFiltro,
            "añosDisponibles" => $añosDisponibles
        ]);
    }

}
