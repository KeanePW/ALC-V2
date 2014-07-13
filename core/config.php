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

#########################################
//-> Debug Console Settings Start
#########################################

define('debug_all_sql_querys', false);
define('save_debug_console', false);

define('show_initialize', true);
define('show_loaded', true);
define('show_info', true);
define('show_warning', true);
define('show_xml', false);
define('show_sessions_debug', true);
define('show_deprecation_debug', true);
define('show_debug_console', true);

#########################################
//-> Sessions Settings Start
#########################################

define('sessions_backend', 'php'); //php,mysql,memcache,apc
define('sessions_encode_type', 'sha1');
define('sessions_encode', true);
define('sessions_ttl_maxtime', (2*60*60)); //Live-Time 2h
define('sessions_memcache_host', 'localhost');
define('sessions_memcache_port', 11211);

define('sessions_mysql_host', 'localhost');
define('sessions_mysql_user', 'user');
define('sessions_mysql_pass', 'xxxx');
define('sessions_mysql_db', 'test');

#########################################
//-> Cookie Settings Start
#########################################

define('cookie_expires', (60*60*24*30*12)); // Wie Lange die Cookies des CMS ihre Gültigkeit behalten.

#########################################
//-> CMS Settings Start
#########################################
$picformat = array('jpg', 'jpeg', 'gif', 'png'); // Unterstützte Bildformate

