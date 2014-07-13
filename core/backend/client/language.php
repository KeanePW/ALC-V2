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

class language extends core {
    private static $language = '';
    private static $languages = '';
    private static $language_files = array();
    private static $user_agent = '';

    /**
     * Erkenne die Sprache des Users am Browser
     */
    private static function detect_language() {
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            self::$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2);
        else if ($_SERVER['HTTP_USER_AGENT']) {
            self::$user_agent = explode(";", $_SERVER['HTTP_USER_AGENT']);
            for($i=0; $i < sizeof(self::$user_agent); $i++)
            {
                self::$languages = explode("-",self::$user_agent[$i]);
                if (sizeof(self::$languages) == 2)
                {
                    if(strlen(trim(self::$languages[0])) == 2)
                    {
                        $size = sizeof(self::$language);
                        self::$language[$size]=trim(self::$languages[0]);
                    }
                }
            }
        }
        else
            self::$language = settings::get('language');
    }

    private static function check_language($lng='') {
        return(file_exists(basePath . 'languages/'.$lng.'.php'));
    }

    public static function set_language($language = '') {
        if($language != '')
            $_SESSION['language'] = $language;
        else {
            self::detect_language();
            $_SESSION['language'] = (cookie::get('language') ? cookie::get('language') : self::$language);
        }

        if(isset($_SESSION['language'])) {
            if(self::check_language($_SESSION['language'])) {
                self::$language = $_SESSION['language'];
                cookie::put('language', self::$language);
            } else {
                self::$language = settings::get('language');
                cookie::put('language', self::$language);
            }
        } else {
            self::$language = settings::get('language');
            cookie::put('language', self::$language);
        }

        cookie::save();
    }

    public static function run_language($language='') {
        if(!count(self::$language_files = self::getFiles(basePath . 'languages/',false,true,array('php'))))
            die('No language files found in "languages/*"!');

        self::set_language($language);
        require_once(basePath . 'languages/global/global.php');
        require_once(basePath . 'languages/'.self::$language.'.php');
    }

    public static function get_language() {
        return self::$language;
    }

    public static function get_language_tag() {
        switch (self::$language) {
            case 'de': return 'de';
            default: return 'uk';
        }
    }

    public static function get_language_files() {
        return self::$language_files;
    }

    public static function get_meta() {
        $meta='';
        if(count(self::$language_files) >= 1) {
            foreach(self::$language_files as $file) {
                $file = explode('.',$file);
                $file = substr($file[0], 0, 2);
                $meta .= '    <meta http-equiv="Content-Language" content="'.$file.'"/>'."\n";
            }
        }

        return substr($meta, 0, -1);
    }

    public static function get_menu($lang='') {
        $options = '';
        if(count(self::$language_files) >= 1) {
            foreach(self::$language_files as $file) {
                $file = explode('.',$file);
                $firstString = substr($file[0], 0,1);
                $lang_name = strtoupper($firstString).substr($file[0], 1);
                $options .= '<option value="'.$file[0].'" '.($file[0] == $lang ? 'selected="selected"' : '').'> '.$lang_name.'</option>';
            }
        }

        return '<select id="language" name="language" class="dropdown">'.'<option value="default" '.( $lang == 'default' ? 'selected="selected"' : '').'> '._default.'</option>'.$options.'</select>';
    }
}