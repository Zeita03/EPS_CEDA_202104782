<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace DPPortada\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends \Utilidades\BaseAbstract\Controller\BaseAbstractActionController {

    private $authService;
    private $adapter;

    function __construct($authService, $adapter) {
        $this->authService = $authService;
        $this->adapter = $adapter;
    }
    
    public function getCodigoValidacion($id) {
        $strValidacion = $id . time() . $id . time() . $id . $id;
        return md5($strValidacion);
    }

    public function indexAction() {
        if ($this->authService->hasIdentity() && $this->authService->getIdentity() instanceof \Auth\Model\AuthEntity && $this->authService->getIdentity()->isAutenticado() && $this->authService->getIdentity()->getRol() == 'admin') {
            return $this->redirect()->toRoute('administracionHome/administracion', ["action" => "perfil"]);
        }
        //verificar si es un request post como autenticación
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            if ($data["usuario"] && $data["passwd"]) {
                $userManager = new \Auth\Service\AuthManager($this->authService, $this->adapter);
                $result = $userManager->login($data["usuario"], $data["passwd"], false, "admin");
                if ($result->getCode() == \Laminas\Authentication\Result::SUCCESS) {
                    return $this->redirect()->toRoute('administracionHome/administracion', ["action" => "perfil"]);
                } else {
                    $this->flashMessenger()->addErrorMessage('Hubo un error al iniciar sesión, revise sus credenciales o asegurese tener una cuenta activa');
                }
            } else {
                $this->flashMessenger()->addErrorMessage('Información incompleta');
            }
        }
        return new ViewModel();
    }


    public function homepageAction(){
        return new ViewModel();
    }

    public function logoutAction() {
        $this->authService->clearIdentity();
        $this->redirect()->toRoute('home');
    }

    public function recuperarpassAction() {
        //variable de control para una validación exitosa...
        $resultado = false;
        
        //actualizar información...
        if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            $usuarioTable = new \ORM\Model\Entity\UsuarioTable($this->adapter);
            $dataUsuario = $usuarioTable->select(["email" => $params["email"]])->toArray();
            if (empty($dataUsuario)) {
                $this->flashMessenger()->addErrorMessage('El correo no se encuentra registrado en la plataforma.');
            }
            $hash = $this->getCodigoValidacion($params["email"]);
            $nuevaClave = substr($hash, 4, 6);
            $bcrypt = new \Laminas\Crypt\Password\Bcrypt();
            $resultUpdate = $usuarioTable->update(["passwd" => $bcrypt->create($nuevaClave), "updated_at" => new \Laminas\Db\Sql\Expression('NOW()')], ["email" => $params["email"]]);

            if ($resultUpdate > 0) {
                $mailManager = new \Utilidades\Service\MailManager();
                /*$htmlMail = ("Estimado {$dataUsuario[0]["nombre"]}, 
                                                                        <br><br>Hemos recibido tu solicitud de reestablecer contraseña.
                                                                        <br><br>Se ha asignado la siguiente contraseña temporalmente:
                                                                        <br>{$nuevaClave}
                                                                        <br><br>Cualquier inconveniente no dudes en contactarnos.
                                                                        <br><br>Atentamente,
                                                                        ");*/

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
                                                                                    <span class="tinyMce-placeholder">Solicitud para cambiar contraseña</span>
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
                                                                                                style="font-size:16px;">Hola ' . $dataUsuario[0]["nombre"] . ':</span></p>
                                                                                        <p
                                                                                            style="margin:0;font-size:16px;mso-line-height-alt:18px">
                                                                                             </p>
                                                                                        <p style="margin:0;font-size:16px">
                                                                                            Hemos recibido tu solicitud de reestablecer contraseña.
                                                                                            
                                                                                        </p>
                                                                                        <p style="margin:0;font-size:16px">
                                                                                            Se le ha asignado la siguiente contraseña temporalmente: <b>' . $nuevaClave . '</b>
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

                $mailManager->sendGeneralMessage($dataUsuario[0]["email"], "CEDA - Restablecer Contraseña", $htmlMail);
                $this->flashMessenger()->addSuccessMessage("Se ha realizado el proceso de forma exitosa, por favor, verificar tu correo electrónico para encontrar la nueva contraseña.");
                $resultado = true;
            } else {
                $this->flashMessenger()->addErrorMessage("Ha ocurrido un error al intentar recuperar la contraseña.");
            }
        }

        return new ViewModel(["resultado" => $resultado]);
    }


    public function registerAction(){

        
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
            $tableUsuarioRol = new \ORM\Model\Entity\UsuarioRolTable($this->adapter);
            $result = $usuarioTable->insert($params);
            //$arrayDetalle = ['DOCENTE'];
            //$resultRol = $tableUsuarioRol->actualiarAsociados($params["usuario"], $arrayDetalle, '');
            if ($result > 0) {
                $result2 = $usuarioTable->getUserByEmail($params["email"]);
                $arrayDetalle = ['DOCENTE'];
                $resultRol = $tableUsuarioRol->actualiarAsociados($result2[0]['usuario'], $arrayDetalle, '');
                $this->flashMessenger()->addSuccessMessage('Usuario creado con éxito ');
            } else {
                $this->flashMessenger()->addErrorMessage('Ha ocurrido un error al intentar registrar el usuario.');
            }
        }

        return new ViewModel();
    }

}
