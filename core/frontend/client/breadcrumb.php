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

class breadcrumb extends frontend {
    private static $index = '';
    private static $breadcrumb = array();

    public static function get() {
        if(count(self::$breadcrumb)) {
            self::gen_breadcrumb();
            self::$templsys->load("page/menu_breadcrumb");
            self::$templsys->assign('li', self::$index);
            self::$index = '';
            return self::$templsys->out();
        }

        return '';
    }

    public static function add($text='',$link='') {
        self::$breadcrumb[$text] = $link;
    }

    public static function add_array($input=array()) {
        self::$breadcrumb = array_merge($input,self::$breadcrumb);
    }

    private static function gen_breadcrumb() {
        foreach (self::$breadcrumb as $text => $link) {
            self::$templsys->load("page/menu_breadcrumb_li");
            self::$templsys->assign('text', $text);
            self::$templsys->assign('link', $link);
            self::$index .= self::$templsys->out();
        }
    }
}