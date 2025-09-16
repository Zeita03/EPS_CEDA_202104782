<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utilidades\Service;


/**
 * Description of MailManager
 *
 * @author eliel
 */
class FileManager {


    public function file_name($string) {
        // Tranformamos todo a minusculas
        $string = strtolower($string);
    
        //Rememplazamos caracteres especiales latinos
        $find = array('á', 'é', 'í', 'ó', 'ú', 'ñ');
        $repl = array('a', 'e', 'i', 'o', 'u', 'n');
        $string = str_replace($find, $repl, $string);
    
        // Añadimos los guiones
        $find = array(' ', '&', '\r\n', '\n', '+');
        $string = str_replace($find, '-', $string);
    
        // Eliminamos y Reemplazamos otros carácteres especiales
        $find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
        $repl = array('', '-', '');
        $string = preg_replace($find, $repl, $string);
    
        return $string;
    }


    public function uploadFile($file_name){
        $new_name_file = null;
        $encripted_name = null;
        if ($file_name != '' || $file_name != null) {
            $file_type = $_FILES['subir_archivo']['type'];
            list($type, $extension) = explode('/', $file_type);
            //var_dump($extension);
            if ($extension == 'pdf') {
                $directorio = 'archivos/';
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }
                $file_tmp_name = $_FILES['subir_archivo']['tmp_name'];
                $x = substr($file_name, 0, strrpos($file_name, '.'));
                $new_name_file =  $this->file_name($x) . date('Ymshis'); 
                //var_dump($new_name_file);
                $encripted_name = md5($new_name_file);

                if (copy($file_tmp_name, $directorio . $encripted_name . '.' . $extension)) {   
                }  
            }
        }
        return $encripted_name;
    }


}
