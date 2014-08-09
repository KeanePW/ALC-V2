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

define('modulePath',basePath . 'modules/player/');
if(file_exists(modulePath . 'languages/'.language::get_language().'.php'))
    require_once(modulePath . 'languages/'.language::get_language().'.php');

/* Filter POST & Prüfe auf Steam-ID64/CID */
$validate = array('steam_id' => 'required|numeric|min_len,1');
$gump_check_steamid = $gump->validate($_POST, $validate);

/* Filter GET & Prüfe auf UserID/UID */
$validate = array('uid' => 'required|numeric|min_len,1');
$gump_check_uid = $gump->validate($_GET, $validate);

if($gump_check_steamid === TRUE || $gump_check_uid === TRUE) {
    if($gump_check_steamid === TRUE) {
        $filter = array('steam_id' => 'sanitize_numbers');
        $_POST = $gump->filter($_POST, $filter);
        $player = $system_db->select('SELECT `uid`,`name` FROM `players` WHERE `playerid` = ?',array($_POST['steam_id']));
    }

    if($gump_check_uid === TRUE) {
        $filter = array('uid' => 'sanitize_numbers');
        $_GET = $gump->filter($_GET, $filter);
        $player = $system_db->select('SELECT `uid`,`name` FROM `players` WHERE `uid` = ?',array($_GET['uid']));
    }

    /* Start Player */
    breadcrumb::add('Player List','?index=playerlist');
    breadcrumb::add(convert::ToHTML($player['name']),'?index=player&uid='.$player['uid']);
    $player = alc_player::get_player($player['uid']); //Get User Infos

    echo '<pre>';
    print_r($player);
    echo '</pre>';

}



$templsys_login = new Template();
$templsys_login->load("player/player");
$templsys_login->assign('breadcrumb', breadcrumb::get());
output::set('index', $templsys_login->out());