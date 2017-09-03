<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Lib;
defined('BASE') or exit('Access Denied!');

// WARNING!! Kelas ini belum fix, saya stuck di kelas ini. maaf :)
// TODO: Selesaikan semua kueri untuk tiap-tiap database driver

class Session {
    public $data=[];
    protected $started=false;
    protected $config;
    protected $usingdb;
    protected $container;
    protected $table;
    protected $cookie;
    protected $data_exist;
    protected $dbs;
    protected $driver;
    protected $info=[];

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->start();
        $this->config=summon('config');
        $this->usingdb=$this->config->get('use_database','session');
        $this->container=$this->config->get('database_container','session');
        $this->table=$this->config->get('table_name','session');
        $this->cookie=$this->config->get('cookie_name','session');
        $this->driver=strtolower($this->config->get($this->container,'db')['driver']);
        $this->info=[
            'cid'=>$this->config->get('id_column_name','session'),
            'ctoken'=>$this->config->get('token_column_name','session'),
            'cip'=>$this->config->get('ip_address_column_name','session'),
            'clastseen'=>$this->config->get('last_seen_column_name','session'),
            'cdata'=>$this->config->get('session_data_column_name','session')
        ];
        $this->load=summon('loader');
        $this->web=summon('web');
        if ($this->usingdb==true) {
            if (!array_key_exists($this->container,$this->config->getall('db')))
                abort("Database credentials for '%s' can't be found on config file",[$this->container]);
            else $this->dbs=$this->load->db($this->container);
            if (!$this->dbexist())
                $this->maketable();
            $this->data_exist=false;
        }
        else $this->cache=$this->load->lib('cache');
        if (!$this->check())
            $this->create();
    }

    /**
    *   Memulai sesi
    */
    protected function start() {
        if (!$this->started) {
            session_start();
            $this->started=true;
        }
    }

    /**
    *   Buat token sesi
    */
    protected function create() {
        $this->data[$this->info['ctoken']]=substr(sha1(base64_encode(md5(utf8_encode(microtime(1))))),0,20);
    }

    /**
    *   Cek status sesi dan cookie
    */
    protected function check() {
        $cookie=$this->web->cookie($this->cookie);
        if ($cookie==false)
            return false;
        $token=base64_decode($cookie);
        if ($this->usingdb==true) {
            if (strpos($this->driver,'sql')!==false) {
                $this->driver='sql';
                $result=$this->dbs
                    ->table($this->table)
                    ->where($this->info['ctoken'],$token)
                    ->one();
                $count=count($result);
                $this->dbs->reset();
            }
            elseif (strpos($this->driver,'mongo')!==false) {
                $this->driver='mongo';
                $result=null; // TODO: mongo select document based on 'ctoken'
                $count=count($result);
            }
            elseif (strpos($this->driver,'jong')!==false) {
                $this->driver='jong';
                // BELUM BERJALAN
                $result=$this->dbs
                    ->table($this->table)
                    ->where([$this->info['ctoken']=>$token])
                    ->all();
                $count=count($result);
            }
            if ($count>0) {
                $this->data_exist=true;
                $result[$this->info['cdata']]=unserialize($result[$this->info['cdata']]);
            }
        }
        else {
            $result=$this->cache->read($this->table.$token);
            $count=count($result);
        }
        if ($count>0) {
            $this->data[$this->info['ctoken']]=$result[$this->info['ctoken']];
            if ($result[$this->info['cip']]==$this->web->ip()) {
                if (count($result[$this->info['cdata']])>0)
                    foreach($result[$this->info['cdata']] as $key=>$value)
                        $this->set($key,$value);
                $this->data[$this->info['clastseen']]=time();
                return true;
            }
            else $this->destroy();
        }
        return false;
    }

    /**
    *   Menyimpan sesi
    */
    function save() {
        $cookie_data=base64_encode($this->data[$this->info['ctoken']]);
        $this->web->setcookie($this->cookie,$cookie_data);
        $data=[
            $this->info['ctoken']=>$this->data[$this->info['ctoken']],
            $this->info['cip']=>$this->web->ip(),
            $this->info['clastseen']=>time()
        ];
        if ($this->usingdb==true) {
            if ($this->data_exist==false) {
                $data[$this->info['cdata']]=serialize($this->data);
                if ($this->driver=='sql') {
                    $this->dbs
                        ->table($this->table)
                        ->insert($data)
                        ->execute();
                    $result=$this->dbs->numrows;
                    $this->dbs->reset();
                }
                elseif ($this->driver=='mongo') {
                    // TODO: mongo insert data to collection
                    $result=0;
                }
                elseif ($this->driver=='jong') {
                    // BELUM BERJALAN
                    $result=$this->dbs->table($this->table)->insert([$data]);
                }
            }
            else return $this->renew();
            if ($result>0)
                return true;
            return false;
        }
        else {
            $data[$this->info['cdata']]=$this->data;
            return $this->cache
                ->write($this->table.$data[$this->info['ctoken']],$data,(60*60*24*365));
        }
    }

    /**
    *   Hapus semua sesi
    */
    function destroy() {
        $ssid=base64_encode($this->data[$this->info['ctoken']]);
        $this->web->setcookie($this->cookie,$ssid,time()-1);
        if ($this->usingdb==true) {
            if ($this->driver=='sql') {
                $this->dbs
                    ->table($this->table)
                    ->where($this->info['ctoken'],$this->data[$this->info['ctoken']])
                    ->delete()
                    ->execute();
                $this->dbs->reset();
            }
            elseif ($this->driver=='mongo') {
                // TODO: mongo delete data from collection
            }
            elseif ($this->driver=='jong') {
                // BELUM BERJALAN
                $id=$db->table($this->table)
                    ->find($this->data[$this->info['ctoken']],$this->info['ctoken'])['id'];
                $db->table($this->table)
                    ->delete($id);
            }
        }
        else $this->cache->delete($this->table.$this->data[$this->info['ctoken']]);
        $this->data=[];
        $this->started=false;
        session_destroy();
        $this->start();
        $this->create();
    }

    /**
    *   Get data sesi
    *   @param $name string
    */
    function get($name) {
        if (isset($this->data[$name]))
            return $this->data[$name];
        return null;
    }

    /**
    *   Set data sesi
    *   @param $name string
    *   @param $value mixed
    */
    function set($name,$value=null) {
        if (is_array($name))
            foreach ($name as $key=>$value)
                $this->data[$key]=$value;
        else $this->data[$name]=$value;
    }

    /**
    *   Cek eksistensi session
    *   @param $name string
    */
    function exists($name) {
        return array_key_exists($name,$this->data);
    }

    /**
    *   Hapus data sesi
    *   @param $name string
    */
    function delete($name) {
        unset($this->data[$name]);
    }

    /**
    *   Perbarui waktu sesi
    */
    protected function renew() {
        if ($this->usingdb==true) {
            $data=[
                $this->info['clastseen']=>time(),
                $this->info['cdata']=>serialize($this->data)
            ];
            if ($this->driver=='sql') {
                $update=$this->dbs
                    ->table($this->table)
                    ->where($this->info['ctoken'],$this->data[$this->info['ctoken']])
                    ->update($data)
                    ->execute();
            }
            elseif ($this->driver=='mongo') {
                $update=null;
                // TODO: mongo update data
            }
            elseif ($this->driver=='jong') {
                // BELUM BERJALAN
                $id=$db->table($this->table)
                    ->find($this->data[$this->info['ctoken']],$this->info['ctoken']);
                $update=$db->table($this->table)
                    ->update($id,[$data]);
            }
            return $update;
        }
    }

    /**
    *   Cek tabel sesi sudah dibuat atau belum
    */
    protected function dbexist() {
        if (strpos($this->driver,'sql')!==false) {
            $this->dbs
                ->table('information_schema.TABLES')
                ->where('TABLE_SCHEMA',$this->config->get($this->container,'db')['database'])
                ->where('TABLE_NAME',$this->table)
                ->limit(1)
                ->execute();
            $result=$this->dbs->numrows;
        }
        elseif (strpos($this->driver,'mongo')!==false) {
            $result=0;
            // TODO: mongo check collection existance
        }
        elseif (strpos($this->driver,'jong')!==false) {
            if (file_exists(RES.'jong'.DS.$this->table.DS.'meta.php'))
                $result=1;
            else $result=0;
        }
        if ($result>0)
            return true;
        else return false;
    }

    /**
    *   Buat tabel sesi
    */
    protected function maketable() {
        if (strpos($this->driver,'sql')!==false) {
            $this->driver='sql';
            $query="CREATE TABLE IF NOT EXISTS `{$this->table}` (
              `{$this->info['cid']}` INT(11) NOT NULL AUTO_INCREMENT,
              `{$this->info['ctoken']}` VARCHAR(25) NOT NULL DEFAULT '',
              `{$this->info['cip']}` VARCHAR(50) DEFAULT NULL,
              `{$this->info['clastseen']}` VARCHAR(50) DEFAULT NULL,
              `{$this->info['cdata']}`  TEXT,
              PRIMARY KEY(`{$this->info['cid']}`,`{$this->info['ctoken']}`)
          ) DEFAULT CHARSET=UTF8;";
            $this->dbs
                ->sql($query)
                ->execute();
            $this->dbs->reset();
        }
        elseif (strpos($this->driver,'mongo')!==false) {
            $this->driver='mongo';
            // TODO: mongo create collection statement
        }
        elseif (strpos($this->driver,'jong')!==false) {
            $this->driver='jong';
            $this->dbs
                ->table($this->table)
                ->insert([
                    $this->info['cid']=>'none',
                    $this->info['ctoken']=>'none',
                    $this->info['cip']=>'none',
                    $this->info['clastseen']=>'none',
                    $this->info['cdata']=>'none',
                ]);
            $this->dbs
                ->table($this->table)
                ->indexes(['id',$this->info['ctoken']]);
        }
    }
}
