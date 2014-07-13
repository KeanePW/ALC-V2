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

class convert {
    public static function EncodeString($input) {
        return self::UTF8(stripcslashes(self::spChars(self::ToHTML($input))));
    }

    public static function DecodeString($input) {
        return trim(stripslashes(self::spChars(self::FromHTML(self::UTF8_Reverse($input)),true)));
    }

    public static final function ToString($input) {
        return (string)$input;
    }

    public static final function BoolToInt($input) {
        return (int)($input == true ? 1 : 0);
    }

    public static final function IntToBool($input) {
        return ($input == 0 ? false : true);
    }

    public static final function ToInt($input) {
        return (int)$input;
    }

    public static final function ToFloat($input) {
        return floatval($input);
    }

    public static final function UTF8($input) {
        return self::ToString(utf8_encode($input));
    }

    public static final function UTF8_Reverse($input) {
        return utf8_decode($input);
    }

    public static final function ToHTML($input) {
        return htmlentities($input, ENT_COMPAT, 'iso-8859-1');
    }

    public static final function FromHTML($input) {
        return html_entity_decode($input, ENT_COMPAT, 'iso-8859-1');
    }

    /**
     * Ein Objekt in ein Array konvertieren.
     * @param objekt $object
     * @return array
     */
    public static function objectToArray( $object ) {
        if ( is_object( $object ) ) {
            foreach ( $object as $key => $value ) {
                $array[$key] = $value;
            }
        } else {
            $array = $object;
        }

        return $array;
    }

    public static final function ArrayToString(array $input) {
        if(!is_array($input)){
            return false;
        }

        return serialize($input);
    }

    public static final function StringToArray($input) {
        return (array)unserialize($input);
    }

    public static final function ArrayUTF8Encode($dat) {
        if(is_string($dat))
            return self::UTF8($dat);

        if (is_object($dat)) {
            $ovs= get_object_vars($dat); $new=$dat;
            foreach ($ovs as $k =>$v)    {
                $new->$k=self::ArrayUTF8Encode($new->$k);
            }

            return $new;
        }

        if(!is_array($dat))
            return $dat;

        $ret = array();
        foreach($dat as $i=>$d)
            $ret[$i] = self::ArrayUTF8Encode($d);

        return $ret;
    }

    public static final function ArrayUTF8Decode($dat) {
        if (is_string($dat))
            return self::UTF8_Reverse($dat);

        if(is_object($dat)) {
            $ovs= get_object_vars($dat);
            $new=$dat;
            foreach ($ovs as $k =>$v)    {
                $new->$k=self::ArrayUTF8Decode($new->$k);
            }

            return $new;
        }

        if(!is_array($dat)) return $dat;
        $ret = array();
        foreach($dat as $i=>$d)
            $ret[$i] = self::ArrayUTF8Decode($d);

        return $ret;
    }

    public static final function ArrayToJson(array $array) {
        if(!is_array($array)){
            return false;
        }

        return json_encode($array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    public static final function JsonToArray($input) {
        return self::JsonToObject($input,true);
    }

    public static final function ObjectToJson($object) {
        if(!is_object($object)){
            return false;
        }

        return json_encode($object, JSON_FORCE_OBJECT);
    }

    public static final function JsonToObject($input,$asArray=false) {
        return json_decode($input, $asArray);
    }

    public static final function BinToHex($bin) {
        return bin2hex(gzcompress($bin));
    }

    public static final function HexToBin($hex) {
        return gzuncompress(hex2bin($hex));
    }

    public static final function spChars($input,$reverse=false) {
        $var0 = array("€", "'", "\"","'","[","]");
        $var1 = array("&euro;","&apostroph;","&quot;","&#39;","&#91;","&#93;");
        return self::spCharsUml($reverse ? str_replace($var1, $var0, $input) : str_replace($var0, $var1, $input),$reverse);
    }

    public static final function spCharsUml($input,$reverse=false) {
        $var0 = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß");
        $var1 = array("&Auml;", "&Ouml;", "&Uuml;", "&auml;", "&ouml;", "&uuml;", "&szlig;");
        return $reverse ? str_replace($var1, $var0, $input) : str_replace($var0, $var1, $input);
    }
}