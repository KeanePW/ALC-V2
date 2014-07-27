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
if (!defined('RunALC')) { exit; }

class alc_player {
    private static $player = array();

    /* Public Functions */
    public static function get_player($uid=0) {
        global $system_db;
        if(!$uid) return false;
        $player = $system_db->select('SELECT * FROM `players` WHERE `uid` = ?',array($uid));
        if(!$system_db->rowCount()) return false;
        $player['cop_licenses'] = self::get_licenses($player['cop_licenses']);
        $player['civ_licenses'] = self::get_licenses($player['civ_licenses']);
        $player['med_licenses'] = self::get_licenses($player['med_licenses']);
        $player['cop_gear'] = self::get_gear($player['cop_gear']);
        $player['civ_gear'] = self::get_gear($player['civ_gear']);
        $player['aliases'] = self::get_gear($player['aliases']);
        self::$player = $player;
        return $player;
    }

    public static function set_player($uid=0,$player=array()) {
        global $system_db;
        if(!$uid || count($player) != 17) return false;

        $sql_update = '';
        /** Licenses Update **/
        if(self::$player['cop_licenses'] != $player['cop_licenses'])
            $sql_update .= "`cop_licenses` = '\"".self::generate_licenses($player['cop_licenses'])."\"', ";

        if(self::$player['civ_licenses'] != $player['civ_licenses'])
            $sql_update .= "`civ_licenses` = '\"".self::generate_licenses($player['civ_licenses'])."\"', ";

        if(self::$player['med_licenses'] != $player['med_licenses'])
            $sql_update .= "`med_licenses` = '\"".self::generate_licenses($player['med_licenses'])."\"', ";

        /** Gear Update **/
        if(self::$player['cop_gear'] != $player['cop_gear'])
            $sql_update .= "`cop_licenses` = '\"".self::generate_gear($player['cop_gear'])."\"', ";

        if(self::$player['civ_gear'] != $player['civ_gear'])
            $sql_update .= "`civ_gear` = '\"".self::generate_gear($player['civ_gear'])."\"', ";

        /** Player Update **/
        if(self::$player['name'] != $player['name'])
            $sql_update .= "`name` = '".$player['name']."', ";

        if(self::$player['playerid'] != $player['playerid'])
            $sql_update .= "`playerid` = ".((int)$player['playerid']).", ";

        if(self::$player['cash'] != $player['cash'])
            $sql_update .= "`cash` = ".((int)$player['cash']).", ";

        if(self::$player['bankacc'] != $player['bankacc'])
            $sql_update .= "`bankacc` = ".((int)$player['bankacc']).", ";

        if(self::$player['coplevel'] != $player['coplevel'])
            $sql_update .= "`coplevel` = ".((int)$player['coplevel']).", ";

        if(self::$player['mediclevel'] != $player['mediclevel'])
            $sql_update .= "`mediclevel` = ".((int)$player['mediclevel']).", ";

        if(self::$player['arrested'] != $player['arrested'])
            $sql_update .= "`arrested` = ".((int)$player['arrested']).", ";

        if(self::$player['aliases'] != $player['aliases'])
            $sql_update .= "`aliases` = '\"".self::generate_gear($player['aliases'])."\"', ";

        if(self::$player['adminlevel'] != $player['adminlevel'])
            $sql_update .= "`adminlevel` = ".((int)$player['adminlevel']).", ";

        if(self::$player['donatorlvl'] != $player['donatorlvl'])
            $sql_update .= "`donatorlvl` = ".((int)$player['donatorlvl']).", ";

        if(self::$player['blacklist'] != $player['blacklist'])
            $sql_update .= "`blacklist` = ".((int)$player['blacklist']).", ";

        if(!empty($sql_update))
            return $system_db->update("UPDATE `players` SET ".substr($sql_update, 0, -2)." WHERE `uid` = ?",array($uid));

        return false;
    }

    /* Private Functions */
    //[[`license_cop_air`,1],[`license_cop_swat`,0],[`license_cop_cg`,0]]
    private static function get_licenses($string='') {
        if($string == '"[]"') return array();
        $string = str_replace('],[', ']|[', substr($string, 2 , strlen($string)-4));
        $epl = explode('|', $string); $return = array();
        foreach ($epl as $string) {
            $string_exp = explode(',', $string);
            if(count($string_exp) >= 2) {
                $string_exp[0] = str_replace('`', '', substr($string_exp[0], 1 , strlen($string_exp[0])-1));

                if(!is_numeric($string_exp[1]))
                    $string_exp[1] = substr($string_exp[1], 0 , strlen($string_exp[1])-1);

                $return[$string_exp[0]] = $string_exp[1];
            }
        }

        return $return;
    }

    private static function generate_licenses($array=array()) {
        if(!count($array)) return '[]';
        $string = '';
        foreach ($array as $license => $var) {
            $string .= '[`'.$license.'`,'.$var.'],';
        }
        return '['.substr($string, 0, -1).']';
    }

    //[``,`hgun_P07_snds_F`,[`16Rnd_9x21_Mag`],`U_Rangemaster`,`V_Rangemaster_belt`,``,[`ItemMap`,`ItemCompass`],[],[],[`muzzle_snds_L`,``,``],[`16Rnd_9x21_Mag`,`16Rnd_9x21_Mag`],[`16Rnd_9x21_Mag`,`16Rnd_9x21_Mag`,`16Rnd_9x21_Mag`],[]]"
    private static function get_gear($string='') {
        if($string == '"[]"') return array();
        $string = str_replace('`','"',substr(substr($string,1), 0, -1));
        return json_decode($string);
    }

    private static function generate_gear($array=array()) {
        if(!count($array)) $array = array();
        return str_replace('"','`',json_encode($array));
    }
}