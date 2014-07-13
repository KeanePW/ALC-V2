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

class settings {
    protected static $index = array();

    /**
     * Gibt eine Einstellung aus der Settings Tabelle zurück
     * @param string $what
     * @return string|int|boolean
    */
    public final static function get($what='') {
        $what = strtolower($what);
        if(self::is_exists($what)) {
            $data = self::$index[$what];
            return $data['value'];
        }
        else
            Debugger::insert_error('settings::get()', 'Setting "'.$what.'" not found in '.dba::get('settings'));

        return false;
    }

    /**
     * Gibt mehrere Einstellungen aus der Settings Tabelle zurück
     * @param string $what
     * @return array|boolean
     */
    public final static function get_array($what=array()) {
        if(!is_array($what) || !count($what) || empty($what))
            return false;

        $return = array();
        foreach ($what as $key) {
            $key = strtolower($key);
            if(array_key_exists($key, self::$index)) {
                $data = self::$index[$key];
                $return[$key] = $data['value'];
                $data = array();
            }
        }

        if(count($return) >= 1) return $return;
        return false;
    }

    /**
     * Gibt die Standard Einstellung einer Einstellung zurück
     * @param string $what
     * @return mixed|boolean
     */
    public final static function get_default($what='') {
        $what = strtolower($what);
        if(self::is_exists($what)) {
            $data = self::$index[$what];
            return $data['default'];
        }
        else
            Debugger::insert_error('settings::get_default()', 'Setting "'.$what.'" not found in '.dba::get('settings'));

        return false;
    }

    /**
     * Aktualisiert die Werte innerhalb der Settings Tabelle
     * @param string $what
     * @param string $var
     * @return boolean
     */
    public final static function set($what='',$var='', $default=true) {
        global $system_db;
        $what = strtolower($what);
        if(self::is_exists($what)) {
            if(self::changed($what,$var)) {
                $var = empty($var) && $default ? self::get_default($what) : $var;
                $data = self::$index[$what];
                $data['value'] = ($data['length'] >= 1 ? core::cut($var,((int)$data['length']),false) : $var);
                self::$index[$what] = $data;
                Debugger::insert_successful('settings::set()', 'Set "'.$what.'" to "'.$var.'"');
                $value = ($data['length'] >= 1 ? core::cut($var,((int)$data['length']),false) : $var);
                return $system_db->update("UPDATE `".dba::get('settings')."` SET `value` = ? WHERE `key` = ?;",array(utf8_encode($value),$what));
            }
        }

        return false;
    }

    /**
     * Vergleicht den Aktuellen Wert mit dem neuen Wert ob ein Update erforderlich ist
     * @param string $what
     * @param string $var
     * @return boolean
     */
    public final static function changed($what='',$var='') {
        if(debug_config_reset) return true;
        $what = strtolower($what);
        if(self::is_exists($what)) {
            $data = self::$index[$what];
            return ($data['value'] == $var ? false : true);
        }

        return false;
    }

    /**
     * Prüft ob ein Key existiert
     * @param string $what
     * @return boolean
     */
    public final static function is_exists($what='')
    { return (array_key_exists(strtolower($what), self::$index)); }

    /**
     * Laden der Einstellungen aus der Datenbank
     */
    public final static function load() {
        global $system_db;
        Debugger::insert_initialize('settings::load()', 'Settings - Load');
        $foreach = $system_db->select_foreach("SELECT `key`,`value`,`default`,`length`,`type` FROM `".dba::get('settings')."`");
        foreach ($foreach as $get) {
            $setting = array();
            $setting['value'] = !((int)$get['length']) ? $get['type'] == 'int' ? ((int)$get['value']) : utf8_decode($get['value'])
            : core::cut($get['type'] == 'int' ? ((int)$get['value']) : utf8_decode($get['value']),((int)$get['length']),false);
            $setting['default'] = $get['type'] == 'int' ? ((int)$get['default']) : utf8_decode($get['default']);
            $setting['length'] = ((int)$get['length']);
            self::$index[$get['key']] = $setting;
        }

        Debugger::insert_successful('settings::load()', $system_db->rowCount().' settings loaded');
    }

    /**
     * Eine neue Einstellung in die Datenbank schreiben
     * @param string $what
     * @param string/int $var
     * @param string/int $default
     * @param int $length
     * @param boolean $int
     * @return boolean
     */
    public final static function add($what='',$var='',$default='',$length='',$int=false) {
        global $system_db;
        $what = strtolower($what);
        if(!self::is_exists($what)) {
            $setting = array();
            $setting['value'] = !((int)$length) ? $int ? ((int)$var) : ((string)$var)
            : core::cut($int ? ((int)$var) : ((string)$var),((int)$length),false);
            $setting['default'] = $int ? ((int)$default) : ((string)$default);
            $setting['length'] = ((int)$length);
            self::$index[$what] = $setting;

            Debugger::insert_successful('settings::add()', 'Add "'.$what.'" set to "'.$var.'"');
            return $system_db->insert("INSERT INTO `".dba::get('settings')."` SET `key` = ?, `value` = ?, `default` = ?, `length` = ?, `type` = ?;",
            array($what,($int ? $var : utf8_encode($var)),($int ? $default : utf8_encode($default)),$length,($int ? 'int' : 'string')));
        }

        return false;
    }

    /**
     * Löscht eine Einstellung aus der Datenbank
     * @param string $what
     * @return boolean
     */
    public final static function remove($what='') {
        global $system_db;
        $what = strtolower($what);
        if(self::is_exists($what)) {
            Debugger::insert_info('settings::remove()', 'Remove "'.$what.'"');
            unset(self::$index[$what]);
            return $system_db->delete("DELETE FROM `".dba::get('settings')."` WHERE `key` = ?;",array($what));
        }

        return false;
    }
}