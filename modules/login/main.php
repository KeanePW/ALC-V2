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

define('modulePath',basePath . 'modules/login/');
if(file_exists(modulePath . 'languages/'.language::get_language().'.php'))
    require_once(modulePath . 'languages/'.language::get_language().'.php');

$templsys_login = new Template();

switch ($do) {
    case 'logout':
        if(backend::loggedin()) {
            $system_db->update("UPDATE `".dba::get('users')."` SET `sessid` = '', `userip` = '', `permid` = '', `online` = '0' WHERE `id` = ?;",array($_SESSION['userid']));
            cookie::clear();
            cookie::save();
            $session->init(true);
            header("Location: ".$_SERVER['HTTP_REFERER']);
        }
    break;
    default:
        if(!backend::loggedin()) {
            /* Filter POST & Prüfe auf Login */
            $validate = array('login'    => 'required',
                              'username' => 'required|alpha_numeric|min_len,1',
                              'password' => 'required|alpha_numeric|min_len,1');

            $gump_check_login = $gump->validate($_POST, $validate);
            if($gump_check_login === TRUE) {
                $filter = array('username' => 'trim|sanitize_string',
                                'password'  => 'trim|sanitize_string|sha1');

                $_POST = $gump->filter($_POST, $filter);
                unset($_POST['login']); //Unset submit button

                /* Login mit Datenbank abgleichen */
                $get = $system_db->select("SELECT `id`,`time` FROM `".dba::get('users')."` WHERE `username` = ? AND `password` = ?",array($_POST['username'],$_POST['password']));
                if($system_db->rowCount()) {
                    Debugger::insert_successful('module::login', 'Datensatz f&uuml;r User: "'.$_POST['username'].'" gefunden');

                    /* Autologin */
                    if(isset($_POST['autologin'])) {
                        $permanent_id = sha1(core::random_password());
                        $system_db->update("UPDATE `".dba::get('users')."` SET `permid` = '".$permanent_id."' WHERE `id` = ".$get['id'].";");
                        cookie::extract();
                        cookie::put('id', $get['id']);
                        cookie::put('pid', $permanent_id);
                        cookie::save();
                    }

                    /* Aktualisiere User Datenbank */
                    $system_db->update("UPDATE `".dba::get('users')."` SET `sessid` = '".session_id()."', `userip` = '".($userip=core::visitorIp())."', `online` = '1' WHERE `id` = ".$get['id'].";");

                    /* Schreibe Sessions */
                    $_SESSION['userid']         = $get['id'];
                    $_SESSION['lastvisit']      = $get['time'];
                    $_SESSION['ip']             = $userip;
                    unset($userip);

                    /* Aktualisiere User Statistiken */
                    $system_db->update("UPDATE `".dba::get('userstats')."` SET `logins` = logins+1, `last_online` = ".time()." WHERE `id` = ".$get['id'].";");
                    header("Location: ".$_SERVER['HTTP_REFERER']);
                }
                else {
                    output::set('alert', MSGBox::get_msg_error(_login_fail));
                    Debugger::insert_error('module::login', 'Datensatz f&uuml;r User: "'.$_POST['username'].'" wurde nicht gefunden');
                }
            }

            if(!backend::loggedin()) {
                $templsys_login->load("login/login");
                $templsys_login->assign('alert', output::get('alert',true));
                output::set('index', $templsys_login->out());
            }
        }
    break;
}