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

class MSGBox extends frontend {
    private static $index = '';

    public static function show() {
        return self::$index;
    }

    public static function msg_error($text='') { //alias get_msg_error()
        self::$index .= self::get_msg_error($text);
    }

    public static function msg_info($text='') { //alias get_msg_info()
        self::$index .= self::get_msg_info($text);
    }

    public static function msg_success($text='') { //alias get_msg_success()
        self::$index .= self::get_msg_success($text);
    }

    public static function msg_warning($text='') { //alias get_msg_warning()
        self::$index .= self::get_msg_warning($text);
    }

    //GET
    public static function get_msg_error($text='') { //alias msg_box()
        return self::msg_box($text,'alert-danger','error');
    }

    public static function get_msg_info($text='') { //alias msg_box()
        return self::msg_box($text,'alert-info','notice');
    }

    public static function get_msg_success($text='') { //alias msg_box()
        return self::msg_box($text,'alert-success','success');
    }

    public static function get_msg_warning($text='') { //alias msg_box()
        return self::msg_box($text,'alert-warning','warning');
    }

    private static function msg_box($text='',$class='',$dismiss='') {
        self::$templsys->load("msg/msg_alertbox");
        self::$templsys->assign('class', $class);
        self::$templsys->assign('status', $dismiss);
        self::$templsys->assign('text', convert::ToHTML($text));
        return self::$templsys->out();
    }
}