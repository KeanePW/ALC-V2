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

class Template {
    private $index = '';
    private $template = 'standard';
    private $replacer = array();
    private $leftDelimiter = '{';
    private $rightDelimiter = '}';

    public function __construct($template='') {
        if(!empty($template) && is_dir(basePath . 'templates/' . $template))
            $this->template = $template;
        else if(!empty(settings::get('tmpdir')) && is_dir(basePath . 'templates/' . settings::get('tmpdir')))
            $this->template = settings::get('tmpdir');
        else
            $this->template = 'standard';
    }

    public function load($file) {
        $cache_hash = md5($file);
        #if(!$this->index=apc_fetch($cache_hash)) {
            if(file_exists(basePath . 'templates/' . $this->template . '/' . $file . '.tpl')) {
                $this->index = file_get_contents(basePath . 'templates/' . $this->template . '/' . $file . '.tpl');
                #apc_add($cache_hash, $this->index, 60);
            } else
                $this->index = $file;
      #  }
    }

    public function getidir() {
        return 'templates/' . $this->template . '/';
    }

    public function setLeftDelimiter($leftDelimiter) {
        $this->leftDelimiter = $leftDelimiter;
    }

    public function setRightDelimiter($rightDelimiter) {
        $this->rightDelimiter = $rightDelimiter;
    }

    public function assign_array($array) {
        $this->replacer = array_merge($this->replacer,$array);
    }

    public function assign($replace, $replacement) {
        $this->replacer[$replace] = $replacement;
    }

    public function out($file=null,$array=array()) {
        if(!empty($file)) {
            $temp = new Template();
            $temp->load($file);
            $temp->assign_array($array);
            return $temp->out();
        }

        if(count($this->replacer) >= 1)
            foreach ($this->replacer as $search => $replace) {
                $this->index = str_replace($this->leftDelimiter.$search.$this->rightDelimiter, $replace, $this->index);
            unset($this->replacer[$search]);
        }

        $this->index = str_replace($this->leftDelimiter.'lang'.$this->rightDelimiter, language::get_language(), $this->index); //Language
        $this->index = preg_replace_callback("(\{lang(.*?)\\})is", function($lang) { if(defined($lang[1])) return constant($lang[1]); return 'lang'.$lang[1]; }, $this->index);
        $this->assign_tags();
        $this->replacer = array();
        return $this->index;
    }

    public function page() {
        $this->out();
        $this->index = preg_replace_callback("(\{menu_(.*?)\\})is", function($function) {
            if(function_exists($function[1]))
                return call_user_func($function[1]);

            Debugger::insert_warning('Template::page() -> preg_replace_callback()', 'Function: "'.$function[1].'" not found! Used for {menu_'.$function[1].'}');
            return '';
        }, $this->index);

        $this->assign("debugger", Debugger::show_logs());
        $this->assign("msg_box", MSGBox::show());
        return $this->out();
    }

    private function assign_tags() {
        $this->index = str_replace($this->leftDelimiter.'dir'.$this->rightDelimiter, 'templates/' . $this->template, $this->index); //Template dir
        $this->index = str_replace($this->leftDelimiter.'idir'.$this->rightDelimiter, 'templates/' . $this->template. '/images', $this->index); //Images dir
        $this->index = (!backend::loggedin() ? preg_replace("|<logged_in>.*?</logged_in>|is", "", $this->index) : preg_replace("|<logged_out>.*?</logged_out>|is", "", $this->index));
        $this->index = str_ireplace(array("<logged_in>","</logged_in>","<logged_out>","</logged_out>"), '', $this->index);
    }
}
