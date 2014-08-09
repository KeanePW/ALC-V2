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

$prev = ''; $db_array = array();

/* block attempts to directly run this script */
if (getcwd() == dirname(__FILE__)) {
    die('block directly run');
}

if (!defined('RunALC')) {
    exit;
}

/* Require Shared Core */
$require_shared = core::getFiles(basePath . 'core/backend/shared',false,true,array('php'));
if($require_shared && count($require_shared) >= 1) {
    foreach ($require_shared as $require_shared_file) {
        if(file_exists(basePath . 'core/backend/shared/' . $require_shared_file))
            require_once basePath . 'core/backend/shared/' . $require_shared_file;
    }
}

/* Require Server Core */
$require_server = core::getFiles(basePath . 'core/backend/server',false,true,array('php'));
if($require_server && count($require_server) >= 1) {
    foreach ($require_server as $require_server_file) {
        if(file_exists(basePath . 'core/backend/server/' . $require_server_file))
            require_once basePath . 'core/backend/server/' . $require_server_file;
    }
}

/* Require Client Core */
$require_client = core::getFiles(basePath . 'core/backend/client',false,true,array('php'));
if($require_client && count($require_client) >= 1) {
    foreach ($require_client as $require_client_file) {
        if(file_exists(basePath . 'core/backend/client/' . $require_client_file))
            require_once basePath . 'core/backend/client/' . $require_client_file;
    }
}
unset($require_shared,$require_shared_file,$require_server,$require_server_file,$require_client,$require_client_file);

/**
 * Start Debugger
 */
Debugger::initCon();
set_error_handler('error_handler');

/**
 * Start Sessions
 */
$session = new session();
if(!$session->init())
    die('PHP-Sessions not started!');

/**
 * Start Database
 */
require_once basePath . 'core/config.mysql.php';
$system_db = db::getInstance('default');

//-> MySQL-Datenbankangaben
//   [TAG]                    [TABELLE]              [NAME + PREFIX]
$db_array['users']        =    'users';                # alc_users
$db_array['userstats']    =    'users_stats';          # alc_users_stats
$db_array['userperm']     =    'users_permissions';    # alc_users_permissions
$db_array['usergroups']   =    'users_groups';         # alc_users_groups

$db_array['groups']       =    'groups';               # alc_groups
$db_array['permissions']  =    'permissions';          # alc_permissions

$db_array['settings']     =    'settings';             # alc_settings
$db_array['ipcheck']      =    'ipcheck';              # alc_ipcheck

$db_array['navi']         =    'navigation';           # alc_navigation
$db_array['navikat']      =    'navigation_kategorie'; # alc_navigation_kategorie

/**
 * Start Database Array
 */
dba::init();
unset($db_array);

/**
 * Start CMS Settings
 */
settings::load();

/**
 * Start Cookies
 */
cookie::init('hmp'.$prev);

/**
 * Start PHPMailer
 */
$phpmailer = new PHPMailer();

/**
 * Start Snoopy
 */
$snoopy = new Snoopy();

/**
 * Start Gump
 */
$gump = new GUMP();
$gump_import_get = $gump->sanitize($_GET);

/* Filter GET index,page,do,language */
$filter = array('index'       => 'trim|sanitize_string|strtolower',
                'page'        => 'trim|sanitize_numbers',
                'do'          => 'trim|sanitize_string|strtolower',
                'language'    => 'trim|sanitize_string|strtolower');

$_GET = $gump->filter($gump_import_get, $filter);

$validate = array('index' => 'required|alpha_numeric|max_len,20',
                  'page'  => 'numeric|max_len,3',
                  'do'    => 'alpha_numeric|max_len,30');

$do = 'default'; $page = 1; $index = 'home';
$gump_check_core = $gump->validate($_GET, $validate);
if($gump_check_core === TRUE) {
    $do = isset($_GET['do']) ? $_GET['do'] : $do;
    $page = isset($_GET['page']) ? $_GET['page'] : $page;
    $index = isset($_GET['index']) ? $_GET['index'] : 'news';
}

/**
 * Start Permissions
 */
permissions::load();

/**
 * Start Language
 */
if(isset($_GET['language']) && !empty($_GET['language']) && file_exists(basePath."languages/".$_GET['language'].".php")) {
    language::run_language($_GET['language']);
    header("Location: ".$_SERVER['HTTP_REFERER']);
} else if(isset($_POST['language']) && !empty($_POST['language']) && file_exists(basePath."languages/".$_POST['language'].".php")) {
    language::run_language($_GET['language']);
    header("Location: ".$_SERVER['HTTP_REFERER']);
} else {
    language::run_language();
}

/**
 * Start Template
 */
$templsys_core = new Template();
$templsys_core->load("index");
output::setEmpty('content');
output::setEmpty('content_right');
output::setEmpty('content_left');
output::setEmpty('content_footer');

/**
 * ******************************************
 * ************* FUNCTIONS ******************
 * ******************************************
 */

class backend {
    public static function loggedin() {
        global $system_db;
        if(!isset($_SESSION['userid']) || empty($_SESSION['userid'])) return false;
        if(!isset($_SESSION['lastvisit'])) return false;
        if(!isset($_SESSION['ip']) || empty($_SESSION['ip'])) return false;
        if($_SESSION['ip'] != core::visitorIp()) return false;
        $get = $system_db->select("SELECT enabled FROM `".dba::get('users')."` WHERE `id` = ?", array($_SESSION['userid']));
        if(!$system_db->rowCount() || !$get['enabled']) return false;
        return true;
    }

    public static function userid() {
        if(!isset($_SESSION['userid']) || empty($_SESSION['userid'])) return 0;
        if(!isset($_SESSION['lastvisit'])) return 0;
        if(!isset($_SESSION['ip']) || empty($_SESSION['ip'])) return 0;
        return convert::ToInt($_SESSION['userid']);
    }

    //-> schreibe in die IPCheck Tabelle
    public static function setIpcheck($what = '') {
        global $system_db;
        return $system_db->insert("INSERT INTO ".dba::get('ipcheck')." SET `ip` = '".core::visitorIp()."', `userid` = '".backend::userid()."', `time` = ".time().", `what` = ?;",array($what));
    }

    //-> Checkt versch. Dinge anhand der Hostmaske eines Users
    public static function ipcheck($what, $time = 0) {
        global $system_db;

        $get = $system_db->select("SELECT `time`,`what` FROM `".dba::get('ipcheck')."` WHERE what = ? AND ip = '".core::visitorIp()."';",array($what));
        if(!$system_db->rowCount())
            return false;

        $time = convert::ToInt($time);
        if($get['time']+$time<time())
            $system_db->delete("DELETE FROM `".dba::get('ipcheck')."` WHERE what = ? AND ip = '".core::visitorIp()."' AND time+'".$time."'<'".time()."'",array($what));

        if($get['time']+$time>time())
            return true;

        return false;
    }
}

/* Require Menus */
$require_menus = core::getFiles(basePath . 'menu',true);
if($require_menus && count($require_menus) >= 1) {
    foreach ($require_menus as $require_menu) {
        if(file_exists(basePath . 'menu/' . $require_menu. '/main.php'))
            require_once basePath . 'menu/' . $require_menu. '/main.php';
    }
}
unset($require_menus,$require_menu);

