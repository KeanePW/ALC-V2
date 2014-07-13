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

final class cookie {
    private static $cname = "";
    private static $val = array();
    private static $expires;
    private static $dir = '/';
    private static $site = '';

    /**
    * Setzt die Werte für ein Cookie und erstellt es.
    */
    public final static function init($cname, $cexpires=false, $cdir="/", $csite="") {
        global $mysql_salt;
        self::$cname=$cname;
        self::$expires = ($cexpires ? $cexpires : (time()+cookie_expires));
        self::$dir=$cdir;
        self::$site=$csite;
        self::$val=array();
        self::extract();
    }

    /**
    * Extraktiert ein gespeichertes Cookie
    */
    public final static function extract($cname="") {
        global $mysql_salt;
        $cname=(empty($cname) ? self::$cname : $cname);
        if(!empty($_COOKIE[$cname])) {
            $arr = unserialize(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cname]) : $_COOKIE[$cname]);
            if($arr!==false && is_array($arr)) {
                foreach($arr as $var => $val) {
                    $_COOKIE[$var]=$val;
                }
            }

            self::$val=$arr;
        }

        unset($_COOKIE[$cname]);
    }

    /**
    * Liest und gibt einen Wert aus dem Cookie zurück
    *
    * @return string
    */
    public final static function get($var) {
        global $mysql_salt;
        if(!isset(self::$val) || empty(self::$val))
            return false;

        if(!array_key_exists($var, self::$val))
            return false;

        return utf8_decode(self::$val[$var]);
    }

    /**
    * Setzt ein neuen Key und Wert im Cookie
    */
    public final static function put($var, $value) {
        global $mysql_salt;
        self::$val[$var]=utf8_encode($value);
        $_COOKIE[$var]=self::$val[$var];
        if(empty($value)) unset(self::$val[$var]);
    }

    /**
    * Leert das Cookie
    */
    public final static function clear()
    { self::$val=array(); self::save(); }

    /**
    * Speichert das Cookie
    */
    public final static function save() {
        global $mysql_salt;
        $cookie_val = (empty(self::$val) ? '' : serialize(self::$val));
        if(strlen($cookie_val)>4*1024)
            trigger_error("The cookie ".self::$cname." exceeds the specification for the maximum cookie size.  Some data may be lost", E_USER_WARNING);

        setcookie(self::$cname, $cookie_val, self::$expires, self::$dir, self::$site);
    }
}