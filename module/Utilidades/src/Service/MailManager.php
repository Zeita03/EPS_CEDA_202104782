<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utilidades\Service;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Mail\Transport\SmtpOptions;

/**
 * Description of MailManager
 *
 * @author 
 */
class MailManager {


    public function getEmailConfig() {
        
        $config["connection_config"] = [];
        $config["host"] = 'smtp.gmail.com';
        $config["connection_class"] = 'login';
        $config["port"] = 587;
        $config["connection_config"]["ssl"] = 'tls';
        $config["connection_config"]["username"] = 'noresponder@farusac.edu.gt';
        $config["connection_config"]["password"] = 'syig phdg crmp ydxt';

        return $config;
    }

    public function sendGeneralMessage($to, $subject, $htmlMessage) {
        $general_mail_config = $this->getEmailConfig();

	$message = new Message();
        $message->setEncoding("UTF-8")
                ->addTo($to)
                ->addFrom('no-replay@plataforma.com', 'no-replay@plataforma.com')
                ->setReplyTo('no-replay@plataforma.com', 'No responder')
                ->setSender('no-replay@plataforma.com', 'No responder')
                ->setSubject($subject);

        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
        $options = new SmtpOptions($general_mail_config);

        

        //html
        $html_body = $htmlMessage;

        $html = new MimePart($html_body);
        $html->type = "text/html";
        $html->charset = "utf-8";

        //agregando las partes
        $body = new MimeMessage();
        $body->addPart($html);

        $message->setBody($body);

        $transport->setOptions($options);
        return $transport->send($message);
    }

}
