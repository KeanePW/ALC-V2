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

class permissions {
    private static $permissions = array();

    public static function load() {
        global $system_db;
        $permissions = $system_db->select_foreach("SELECT `id`,`name`,`permission` FROM `".dba::get('permissions')."`");
        foreach ($permissions as $permission) {
            self::$permissions[$permission['id']] = array('name' => $permission['name'], 'permission' => $permission['permission']);
        }
    }

    public static function getByGroupID($groupid=0) {
        global $system_db;
        if(!$groupid) return array();
        $permissions_input = $system_db->select("SELECT `permissions` FROM `".dba::get('groups')."` WHERE `id` = ?",array($groupid));
        if(!$system_db->rowCount()) return array();
        return self::getByString($permissions_input['permissions']);
    }

    public static function getByUserPermissions($userid=0) {
        global $system_db;
        if(!$userid) return array();
        $permissions_input = $system_db->select("SELECT `permissions` FROM `".dba::get('userperm')."` WHERE `id` = ?",array($userid));
        if(!$system_db->rowCount()) return array();
        return self::getByString($permissions_input['permissions']);
    }

    public static function hasPermission($permission='',$userid=0) {
        global $system_db;

        if(isset($_SESSION['userid']) && !empty($_SESSION['userid']) && !$userid)
            $userid = $_SESSION['userid'];

        $get = $system_db->select("SELECT s2.groupid FROM ".dba::get('users')." AS s1 LEFT JOIN ".dba::get('usergroups')." AS s2 ON s1.id = s2.userid WHERE s1.id = ".$userid." ORDER BY s2.id");
        if($system_db->rowCount()) {
            $array_permission = self::getByGroupID($get['groupid']);
            if(array_key_exists($permission, $array_permission)) return true; //Permission by Group
        }

        $array_permission = self::getByUserPermissions($userid);
        if(array_key_exists($permission, $array_permission)) return true; //Permission by User

        return false;
    }

    private static function getByString($permissions_input='') {
        if(empty($permissions_input) || !$permissions_input) return array();
        $permissions = array();
        if($permissions_input != '*') {
            $permissionIDs = explode(',', $permissions_input);
            foreach ($permissionIDs as $permissionID) {
                echo $permissionID;
                $permissions[self::$permissions[$permissionID]['permission']] = array('id' => $permissionID, 'name' => self::$permissions[$permissionID]['name']);
            }
        } else if($permissions_input == '*') { // Admin *
            foreach (self::$permissions as $permissionID => $permissions_data) {
                $permissions[$permissions_data['permission']] = array('id' => $permissionID, 'name' => $permissions_data['name']);
            }
        }

        return $permissions;
    }

    public static function genListGroupPermission($groupid=0) {
        global $system_db;
        $list = '';
        if(!$groupid) return $list;
        $user_permissions = self::getByGroupID($groupid);
        $foreach = $system_db->select_foreach("SELECT * FROM `".dba::get('permissions')."`");
        foreach ($foreach as $permission) {
            $checked = array_key_exists($permission['permission'], $user_permissions) ? 'checked="checked"' : '';
            $list .= '<tr>
              <td><input type="checkbox" name="'.$permission['permission'].'" id="'.$permission['permission'].'" '.$checked.'/></td>
              <td>'.constant($permission['name']).'</td>
            </tr>';
        }

        return $list;
    }

    public static function genListUserPermission($userid=0) {
        global $system_db;
        $list = '';
        if(!$userid) return $list;
        $user_permissions = self::getByUserPermissions($userid);
        $foreach = $system_db->select_foreach("SELECT * FROM `".dba::get('permissions')."`");
        foreach ($foreach as $permission) {
            $checked = array_key_exists($permission['permission'], $user_permissions) ? 'checked="checked"' : '';
            $list .= '<tr>
              <td><input type="checkbox" name="'.$permission['permission'].'" id="'.$permission['permission'].'" '.$checked.'/></td>
              <td>'.constant($permission['name']).'</td>
            </tr>';
        }

        return $list;
    }

    public static function updateUserPermissions($userid=0) {
        global $system_db;

        $updateList = ''; $i=0;
        $foreach = $system_db->select_foreach("SELECT * FROM `".dba::get('permissions')."`");
        $count_permissions = $system_db->rowCount();
        foreach ($foreach as $permission) {
            if(array_key_exists($permission['permission'], $_POST)) {
                $updateList .= $permission['id'].',';
                $i++;
            }
        }

        if(!empty($updateList))
            $updateList = substr($updateList, 0, -1);
        else
            $updateList = '0';

        if(!empty($updateList) && $i == $count_permissions)
            $updateList = '*';

        $system_db->update("UPDATE `".dba::get('userperm')."` SET `permissions` = ? WHERE `id` = ?;",array($updateList,$userid));
    }

    public static function updateGroupPermissions($groupid=0) {
        global $system_db;

        $updateList = ''; $i=0;
        $foreach = $system_db->select_foreach("SELECT * FROM `".dba::get('permissions')."`");
        $count_permissions = $system_db->rowCount();
        foreach ($foreach as $permission) {
            if(array_key_exists($permission['permission'], $_POST)) {
                $updateList .= $permission['id'].',';
                $i++;
            }
        }

        if(!empty($updateList))
            $updateList = substr($updateList, 0, -1);
        else
            $updateList = '0';

        if(!empty($updateList) && $i == $count_permissions)
            $updateList = '*';

        $system_db->update("UPDATE `".dba::get('groups')."` SET `permissions` = ? WHERE `id` = ?;",array($updateList,$groupid));
    }
}