<?php
/**
Copyright (c) 2014, Revoplay.de Development
http://www.revoplay.de
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if (!defined('RunALC')) {
    exit;
}

define("EOL","\r\n");
final class Debugger {
    private static $log_array = array(array());
    private static $file_data = '';
    private static $templsys = '';

    public static final function initCon() {
        self::$log_array=array(array());
        self::$file_data='';
        self::insert_initialize('Debugger::initCon()','Debugger');
    }

    public static final function insert_log($file,$msg,$back=false,$func="",$line=0)
    { self::$log_array[$file][] = ($line != 0 ? 'Line:"'.$line.'" => ' : "").($back ? $msg.$func : $func.$msg); }

    public static final function insert_initialize($file,$func)
    { if(show_initialize) self::$log_array[$file][] = '<font color="#0000FF">Initialize '.$func.'</font>'; }

    public static final function insert_successful($file,$func)
    { self::$log_array[$file][] = '<font color="#009900">'.$func.'</font>'; }

    public static final function insert_error($file,$msg)
    { self::$log_array[$file][] = '<font color="#FF0000">'.$msg.'</font>'; }

    public static final function insert_loaded($file,$func)
    { if(show_loaded) self::$log_array[$file][] = '<font color="#009900">'.$func.' Loaded</font>'; }

    public static final function insert_info($file,$info)
    { if(show_info) self::$log_array[$file][] = '<font color="#9900CC">'.$info.'</font>'; }

    public static final function insert_warning($file,$func)
    { if(show_warning) self::$log_array[$file][] = '<font color="#FFFF00">'.$func.'</font>'; }

    public static final function sql_error_handler($query,$params,$getMessage) {
        global $mysql;
        $message = '#####################################################################'.EOL.
        '   Datum   = '.date("d.m.y H:i", time()).EOL.
        '   URL     = http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['PHP_self'].EOL.EOL.
        '   PDO-Query failed:'.EOL.
        '   QueryError   = '.$getMessage.EOL.EOL.
        '   Query   = '.$query.EOL.
        '   QueryParams   = '.print_r($params,true).EOL.
        '#####################################################################'.EOL.EOL;

        $fp = fopen(basePath."/core/_logs/sql_error.log", "a+");
        fwrite($fp, $message);
        fclose($fp);
    }

    public static final function save_log() {
        foreach(self::$log_array as $file => $msg_array)
        { foreach($msg_array as $msg) { self::$file_data .= strip_tags('"'.$file.'" => "'.$msg.'"')."\n"; } }
        file_put_contents(basePath . '/core/_logs/debug_'.date("s-i-h").'_'.date("d_m_Y").'.txt', self::$file_data);
    }

    public static final function show_logs() {
        if(!show_debug_console) return '';
        self::$templsys = new Template();
        $data = ''; $i=0;
        foreach(self::$log_array as $file => $msg_array) {
            foreach($msg_array as $msg) {
                $i++;
                $data .= self::$templsys->out('debugger/show_tr',array('id' => $i, 'code' => $file, 'msg' => $msg));
            }
        }

        if(!$i) return '';
        self::$templsys->load('debugger/show_log');
        self::$templsys->assign_array(array('log' => $data, 'count' => $i));
        return self::$templsys->out();
    }

    public static final function wire_log($input_level, $input_maxlevel=9, $input_file_name='', $input_content = "",$input_customlevel = "") {
        $file = basePath . "core/_logs/".date("Y-m-d",time(TRUE))."_".$input_file_name.".log";
        if ($input_maxlevel > 0) {
            $string =
            "#############################".EOL.
            "# <".$input_file_name.">-Logfile ".date("Y-m-d",time(TRUE))." #".EOL.
            "# ========================= #".EOL.
            "# File created at: ".date("H:i:s",time(TRUE))." #".EOL.
            "#############################".EOL.EOL;
            if (!file_exists($file)) {
                if (!$fileheader = fopen($file,"w")) {
                    $status["int"] = 3;
                    $status["str"] = "LOG_COULD_NOT_OPEN_FILE";
                    return $status;
                }

                if (!fwrite($fileheader,$string)) {
                    $status["int"] = 3;
                    $status["str"] = "LOG_COULD_NOT_WRITE_FILE";
                    return $status;
                }

                if (!fclose($fileheader)) {
                    $status["int"] = 4;
                    $status["str"] = "LOG_COULD_NOT_CLOSE_FILE";
                } else {
                    $status["int"] = 0;
                    $status["str"] = "LOG_OK";
                }
            }
        }

        switch($input_level)
        {

            /**** Wert:(OFF) ***********************************************************/
            case "off":
                $loglevel_int = 0;
                $loglevel_str = "";
                break;

                /**** Wert:ERROR ***********************************************************/
            case "error":
                $loglevel_int = 1;
                $loglevel_str = "ERROR";
                break;

                /**** Wert:SECURITY ********************************************************/
            case "security":
                $loglevel_int = 2;
                $loglevel_str = "SECURITY";
                break;

                /**** Wert:WARNING *********************************************************/
            case "warning":
                $loglevel_int = 3;
                $loglevel_str = "WARNING";
                break;

                /**** Wert:SESSION *********************************************************/
            case "session":
                $loglevel_int = 4;
                $loglevel_str = "SESSION";
                break;

                /**** Wert:STATUS **********************************************************/
            case "status":
                $loglevel_int = 5;
                $loglevel_str = "STATUS";
                break;

                /**** Wert:ACCESS **********************************************************/
            case "access":
                $loglevel_int = 6;
                $loglevel_str = "ACCESS";
                break;

                /**** Wert:CUSTOM1 *********************************************************/
            case "custom1":
                $loglevel_int = 7;
                $loglevel_str = "[C1:".$input_customlevel."]";
                break;

                /**** Wert:USTOM2 **********************************************************/
            case "custom2":
                $loglevel_int = 8;
                $loglevel_str = "[C2:".$input_customlevel."]";
                break;

                /**** Wert:DEBUG ***********************************************************/
            case "debug":
                $loglevel_int = 9;
                $loglevel_str = "DEBUG";
                break;

                /**** Wert:Off *************************************************************/
            default: // UNKNOWN
                $loglevel_int = 0;
                $loglevel_str = "";
                break;
        }

        if ($loglevel_int > 0 AND $loglevel_int <= $input_maxlevel) {
            $string = date("H:i:s",time(TRUE))." ". $_SERVER["REMOTE_ADDR"]." [".$loglevel_str."]: ".$input_content.EOL;

            if (!$fileheader = fopen($file,"a")) {
                $status["int"] = 2;
                $status["str"] = "LOG_COULD_NOT_OPEN_FILE";
                return $status;
            }

            if (!fwrite($fileheader,$string)) {
                $status["int"] = 3;
                $status["str"] = "LOG_COULD_NOT_WRITE";
                return $status;
            }

            if (!fclose($fileheader)) {
                $status["int"] = 4;
                $status["str"] = "LOG_COULD_NOT_CLOSE_FILE";
            } else {
                $status["int"] = 0;
                $status["str"] = "LOG_OK";
            }
        }
    }
}

function error_handler($code, $msg, $file, $line, $context)
{
    $file = str_replace(basePath, '', $file);
    switch ($code)
    {
        case E_WARNING:
        case E_USER_WARNING:
            Debugger::insert_log("<b>WARNUNG:' ".$file." '</b>", $msg, false, "", $line);
        break;
        case E_NOTICE:
        case E_USER_NOTICE:
            Debugger::insert_log("<b>HINWEIS:' ".$file." '</b>", $msg, false, "", $line);
        break;
        case E_DEPRECATED:
            if(show_deprecation_debug)
                Debugger::insert_log("<b>VERALTET:' ".$file." '</b>", $msg, false, "", $line);
            break;
        default:
            Debugger::insert_log("Unbekannt:' ".$file." ' [".$code."]", $msg, false, "", $line);
        break;
    }

    return true;
}