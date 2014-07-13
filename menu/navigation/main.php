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

define('MenuNavPath',basePath . 'menu/navigation/');
if(file_exists(MenuNavPath . 'languages/'.language::get_language().'.php'))
    require_once(MenuNavPath . 'languages/'.language::get_language().'.php');

function navigation() {
    global $system_db;
    $templsys = new Template();
    $navigation = '';
    $foreach = $system_db->select_foreach("SELECT * FROM `".dba::get('navikat')."` WHERE `page` = 0 AND `shown` = 1");
    if(count($foreach)) {
        foreach ($foreach as $nav_kat) {
            if((!empty($nav_kat['permission']) && backend::loggedin() && permissions::hasPermission($nav_kat['permission'])) ||
            (empty($nav_kat['permission']) && !$nav_kat['internal']) ||
            (backend::loggedin() && $nav_kat['internal'])) {
                /** Sub Navigation / Dropdown */
                $dropdown_menu = false;
                $foreach_sub = $system_db->select_foreach("SELECT * FROM `".dba::get('navi')."` WHERE `katid` = ".convert::ToInt($nav_kat['id'])."
                AND `page` = 0 AND shown = 1 ORDER BY `pos` ASC");
                if(count($foreach_sub) >= 1) {
                    $dropdown_entities = ''; $dropdown_menu = true;
                    foreach ($foreach_sub as $nav) {
                        if((!empty($nav['permission']) && backend::loggedin() && permissions::hasPermission($nav['permission'])) ||
                             (empty($nav['permission']) && !$nav['internal']) ||
                             (backend::loggedin() && $nav['internal'])) {
                            $templsys->load("menu/navigation/show_navigation_dropdown_entities");
                            $templsys->assign('target', convert::DecodeString($nav['target']));
                            $templsys->assign('href', convert::DecodeString($nav['url']));
                            $templsys->assign('class', $nav['wichtig'] ? 'class="wichtig"' : '');
                            $templsys->assign('name', convert::DecodeString($nav['name']));
                            $dropdown_entities .= $templsys->out();
                        }
                    }
                }

                if($dropdown_menu) {
                    $templsys->load("menu/navigation/show_navigation_dropdown");
                    $templsys->assign('class', convert::DecodeString($nav_kat['class']));
                    $templsys->assign('name', (!$nav_kat['wichtig'] ? convert::DecodeString($nav_kat['name']) :
                    '<span class="fontWichtig">'.convert::DecodeString($nav_kat['name']).'</span>'));
                    $templsys->assign('menu_entities', $dropdown_entities);
                } else {
                    $templsys->load("menu/navigation/show_navigation_single");
                    $templsys->assign('class', convert::DecodeString($nav_kat['class']));
                    $templsys->assign('target', convert::DecodeString($nav_kat['target']));
                    $templsys->assign('href', convert::DecodeString($nav_kat['url']));
                    $templsys->assign('name', (!$nav_kat['wichtig'] ? convert::DecodeString($nav_kat['name']) :
                    '<span class="wichtig">'.convert::DecodeString($nav_kat['name']).'</span>'));
                }

                $navigation .= $templsys->out();
            }
        }
    }

    //Main File
    $templsys->load("menu/navigation/show_navigation");
    $templsys->assign('menu', $navigation);
    return $templsys->out();
}
