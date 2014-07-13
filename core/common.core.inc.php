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

/* block attempts to directly run this script */
if (getcwd() == dirname(__FILE__)) {
    die('block directly run');
}

/* Minimum PHP Version */
if (version_compare(PHP_VERSION, '5.4.0', 'lt')) {
    die('PHP 5.4+ is required');
}

/* Set basePath & RunDZCP */
define('RunALC',true);
define('basePath', dirname(dirname(__FILE__).'../').'/');
date_default_timezone_set("Europe/Berlin");

require_once basePath . 'core/config.php';
require_once basePath . 'core/config.mysql.salt.php';

/**
 *  ------------------------
 *  -- CMS Core functions --
 *  ------------------------
 */
class core {
    protected static $buffer = array();

    /**
     * Eine Liste der Dateien oder Verzeichnisse zusammenstellen, die sich im angegebenen Ordner befinden.
     * @category HMP-Core Functions
     * @param string $dir = '/test/dir'
     * @param boolean $only_dir = false
     * @param boolean $only_files = false
     * @param array $file_ext = array('php');
     * @param boolean or string $preg_match = ""
     * @param array $blacklist = array('badfile');
     * @return boolean or array
     */
    public static function getFiles($dir=null,$only_dir=false,$only_files=false,$file_ext=array(),$preg_match=false,$blacklist=array()) {
        $files = array(); $hash = md5($dir.$only_dir.$only_files.count($file_ext).$preg_match.count($blacklist));
        if(!file_exists($dir) && !is_dir($dir)) {
            return $files;
        }

        try {
            if(!self::RTBcheck($hash)) {
                return self::RTBget($hash);
            }
        } catch (Exception $e) {
            echo 'Exception RTBget || RTBcheck: ',  $e->getMessage(), "\n";
        }

        $handle = opendir($dir);
        if($handle) {
            if($only_dir) {
                while(false !== ($file = readdir($handle))) {
                    if($file != '.' && $file != '..' && !is_file($dir.'/'.$file)) {
                        if(!count($blacklist) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                            $files[] = $file;
                        } else {
                            if(!in_array($file, $blacklist) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                                $files[] = $file;
                            }
                        }
                    }
                }
            } else if($only_files) {
                while(false !== ($file = readdir($handle))) {
                    if($file != '.' && $file != '..' && is_file($dir.'/'.$file)) {
                        if(!in_array($file, $blacklist) && !count($file_ext) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                            $files[] = $file;
                        } else {
                            $exp_string = array_reverse(explode(".", $file));
                            if(!in_array($file, $blacklist) && in_array(strtolower($exp_string[0]), $file_ext) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                                $files[] = $file;
                            }
                        }
                    }
                }
            } else {
                while(false !== ($file = readdir($handle))) {
                    if($file != '.' && $file != '..' && is_file($dir.'/'.$file)) {
                        if(!in_array($file, $blacklist) && !count($file_ext) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                            $files[] = $file;
                        } else {
                            $exp_string = array_reverse(explode(".", $file));
                            if(!in_array($file, $blacklist) && in_array(strtolower($exp_string[0]), $file_ext) && ($preg_match ? preg_match($preg_match,$file) : true)) {
                                $files[] = $file;
                            }
                        }
                    } else {
                        if(!in_array($file, $blacklist) && $file != '.' && $file != '..' && ($preg_match ? preg_match($preg_match,$file) : true)) {
                            $files[] = $file;
                        }
                    }
                }
            }

            if(is_resource($handle)) {
                closedir($handle);
            }

            if(!count($files)) {
                return false;
            }

            try {
                self::RTBset($hash,$files);
            } catch (Exception $e) {
                echo 'Exception RTBset: ',  $e->getMessage(), "\n";
            }
            return $files;
        }

        return false;
    }

    /**
     * Generiert eine zufällige Zahl
     * @param integer $min * Der optionale niedrigste Wert
     * @param integer $max * Der optionale höchste Wert
     * @return number
     */
    public static function rand($min = 0, $max = 0) {
        if ($max AND $max <= mt_getrandmax()) {
            $number = mt_rand($min, $max);
        }
        else {
            $number = mt_rand();
        }

        mt_srand();
        return $number;
    }

    /**
     * Gibt die IP des Besuchers / Users zurück
     * @return IP
     */
    public static function visitorIp() {
        $TheIp=$_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $TheIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            $TheIp = $_SERVER['HTTP_CLIENT_IP'];
        }

        if(isset($_SERVER['HTTP_FROM']) && !empty($_SERVER['HTTP_FROM'])) {
            $TheIp = $_SERVER['HTTP_FROM'];
        }

        $TheIp_X = explode('.',$TheIp);
        if(count($TheIp_X) == 4 && $TheIp_X[0]<=255 && $TheIp_X[1]<=255 && $TheIp_X[2]<=255 && $TheIp_X[3]<=255 && preg_match("!^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$!",$TheIp)) {
            return trim($TheIp);
        }

        return '0.0.0.0';
    }

    /**
     * Funktion um eine Variable Prüfung in einem Array durchzuführen
     * @return boolean
     */
    public static function array_var_exists($var,$search) {
        foreach($search as $key => $var_) {
            if($var_==$var) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runtime Buffer
     * Funktion um Werte kurzzeitig zu speichern.
     */
    public static final function RTBset($tag='',$data='',$time=1) {
        self::$buffer[$tag]['ttl'] = (time()+$time);
        self::$buffer[$tag]['data'] = json_encode($data,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    }

    public static final function RTBget($tag) {
        return (isset(self::$buffer[$tag]) ? json_decode(self::$buffer[$tag]['data']) : false);
    }

    public static final function RTBcheck($tag) {
        if(!isset(self::$buffer[$tag])) {
            return true;
        }
        else if(self::$buffer[$tag]['ttl'] < time()) {
            unset(self::$buffer[$tag]['data']);
            unset(self::$buffer[$tag]['ttl']);
            return true;
        } else {
            return false;
        }
    }

    public static final function encrypt($data, $key) {
        global $mysql_salt;
        $key = substr(hash('sha256', $mysql_salt.$key.$mysql_salt), 0, 32);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv));
    }

    public static final function decrypt($data, $key) {
        global $mysql_salt;
        $key = substr(hash('sha256', $mysql_salt.$key.$mysql_salt), 0, 32);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($data), MCRYPT_MODE_ECB, $iv);
    }

    /**
     * Generiert ein zufälliges Passwort
     * @param integer $length * Die Länge des Passworts
     * @return password
     */
    public static function random_password($passwordLength=8, $specialcars=true) {
        $passwordComponents = array("ABCDEFGHIJKLMNOPQRSTUVWXYZ" , "abcdefghijklmnopqrstuvwxyz" , "0123456789" , "#$@!");
        $componentsCount = count($passwordComponents);

        if(!$specialcars && $componentsCount == 4) {
            unset($passwordComponents[3]);
            $componentsCount = count($passwordComponents);
        }

        shuffle($passwordComponents); $password = '';
        for ($pos = 0; $pos < $passwordLength; $pos++) {
            $componentIndex = ($pos % $componentsCount);
            $componentLength = strlen($passwordComponents[$componentIndex]);
            $random = core::rand(0, $componentLength-1);
            $password .= $passwordComponents[$componentIndex]{ $random };
        }

        return $password;
    }

    /**
     * Kuerzt Strings auf ein bestimmte Zeichenfolge
     * @param string $str
     * @param number $length
     * @param boolean $dots
     * @return string
     */
    public static function cut($str, $length = 0, $dots = true) {
        if($length == 0) return '';

        $start = 0;
        $dots = ($dots == true && strlen(html_entity_decode($str)) > $length) ? '...' : '';

        if(strpos($str, '&') === false)
            return (($length === null) ? substr($str, $start) : substr($str, $start, $length)).$dots;

        $chars = preg_split('/(&[^;\s]+;)|/', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
        $html_length = count($chars);

        if(($start >= $html_length) || (isset($length) && ($length <= -$html_length)))
            return '';

        if($start >= 0)
            $real_start = $chars[$start][1];
        else {
            $start = max($start,-$html_length);
            $real_start = $chars[$html_length+$start][1];
        }

        if (!isset($length))
            return substr($str, $real_start).$dots;
        else if($length > 0)
            return (($start+$length >= $html_length) ? substr($str, $real_start) : substr($str, $real_start, $chars[max($start,0)+$length][1] - $real_start)).$dots;
        else
            return substr($str, $real_start, $chars[$html_length+$length][1] - $real_start).$dots;
    }
}

require_once basePath . 'core/backend/backend.inc.php'; //Add Backend
require_once basePath . 'core/frontend/frontend.inc.php'; //Add Frontend
$frontend = new frontend(); unset($frontend); //__construct

/* User aktionen ausführen */
if(backend::loggedin()) {

    $new_msg_count = 0; //DEV -> Summe neuer Nachrichten
    $new_updates_count = 0; //DEV -> Summe neuer Ereignisse

    /** New messages & updates */
    output::set('new_msg_count',convert::ToString($new_msg_count));
    output::set('new_updates_count',convert::ToString($new_updates_count));
    output::set('new_msg_disable_r',(!$new_msg_count ? '-->' : ''));
    output::set('new_msg_disable_l',(!$new_msg_count ? '<!--' : ''));
    output::set('new_updates_disable_r',(!$new_updates_count ? '-->' : ''));
    output::set('new_updates_disable_l',(!$new_updates_count ? '<!--' : ''));
}

/* Include Module */
if(!empty($index) && file_exists(basePath . 'modules/'.$index.'/main.php')) {
    require_once basePath . 'modules/'.$index.'/main.php';
}

$breadcrumb = '<li><a href="#">Home</a></li>
            <li><a href="#">Library</a></li>
            <li class="active">Data</li>';
output::set('breadcrumb',$breadcrumb);

$side_title = 'NEWS';
output::set('side_title',$side_title);
