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

/* block attempts to directly run this script */
if (getcwd() == dirname(__FILE__)) {
    die('block directly run');
}

if (!defined('RunALC')) {
    exit;
}

/* Require Client Core */
$require_client = core::getFiles(basePath . 'core/frontend/client',false,true,array('php'));
if($require_client && count($require_client) >= 1) {
    foreach ($require_client as $require_client_file) {
        if(file_exists(basePath . 'core/frontend/client/' . $require_client_file))
            require_once basePath . 'core/frontend/client/' . $require_client_file;
    }
}
unset($require_client,$require_client_file);

/**
 * ******************************************
 * ************* FUNCTIONS ******************
 * ******************************************
 */

class frontend {
    public static $templsys = NULL;

    function __construct() {
        self::$templsys = new Template();
    }

    /**
     * Gibt eine Flagge aus
     * @param string $code
     * @return html->img
     */
    public static function flag($code) {
        global $picformat;
        $flag_code = 'na.gif';
        if(!empty($code)) {
            foreach($picformat as $end) {
                if(file_exists(basePath . self::$templsys->getidir(). 'images/flags/'.$code.'.'.$end))
                    $flag_code = $code.'.'.$end;
            }
        }

        return '<img src="' . self::$templsys->getidir() . 'images/flags/'.$flag_code.'" alt="" class="icon" />';
    }

    /**
     * Emailadressen in Unicode umwandeln
     * @param string $email
     * @return Unicode string
     */
    public static function eMailAddr($email) {
        $output = '';
        for($i=0;$i<strlen($email);$i++) {
            $output .= str_replace(substr($email,$i,1),"&#".ord(substr($email,$i,1)).";",substr($email,$i,1));
        }
        return $output;
    }

    /**
     * Nickausgabe mit Profil Link und Flagge oder als E-Mail Link
     * @param number $uid
     * @param string $nick
     * @param string $email
     * @param string $href
     * @param string $class
     * @param boolean $cut
     * @return string/html tag
     */
    public static function autor($uid="", $nick="", $email="" ,$class="", $cut=false) {
        global $system_db;
        if((empty($uid) || !$uid) && $_SESSION['userid'] && empty($nick))
            $uid = $_SESSION['userid'];

        if($uid >= 1) {
            $get = $system_db->select("SELECT `nick`,`country` FROM `".dba::get('users')."` WHERE `id` = ?",array($uid));
            if($system_db->rowCount()) {
                $nickname = $cut ? core::cut(convert::DecodeString($get['nick']), $cut) : convert::DecodeString($get['nick']);
                self::$templsys->load(_user_link);
                self::$templsys->assign_array(array("id" => $uid, "country" => self::flag($get['country']), "class" => $class, "href" => '?index=user&amp;profile='.$uid, "nick" => $nickname));
                return self::$templsys->out();
            }
        }

        $nickname = $cut ? core::cut(convert::DecodeString($nick), $cut) : convert::DecodeString($nick);
        self::$templsys->load(_user_link_noreg);
        self::$templsys->assign_array(array("nick" => $nickname, "class" => $class, "email" => self::eMailAddr($email)));
        return self::$templsys->out();
    }

    /**
     * Useravatar ausgeben
     * @param number $userid
     * @param number $width
     * @param number $height
     * @return html->img
     */
    public static function useravatar($setuserid=0, $width=100, $height=100) {
        global $picformat;

        $userid = convert::ToInt($_SESSION['userid']);
        if(empty($setuserid) || !$setuserid)
            $userid = $setuserid;

        foreach($picformat as $endung) {
            if(file_exists(basePath . 'uploads/useravatare/'.convert::ToInt($userid).'.'.$endung)) {
                self::$templsys->load(_userava_link);
                self::$templsys->assign_array(array("id" => convert::ToInt($userid), "endung" => $endung, "width" => $width, "height" => $height));
                return self::$templsys->out();
            }
        }

        self::$templsys->load(_userava_noavatar);
        self::$templsys->assign_array(array("width" => $width, "height" => $height));
        return self::$templsys->out();
    }

    /**
     * Usergruppe ausgeben
     * @param number $userid
     * @return html->text
     */
    public static function usergroup($setuserid=0) {
        global $picformat,$system_db;

        $userid = convert::ToInt($_SESSION['userid']);
        if(empty($setuserid) || !$setuserid)
            $userid = $setuserid;

        $get = $system_db->select("SELECT s2.groupid FROM ".dba::get('users')." AS s1 LEFT JOIN ".dba::get('usergroups')." AS s2 ON s1.id = s2.userid WHERE s1.id = ".$userid." ORDER BY s2.id");
        if(!$system_db->rowCount()) return constant('_group_guest');

        $get = $system_db->select("SELECT name,color FROM `".dba::get('groups')."` WHERE `id` = ?",array($get['groupid']));
        if(!$system_db->rowCount()) return constant('_group_guest');

        return '<font color="'.$get['color'].'">'.(defined('_group_'.$get['name']) ? constant('_group_'.$get['name']) : ucfirst(strtolower($get['name']))).'</font>';
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

    /**
     * Truncates text.
     * Exported from CakePHP
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as Ending and appended to the trimmed string (`ending` is deprecated)
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     *
     * @param string $text String to truncate.
     * @param integer $length Length of returned string, including ellipsis.
     * @param array $options An array of html attributes and options.
     * @return string Trimmed string.
     * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
     */
    public static function truncate($text, $length = 100, $options = array()) {
        $defaults = array('ellipsis' => '...', 'exact' => true, 'html' => false);
        if (isset($options['ending'])) {
            $defaults['ellipsis'] = $options['ending'];
        } elseif (!empty($options['html']) && Configure::read('App.encoding') === 'UTF-8') {
            $defaults['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $defaults;
        extract($options);

        if (!function_exists('mb_strlen')) {
            class_exists('Multibyte');
        }

        if ($html) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen(strip_tags($ellipsis));
            $openTags = array();
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if ($html) {
                $truncateCheck = mb_substr($truncate, 0, $spacepos);
                $lastOpenTag = mb_strrpos($truncateCheck, '<');
                $lastCloseTag = mb_strrpos($truncateCheck, '>');
                if ($lastOpenTag > $lastCloseTag) {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
                    $lastTag = array_pop($lastTagMatches[0]);
                    $spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
                }
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    if (!empty($openTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    } else {
                        foreach ($droppedTags as $closingTag) {
                            $openTags[] = $closingTag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
        $truncate .= $ellipsis;

        if ($html) {
            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }
}