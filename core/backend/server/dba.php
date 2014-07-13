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

class dba {
    private static $dba = array();

    public static final function init($active = "default") {
        global $db_array;
        foreach ($db_array as $dba_key => $dba_val) {
            self::$dba[$dba_key] = db::getPrefix($active).$dba_val; // Add prefix
        }
    }

    public static function get($tag = '') {
        if(empty($tag) || $tag == false || !array_key_exists($tag, self::$dba)) return '';
        return self::$dba[$tag];
    }

    public static function set($tag = '', $table = '', $active = "default") {
        if(array_key_exists($tag, self::$dba))
            return false;

        self::$dba[$tag] = db::getPrefix($active).$table; // Add prefix
        return true;
    }

    //array(array('test' => 'test123'),array('dl' => 'downloads'));
    public static function set_array($array = array(), $active = "default") {
        if(!is_array($array) || !count($array))
            return false;

        $i=0;
        foreach($array as $dba_key => $dba_val) {
            if(array_key_exists($dba_key, self::$dba)) continue;
            self::$dba[$dba_key] = db::getPrefix($active).$dba_val; // Add prefix
            $i++;
        }

        return $i >= 1 ? true : false;
    }

    public static function replace($tag = '', $new_table = '', $active = "default") {
        if(!array_key_exists($tag, self::$dba))
            return false;

        self::$dba[$tag] = db::getPrefix($active).$new_table; // Add prefix
        return true;
    }
}