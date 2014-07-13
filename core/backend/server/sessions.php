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

final class session {
    protected $db;
    protected $memcached;
    protected $_prefix;
    protected $_ttl = 3600;
    protected $_lockTimeout = 10;

    function __construct() {
        global $mysql_salt;

        if(show_sessions_debug && show_initialize)
            Debugger::insert_initialize("session::__construct()", "Sessions");

        $this->_prefix = $mysql_salt.'_';

        switch(sessions_backend) {
            case 'memcache':
                if(show_sessions_debug)
                    Debugger::insert_info("session::__construct()", "Use Memcache for Sessions");

                session_set_save_handler(array($this, 'mem_open'), array($this, 'mem_close'), array($this, 'mem_read'), array($this, 'mem_write'), array($this, 'mem_destroy'), array($this, 'mem_gc'));
                register_shutdown_function('session_write_close');
            break;
            case 'apc':
                if(show_sessions_debug)
                    Debugger::insert_info("session::__construct()", "Use APC for Sessions");

                $this->_ttl = sessions_ttl_maxtime;
                session_set_save_handler(array($this, 'apc_open'), array($this, 'apc_close'), array($this, 'apc_read'), array($this, 'apc_write'), array($this, 'apc_destroy'), array($this, 'apc_gc'));
                register_shutdown_function('session_write_close');
            break;
            case 'mysql':
                if(show_sessions_debug)
                    Debugger::insert_info("session::__construct()", "Use MySQL for Sessions");

                session_set_save_handler(array($this, 'sql_open'), array($this, 'sql_close'), array($this, 'sql_read'), array($this, 'sql_write'), array($this, 'sql_destroy'), array($this, 'sql_gc'));
                register_shutdown_function('session_write_close');
            break;
            default:
                if(show_sessions_debug)
                    Debugger::insert_info("session::__construct()", "Use PHP-Default for Sessions");
        }
    }

    function init($destroy=false) {
        if(!headers_sent() && !$this->is_session_started()) {
            if(show_sessions_debug)
                Debugger::insert_info("session::init()", "Call session_start()");

            if(session_start())
                if(show_sessions_debug)
                    Debugger::insert_successful("session::init()", "Sessions started, ready to use");
        }

        if($destroy) {
            Debugger::insert_error("session::init()", "Sessions destroy & Regenerate");
            session_unset();
            session_destroy();
            session_regenerate_id(true);
        }

        if (in_array(sessions_encode_type, hash_algos()))
            ini_set('session.hash_function', sessions_encode_type);

        ini_set('session.hash_bits_per_character', 5);
        return true;
    }

    ###################################################
    ################ Memcache Backend #################
    ###################################################
    public function mem_open() {
        if(show_sessions_debug)
            DebugConsole::insert_info("session::mem_open()", "Connect to Memcache Server");

        if($this->memcached instanceOf Memcache) return false;
        $this->memcached = new Memcache();
        $this->memcached->addServer(sessions_memcache_host,sessions_memcache_port);

        if(show_sessions_debug) {
            if(!$this->memcached->getServerStatus(sessions_memcache_host, sessions_memcache_port)) {
                Debugger::insert_error("session::mem_open()", "Connect to Memcache Server failed!");
                Debugger::insert_error("session::mem_open()", "Host: ".sessions_memcache_host.':'.sessions_memcache_port);
            }
            else
                Debugger::insert_successful("session::mem_open()", "Connected to Memcache Server");
        }

        return !$this->memcached->getServerStatus(sessions_memcache_host, sessions_memcache_port) ? false : true;
    }

    public function mem_close() {
        if(show_sessions_debug)
            Debugger::insert_info("session::mem_close()", "Disconnect Memcache Server");

        return $this->memcached->close();
    }

    public function mem_read($id) {
        global $db_array;
        if(show_sessions_debug) {
            Debugger::insert_info("session::mem_read()", "Read Session-Data from Memcache");
            Debugger::insert_info("session::mem_read()", "Select ID: '".$id."'");
        }

        $data = $this->memcached->get($this->_prefix.$id);
        if(empty($data)) return '';

        if(sessions_encode)
            $data = core::decrypt($data, $id);

        if(show_sessions_debug)
            Debugger::insert_successful("session::mem_read()", $data);

        return $data;
    }

    public function mem_write($id, $data) {
        if(show_sessions_debug) {
            Debugger::insert_info("session::mem_write()", "Write Session-Data to Memcache");
            Debugger::insert_info("session::mem_write()", "Select ID: '".$id."'");
        }

        if(show_sessions_debug)
            Debugger::insert_successful("session::mem_write()", $data);

        if(sessions_encode)
            $data = core::encrypt($data, $id);

        $result = $this->memcached->replace($this->_prefix.$id, $data, MEMCACHE_COMPRESSED, sessions_ttl_maxtime);
        if( $result == false )
            $result = $this->memcached->set($this->_prefix.$id, $data, MEMCACHE_COMPRESSED, sessions_ttl_maxtime);

        return $result;
    }

    public function mem_destroy($id) {
        return $this->memcached->delete($this->_prefix.$id);
    }

    public function mem_gc($max)
    { return true; }

    ###################################################
    ################### APC Backend ###################
    ###################################################
    public function apc_open($savePath, $sessionName) {
        $this->_prefix = 'BSession/'.$sessionName;
        if (!apc_fetch($this->_prefix.'/TS', $result)) {
            apc_store($this->_prefix.'/TS', array(''));
            apc_store($this->_prefix.'/LOCK', array(''));
        }

        if(show_sessions_debug)
            Debugger::insert_info("session::apc_open()", "Set default store for APC");

        return true;
    }

    public function apc_close() { return true; }

    public function apc_read($id) {
        if(show_sessions_debug) {
            Debugger::insert_info("session::apc_read()", "Read Session-Data from APC");
            Debugger::insert_info("session::apc_read()", "Select ID: '".$id."'");
        }

        $key = $this->_prefix.'/'.$id;
        if (!apc_fetch($key)) return '';

        if ($this->_ttl) {
            $ts = apc_fetch($this->_prefix.'/TS');
            if (empty($ts[$id])) return '';
            else if (!empty($ts[$id]) && $ts[$id] + $this->_ttl < time()) {
                unset($ts[$id]);
                apc_delete($key);
                apc_store($this->_prefix.'/TS', $ts);
                return '';
            }
        }

        if (!$this->_lockTimeout) {
            $locks = apc_fetch($this->_prefix.'/LOCK');
            if (!empty($locks[$id])) {
                while (!empty($locks[$id]) && $locks[$id] + $this->_lockTimeout >= time()) {
                    usleep(10000);
                    $locks = apc_fetch($this->_prefix.'/LOCK');
                }
            }

            $locks[$id] = time();
            apc_store($this->_prefix.'/LOCK', $locks);
        }

        $data = apc_fetch($key);

        if(sessions_encode)
            $data = core::decrypt($data, $id);

        if(show_sessions_debug)
            Debugger::insert_successful("session::apc_read()", $data);

        return $data;
    }

    public function apc_write($id, $data) {
        if(show_sessions_debug) {
            Debugger::insert_info("session::apc_write()", "Write Session-Data to APC");
            Debugger::insert_info("session::apc_write()", "Select ID: '".$id."'");
        }

        $ts = apc_fetch($this->_prefix.'/TS');
        $ts[$id] = time();
        apc_store($this->_prefix.'/TS', $ts);

        $locks = apc_fetch($this->_prefix.'/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix.'/LOCK', $locks);

        if(show_sessions_debug)
            Debugger::insert_successful("session::apc_write()", $data);

        if(sessions_encode)
            $data = core::encrypt($data, $id);

        return apc_store($this->_prefix.'/'.$id, $data, $this->_ttl);
    }

    public function apc_destroy($id) {
        if(show_sessions_debug)
            Debugger::insert_info("session::apc_destroy()", "Call Session destroy");

        $ts = apc_fetch($this->_prefix.'/TS');
        unset($ts[$id]);
        apc_store($this->_prefix.'/TS', $ts);

        $locks = apc_fetch($this->_prefix.'/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix.'/LOCK', $locks);

        return apc_delete($this->_prefix.'/'.$id);
    }

    public function apc_gc($lifetime) {
        if(show_sessions_debug)
            Debugger::insert_info("session::apc_gc()", "Call Garbage-Collection");

        if ($this->_ttl)
            $lifetime = min($lifetime, $this->_ttl);

        $ts = apc_fetch($this->_prefix.'/TS');
        foreach ($ts as $id=>$time) {
            if ($time + $lifetime < time()) {
                apc_delete($this->_prefix.'/'.$id);
                unset($ts[$id]);
            }
        }

        return apc_store($this->_prefix.'/TS', $ts);
    }

    ###################################################
    ################## MySQL Backend ##################
    ###################################################
    public function sql_open() {
        global $sql_host,$sql_user,$sql_pass,$sql_pass,$sql_db;
        if(show_sessions_debug)
            Debugger::insert_info("session::sql_open()", "Connect to MySQL Server");

        if($this->db instanceOf mysqli) return false;
        $this->db = new mysqli(sessions_mysql_host, sessions_mysql_user, sessions_mysql_pass, sessions_mysql_db);
        if(show_sessions_debug) {
            if(!$this->db) {
                Debugger::insert_error("session::sql_open()", "Connect to MySQL Server failed!");
                Debugger::insert_error("session::sql_open()", "Host: ".sessions_mysql_host);
                Debugger::insert_error("session::sql_open()", "User: ".sessions_mysql_user);
                Debugger::insert_error("session::sql_open()", "DB: ".sessions_mysql_db);
            }
            else
                Debugger::insert_successful("session::sql_open()", "Connected to MySQL Server");
        }

        return !$this->db ? false : true;
    }

    public function sql_close() {
        if(show_sessions_debug)
            Debugger::insert_info("session::sql_close()", "Disconnect MySQL Server");

        return $this->db->close();
    }

    public function sql_read($id) {
        global $db_array;
        if(show_sessions_debug) {
            Debugger::insert_info("session::sql_read()", "Read Session-Data from Database");
            Debugger::insert_info("session::sql_read()", "Select ID: '".$id."'");
        }

        $data = null;
        if(!isset($this->read_stmt))
            $this->read_stmt = $this->db->prepare("SELECT data FROM dzcp_sessions WHERE id = ? LIMIT 1");

        if(!$this->read_stmt) return false;
        $this->read_stmt->bind_param('s', $id);
        $this->read_stmt->execute();
        $this->read_stmt->store_result();
        $this->read_stmt->bind_result($data);
        $this->read_stmt->fetch();
        $key = $this->sql_getkey($id);

        if(sessions_encode)
            $data = core::decrypt($data, $key);

        if(show_sessions_debug)
            Debugger::insert_successful("session::sql_read()", $data);

        return $data;
    }

    public function sql_write($id, $data) {
        global $db_array;
        if(show_sessions_debug) {
            Debugger::insert_info("session::sql_write()", "Write Session-Data to Database");
            Debugger::insert_info("session::sql_write()", "Select ID: '".$id."'");
        }

        $key = $this->sql_getkey($id);

        if(show_sessions_debug)
            Debugger::insert_successful("session::sql_write()", $data);

        if(sessions_encode)
            $data = core::encrypt($data, $key);

        $time = time();
        if(!isset($this->w_stmt))
            $this->w_stmt = $this->db->prepare("REPLACE INTO dzcp_sessions (id, set_time, data, session_key) VALUES (?, ?, ?, ?)");

        $this->w_stmt->bind_param('siss', $id, $time, $data, $key);
        return $this->w_stmt->execute();
    }

    public function sql_destroy($id) {
        global $db_array;
        if(show_sessions_debug)
            Debugger::insert_info("session::sql_destroy()", "Call Session destroy");

        if(!isset($this->delete_stmt))
            $this->delete_stmt = $this->db->prepare("DELETE FROM dzcp_sessions WHERE id = ?");

        $this->delete_stmt->bind_param('s', $id);
        return $this->delete_stmt->execute();
    }

    public function sql_gc($max) {
        global $db_array;
        if(show_sessions_debug)
            Debugger::insert_info("session::sql_gc()", "Call Garbage-Collection");

        if(!isset($this->gc_stmt))
            $this->gc_stmt = $this->db->prepare("DELETE FROM dzcp_sessions WHERE set_time < ?");

        $old = time() - $max;
        $this->gc_stmt->bind_param('s', $old);
        return $this->gc_stmt->execute();
    }

    ###################################################
    ##################### Private #####################
    ###################################################

    private function sql_getkey($id) {
        global $db_array;
        if(!isset($this->key_stmt))
            $this->key_stmt = $this->db->prepare("SELECT session_key FROM dzcp_sessions WHERE id = ? LIMIT 1");

        $this->key_stmt->bind_param('s', $id);
        $this->key_stmt->execute();
        $this->key_stmt->store_result();
        if($this->key_stmt->num_rows == 1) {
            $key = null;
            $this->key_stmt->bind_result($key);
            $this->key_stmt->fetch();
            return $key;
        } else {
            $random_key = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            return $random_key;
        }
    }

    private function is_session_started() {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') )
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            else
                return session_id() === '' ? false : true;
        }

        return false;
    }
}