<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Db\Sql;
defined('BASE') or exit('Access Denied!');


class Mapper {
    public $lastquery;
    public $numrows;
    public $lastid;
    public $affectedrows;
    public $iscached=false;
    public $staton=false;
    public $showsql=false;
    public $keyprefix='';
    public $dbinfo=null;
    protected $table;
    protected $where;
    protected $joins;
    protected $order;
    protected $groups;
    protected $having;
    protected $distinct;
    protected $limit;
    protected $offset;
    protected $sql;
    protected $db;
    protected $dbtype;
    protected $cache;
    protected $cachetype;
    protected $stats;
    protected $querytime;
    protected $class;
    protected static $dbdriver=['pdo','mysqli','mysql','pgsql','sqlite','sqlite3'];
    protected static $cachedriver=['memcached','memcache','xcache'];

    /**
    *   Konstruktor class
    */
    function __construct($info=[]) {
        $this->dbinfo=$info;
        try {
            $this->setdb(strtolower($info['driver']).'://'.$info['username'].':'.
                $info['password'].'@'.$info['host'].':'.
                (isset($info['port'])?$info['port']:'3306').'/'.$info['database']);
        }
        catch (\PDOException $e) {
            seterror($e->getMessage(),E_ERROR);
        }
        if (!empty($info['cache']))
            $this->setcache(strtolower($info['cache']));
    }

    /**
    *   Membangun query sql
    *   @param $sql string
    *   @param $input string
    */
    function build($sql,$input) {
        return (strlen($input)>0)?($sql.' '.$input):$sql;
    }

    /**
    *   Parse string koneksi database menjadi objek
    *   @param $dsn string
    */
    function connect($dsn) {
        $url=parse_url($dsn);
        if (empty($url))
            abort("Invalid connection string");
        $conn=[];
        $conn['driver']=isset($url['scheme'])?$url['scheme']:$url['path'];
        $conn['hostname']=isset($url['host'])?$url['host']:null;
        $conn['database']=isset($url['path'])?substr($url['path'],1):null;
        $conn['username']=isset($url['user'])?$url['user']:null;
        $conn['password']=isset($url['pass'])?$url['pass']:null;
        $conn['port']=isset($url['port'])?$url['port']:null;
        return $conn;
    }

    /**
    *   Mengambil statistik query
    */
    function statistics() {
        $this->stats['total_time']=0;
        $this->stats['num_queries']=0;
        $this->stats['num_rows']=0;
        $this->stats['num_changes']=0;
        if (isset($this->stats['queries'])) {
            foreach ($this->stats['queries'] as $query) {
                $this->stats['total_time']+=$query['time'];
                $this->stats['num_queries']+=1;
                $this->stats['num_rows']+=$query['rows'];
                $this->stats['num_changes']+=$query['changes'];
            }
        }
        $this->stats['avg_query_time']=$this->stats['total_time']/
            (float)(($this->stats['num_queries']>0)?$this->stats['num_queries']:1);
        return $this->stats;
    }

    /**
    *   Cek apakah properti tabel telah di-set
    */
    function checktable() {
        if (!$this->table)
            abort("Table is not defined.",null,E_ERROR);
    }

    /**
    *   Cek apakah properti kelas telah di-set
    */
    function checkclass() {
        if (!$this->class)
            abort("Class is not defined.",null,E_ERROR);
    }

    /**
    *   Reset properti kelas
    */
    function reset() {
        $this->where='';
        $this->joins='';
        $this->order='';
        $this->groups='';
        $this->having='';
        $this->distinct='';
        $this->limit='';
        $this->offset='';
        $this->sql='';
    }

    /**
    *   Parse statement kondisional
    *   @param $field string
    *   @param $value string
    *   @param $join string
    *   @param $escape int
    */
    protected function parsecondition($field,$value=null,$join='',$escape=true) {
        if (is_string($field)) {
            if ($value===null)
                return $join.' '.trim($field);
            $operator='';
            if (strpos($field,' ')!==false)
                list ($field,$operator)=explode(' ',$field);
            if (!empty($operator)) {
                switch ($operator) {
                    case '%':
                        $condition=' LIKE ';
                        break;
                    case '!%':
                        $condition=' NOT LIKE ';
                        break;
                    case '@':
                        $condition=' IN ';
                        break;
                    case '!@':
                        $condition=' NOT IN ';
                        break;
                    default:
                        $condition=$operator;
                }
            }
            else $condition='=';
            if (empty($join))
                $join=($field{0}=='|')?' OR':' AND';
            if (is_array($value)) {
                if (strpos($operator,'@')===false)
                    $condition=' IN ';
                $value='('.implode(',',array_map([$this,'quote'],$value)).')';
            }
            else $value=($escape&&!is_numeric($value))?$this->quote($value):$value;
            return $join.' '.str_replace('|','',$field).$condition.$value;
        }
        else if (is_array($field)) {
            $str='';
            foreach ($field as $key=>$value) {
                $str.=$this->parsecondition($key,$value,$join,$escape);
                $join='';
            }
            return $str;
        }
        else abort('Invalid where condition.',null,E_ERROR);
    }

    /**
    *   Set tabel yang akan dioperasikan
    *   @param $table string
    *   @param $reset int
    */
    function table($table,$reset=true) {
        $this->table=$table;
        if ($reset)
            $this->reset();
        return $this;
    }

    /**
    *   Tambahkan klausa join
    *   @param $table string
    *   @param $fields array
    *   @param $type string
    */
    function join($table,array $fields,$type='INNER') {
        static $joins=['INNER','LEFT OUTER','RIGHT OUTER','FULL OUTER'];
        if (!in_array($type,$joins))
            abort('Invalid join type.',null,E_ERROR);
        $this->joins.=' '.$type.' JOIN '.$table.$this->parsecondition($fields,null,' ON',false);
        return $this;
    }

    /**
    *   Tambahkan klausa left join
    *   @param $table string
    *   @param $fields array
    */
    function leftjoin($table,array $fields) {
        return $this->join($table,$fields,'LEFT OUTER');
    }

    /**
    *   Tambahkan klausa right join
    *   @param $table string
    *   @param $fields array
    */
    function rightjoin($table,array $fields) {
        return $this->join($table,$fields,'RIGHT OUTER');
    }

    /**
    *   Tambahkan klausa full join
    *   @param $table string
    *   @param $fields array
    */
    function fulljoin($table,array $fields) {
        return $this->join($table,$fields,'FULL OUTER');
    }

    /**
    *   Tambahkan klausa where
    *   @param $field string|array
    *   @param $value string
    */
    function where($field,$value=null) {
        $this->where.=$this->parsecondition($field,$value,((empty($this->where))?'WHERE':''));
        return $this;
    }

    /**
    *   Tambahkan klausa asc untuk order by
    *   @param $field string
    */
    function ascend($field) {
        return $this->orderby($field,'ASC');
    }

    /**
    *   Tambahkan klausa desc untuk order by
    *   @param $field string
    */
    function descend($field) {
        return $this->orderby($field,'DESC');
    }

    /**
    *   Tambahkan klausa order by
    *   @param $field string|array
    *   @param $direction string
    */
    function orderby($field,$direction='ASC') {
        $direction=strtoupper($direction);
        if (is_array($field))
            foreach ($field as $key=>$value)
                $field[$key]=$value.' '.$direction;
        else $field.=' '.$direction;
        $fields=(is_array($field))?implode(', ',$field):$field;
        $this->order.=((empty($this->order))?'ORDER BY':',').' '.$fields;
        return $this;
    }

    /**
    *   Tambahkan klausa group by
    *   @param $field string|array
    */
    function groupby($field) {
        $this->groups.=((empty($this->order))?'GROUP BY':',').' '.
            ((is_array($field))?implode(',',$field):$field);
        return $this;
    }

    /**
    *   Tambahkan klausa having
    *   @param $field string|array
    *   @param $value string
    */
    function having($field,$value=null) {
        $this->having.=$this->parsecondition($field,$value,((empty($this->having))?'HAVING':''));
        return $this;
    }

    /**
    *   Tambahkan klausa limit
    *   @param $limit int
    *   @param $offset int
    */
    function limit($limit,$offset=null) {
        if ($limit!==null)
            $this->limit='LIMIT '.$limit;
        if ($offset!==null)
            $this->offset($offset);
        return $this;
    }

    /**
    *   Tambahkan klausa offset
    *   @param $offset int
    *   @param $limit int
    */
    function offset($offset,$limit=null) {
        if ($offset!==null)
            $this->offset='OFFSET '.$offset;
        if ($limit!==null)
            $this->limit($limit);
        return $this;
    }

    /**
    *   Tambahkan klausa distinct
    *   @param $value string
    */
    function distinct($value=true) {
        $this->distinct=($value)?'DISTINCT':'';
        return $this;
    }

    /**
    *   Tambahkan klausa between
    *   @param $field string
    *   @param $value1 mixed
    *   @param $value2 mixed
    */
    function between($field,$value1,$value2) {
        $this->where(sprintf('%s BETWEEN %s AND %s',$field,$this->quote($value1),$this->quote($value2)));
    }

    /**
    *   Membuat kueri select
    *   @param $fields string
    *   @param $limit int
    *   @param $offset int
    */
    function select($fields='*',$limit=null,$offset=null) {
        $this->checktable();
        $fields=(is_array($fields))?implode(',',$fields):$fields;
        $this->limit($limit,$offset);
        $this->sql([
            'SELECT',$this->distinct,$fields,'FROM',$this->table,
            $this->joins,$this->where,$this->groups,$this->having,
            $this->order,$this->limit,$this->offset
        ]);
        return $this;
    }

    /**
    *   Membuat kueri insert
    *   @param $data array
    */
    function insert(array $data) {
        $this->checktable();
        if (empty($data))
            return $this;
        $this->sql(['INSERT INTO',$this->table,'('.implode(',',array_keys($data)).')',
            'VALUES','('.implode(',',array_values(array_map([$this,'quote'],$data))).')']);
        return $this;
    }

    /**
    *   Membuat kueri update
    *   @param $data string|array
    */
    function update($data) {
        $this->checktable();
        if (empty($data))
            return $this;
        $values=[];
        if (is_array($data))
            foreach ($data as $key=>$value)
                $values[]=(is_numeric($key))?$value:$key.'='.$this->quote($value);
        else $values[]=(string)$data;
        $this->sql(['UPDATE',$this->table,'SET',implode(',',$values),$this->where]);
        return $this;
    }

    /**
    *   Membuat kueri delete
    *   @param $where string|array
    */
    function delete($where=null) {
        $this->checktable();
        if ($where!==null)
            $this->where($where);
        $this->sql(['DELETE FROM',$this->table,$this->where]);
        return $this;
    }

    /**
    *   Get/set statement sql
    *   @param $sql array
    */
    function sql($sql=null) {
        if ($sql!==null) {
            $this->sql=trim((is_array($sql))?array_reduce($sql,[$this,'build']):$sql);
            return $this;
        }
        return $this->sql;
    }

    /**
    *   Set koneksi database
    *   @param $db string|array
    */
    function setdb($db) {
        $this->db=null;
        if (is_string($db))
            $this->setdb($this->connect($db));
        else if (is_array($db)) {
            switch ($db['driver']) {
                case 'mysql':
                case 'mysqli':
                    $this->db=new \MySQLi($db['hostname'],$db['username'],$db['password'],$db['database']);
                    if ($this->db->connect_error)
                        abort('Connection error: %s',[$this->db->connect_error],E_ERROR);
                    break;
                case 'pgsql':
                    $str=sprintf('host=%s dbname=%s user=%s password=%s',
                        $db['hostname'],$db['database'],$db['username'],$db['password']);
                    $this->db=pg_connect($str);
                    break;
                case 'sqlite':
                    $this->db=sqlite_open($db['database'],0666,$error);
                    if (!$this->db)
                        abort("Connection error: %s",[$error],E_ERROR);
                    break;
                case 'sqlite3':
                    $this->db=new \SQLite3($db['database']);
                    break;
                case 'pdomysql':
                    $dsn=sprintf('mysql:host=%s;port=%d;dbname=%s',$db['hostname'],
                    isset($db['port'])?$db['port']:3306,$db['database']);
                    $this->db=new \PDO($dsn,$db['username'],$db['password']);
                    $db['driver']='pdo';
                    break;
                case 'pdopgsql':
                    $dsn=sprintf('pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',$db['hostname'],
                    isset($db['port'])?$db['port']:5432,$db['database'],$db['username'],$db['password']);
                    $this->db=new \PDO($dsn);
                    $db['driver']='pdo';
                    break;
                case 'pdosqlite':
                    $this->db=new \PDO('sqlite:/'.$db['database']);
                    $db['driver']='pdo';
                    break;
            }
            if ($this->db==null)
                abort('Undefined database.',null,E_ERROR);
            $this->dbtype=$db['driver'];
        }
        else {
            $type=$this->dbtype($db);
            if (!in_array($type,self::$dbdriver))
                abort('Invalid database type.',null,E_ERROR);
            $this->db=$db;
            $this->dbtype=$type;
        }
    }

    /**
    *   Get koneksi db
    */
    function getdb() {
        return $this->db;
    }

    /**
    *   Melihat tipre driver database
    *   @param $db object|resource
    */
    function dbtype($db) {
        if (is_object($db))
            return strtolower(get_class($db));
        else if (is_resource($db)) {
            switch (get_resource_type($db)) {
                case 'mysql link':
                    return 'mysql';
                case 'sqlite database':
                    return 'sqlite';
                case 'pgsql link':
                    return 'pgsql';
            }
        }
        return null;
    }

    /**
    *   Ekseskusi statement sql
    *   @param $key string
    *   @param $expire int
    */
    function execute($key=null,$expire=0) {
        if (!$this->db)
            abort('Database is not defined.',null,E_ERROR);
        if ($key!==null) {
            $result=$this->fetch($key);
            if ($this->iscached)
                return $result;
        }
        $result=null;
        $this->iscached=false;
        $this->numrows=0;
        $this->affectedrows=0;
        $this->lastid=-1;
        $this->lastquery=$this->sql;
        if ($this->staton) {
            if (empty($this->stats))
                $this->stats=['queries'=>[]];
            $this->querytime=microtime(true);
        }
        if (!empty($this->sql)) {
            $error=null;
            switch ($this->dbtype) {
                case 'pdo':
                    try {
                        $result=$this->db->prepare($this->sql);
                        if (!$result)
                            $error=$this->db->errorInfo();
                        else {
                            $result->execute();
                            $this->numrows=$result->rowCount();
                            $this->affectedrows=$result->rowCount();
                            $this->lastid=$this->db->lastInsertId();
                        }
                    }
                    catch(PDOException $ex) {
                        $error=$ex->getMessage();
                    }
                    break;
                case 'mysql':
                case 'mysqli':
                    $result=$this->db->query($this->sql);
                    if (!$result)
                        $error=$this->db->error;
                    else {
                        if (is_object($result))
                            $this->numrows=$result->num_rows;
                        else $this->affectedrows=$this->db->affected_rows;
                        $this->lastid=$this->db->insert_id;
                    }
                    break;
                case 'pgsql':
                    $result=pg_query($this->db,$this->sql);
                    if (!$result)
                        $error=pg_last_error($this->db);
                    else {
                        $this->numrows=pg_num_rows($result);
                        $this->affectedrows=pg_affected_rows($result);
                        $this->lastid=pg_last_oid($result);
                    }
                    break;
                case 'sqlite':
                    $result=sqlite_query($this->db,$this->sql,SQLITE_ASSOC,$error);
                    if ($result!==false) {
                        $this->numrows=sqlite_num_rows($result);
                        $this->affectedrows=sqlite_changes($this->db);
                        $this->lastid=sqlite_last_insert_rowid($this->db);
                    }
                    break;
                case 'sqlite3':
                    $result=$this->db->query($this->sql);
                    if ($result===false)
                        $error=$this->db->lastErrorMsg();
                    else {
                        $this->numrows=0;
                        $this->affectedrows=($result)?$this->db->changes():0;
                        $this->lastid=$this->db->lastInsertRowId();
                    }
                    break;
            }
            if ($error!==null) {
                if ($this->showsql)
                    $error.="\nSQL: ".$this->sql;
                abort("Database error: %s",[$error],E_ERROR);
            }
        }
        if ($this->staton) {
            $this->stats['queries'][]=[
                'query'=>$this->sql,
                'time'=>microtime(true)-$this->querytime,
                'rows'=>(int)$this->numrows,
                'changes'=>(int)$this->affectedrows
            ];
        }
        return $result;
    }

    /**
    *   Ambil semua baris hasil kueri
    *   @param $key string
    *   @param $expire int
    */
    function many($key=null,$expire=0) {
        if (empty($this->sql))
            $this->select();
        $data=[];
        $result=$this->execute($key,$expire);
        if ($this->iscached) {
            $data=$result;
            if ($this->staton)
                $this->stats['cached'][$this->keyprefix.$key]=$this->sql;
        }
        else {
            switch ($this->dbtype) {
                case 'pdo':
                    $data=$result->fetchAll(PDO::FETCH_ASSOC);
                    $this->numrows=sizeof($data);
                    break;
                case 'mysql':
                case 'mysqli':
                    if (function_exists('mysqli_fetch_all'))
                        $data=$result->fetch_all(MYSQLI_ASSOC);
                    else while ($row=$result->fetch_assoc())
                        $data[]=$row;
                    $result->close();
                    break;
                case 'pgsql':
                    $data=pg_fetch_all($result);
                    pg_free_result($result);
                    break;
                case 'sqlite':
                    $data=sqlite_fetch_all($result,SQLITE_ASSOC);
                    break;
                case 'sqlite3':
                    if ($result) {
                        while ($row=$result->fetchArray(SQLITE3_ASSOC))
                            $data[]=$row;
                        $result->finalize();
                        $this->numrows=sizeof($data);
                    }
                    break;
            }
        }
        if (!$this->iscached&&$key!==null)
            $this->store($key,$data,$expire);
        return $data;
    }

    /**
    *   Ambil hanya satu baris hasil kueri
    *   @param $key string
    *   @param $expire int
    */
    function one($key=null,$expire=0) {
        if (empty($this->sql))
            $this->limit(1)->select();
        return (!empty($this->many($key,$expire)))?$this->many($key,$expire)[0]:[];
    }

    /**
    *   Ambil sebuah value dari field
    *   @param $name string
    *   @param $key string
    *   @param $expire int
    */
    function value($name,$key=null,$expire=0) {
        $row=$this->one($key,$expire);
        return (!empty($row))?$row[$name]:null;
    }

    /**
    *   Ambil value minimal dari field
    *   @param $field string
    *   @param $key string
    *   @param $expire int
    */
    function min($field,$key=null,$expire=0) {
        $this->select('MIN('.$field.') min_value');
        return $this->value('min_value',$key,$expire);
    }

    /**
    *   Ambil value maksimal dari field
    *   @param $field string
    *   @param $key string
    *   @param $expire int
    */
    function max($field,$key=null,$expire=0) {
        $this->select('MAX('.$field.') max_value');
        return $this->value('max_value',$key,$expire);
    }

    /**
    *   Ambil penjumlahan value dari field
    *   @param $field string
    *   @param $key string
    *   @param $expire int
    */
    function sum($field,$key=null,$expire=0) {
        $this->select('SUM('.$field.') sum_value');
        return $this->value('sum_value',$key,$expire);
    }

    /**
    *   Ambil rata-rata value dari field
    *   @param $field string
    *   @param $key string
    *   @param $expire int
    */
    function avg($field,$key=null,$expire=0) {
        $this->select('AVG('.$field.') avg_value');
        return $this->value('avg_value',$key,$expire);
    }

    /**
    *   Ambil jumlah total data dalam field
    *   @param $field string
    *   @param $key string
    *   @param $expire int
    */
    function count($field='*',$key=null,$expire=0) {
        $this->select('COUNT('.$field.') num_rows');
        return $this->value('num_rows',$key,$expire);
    }

    /**
    *   Tambahkan quote
    *   @param $value string
    */
    function quote($value) {
        if ($value===null) return 'NULL';
        if (is_string($value)) {
            if ($this->db!==null) {
                switch ($this->dbtype) {
                    case 'pdo':
                        return $this->db->quote($value);
                    case 'mysql':
                    case 'mysqli':
                        return "'".$this->db->real_escape_string($value)."'";
                    case 'pgsql':
                        return "'".pg_escape_string($this->db,$value)."'";
                    case 'sqlite':
                        return "'".sqlite_escape_string($value)."'";
                    case 'sqlite3':
                        return "'".$this->db->escapeString($value)."'";
                }
            }
            $value=str_replace(
                ['\\',"\0","\n","\r","'",'"',"\x1a"],
                ['\\\\','\\0','\\n','\\r',"\\'",'\\"','\\Z'],
                $value
            );
            return "'$value'";
        }
        return $value;
    }

    /**
    *   Set driver cache
    *   @param $cache string
    */
    function setcache($cache) {
        $this->cache=null;
        if (is_string($cache)) {
            if ($cache{0}=='.'||$cache{0}=='/') {
                $this->cache=$cache;
                $this->cachetype='file';
            }
            else $this->setcache($this->connect($cache));
        }
        else if (is_array($cache)) {
            switch ($cache['driver']) {
                case 'memcache':
                    $this->cache=new \Memcache();
                    $this->cache->connect($cache['hostname'],$cache['port']);
                    break;
                case 'memcached':
                    $this->cache=new \Memcached();
                    $this->cache->addServer($cache['hostname'],$cache['port']);
                    break;
                default: $this->cache=$cache['driver'];
            }
            $this->cachetype=$cache['driver'];
        }
        else if (is_object($cache)) {
            $type=strtolower(get_class($cache));
            if (!in_array($type,self::$cachedriver))
                abort("Invalid or unsupported cache type '%s'",[$type],E_ERROR);
            $this->cache=$cache;
            $this->cachetype=$type;
        }
    }

    /**
    *   Get instance dari cache
    */
    function getcache() {
        return $this->cache;
    }

    /**
    *   Simpan value ke cache
    *   @param $key string
    *   @param $value string
    *   @param $expire int
    */
    function store($key,$value,$expire=0) {
        $key=$this->keyprefix.$key;
        switch ($this->cachetype) {
            case 'memcached':
                $this->cache->set($key,$value,$expire);
                break;
            case 'memcache':
                $this->cache->set($key,$value,0,$expire);
                break;
            case 'apc':
                apc_store($key,$value,$expire);
                break;
            case 'xcache':
                xcache_set($key,$value,$expire);
                break;
            case 'file':
                $file=RES.'cache'.DS.$this->cache.DS.md5($key);
                $data=[
                    'value'=>$value,
                    'expire'=>($expire>0)?(time()+$expire):0
                ];
                file_put_contents($file,serialize($data));
                break;
            default: $this->cache[$key]=$value;
        }
    }

    /**
    *   Ambil value dari cache
    *   @param $key string
    */
    function fetch($key) {
        $key=$this->keyprefix.$key;
        switch ($this->cachetype) {
            case 'memcached':
                $value=$this->cache->get($key);
                $this->iscached=($this->cache->getResultCode()==Memcached::RES_SUCCESS);
                return $value;
            case 'memcache':
                $value=$this->cache->get($key);
                $this->iscached=($value!==false);
                return $value;
            case 'apc':
                return apc_fetch($key,$this->iscached);
            case 'xcache':
                $this->iscached=xcache_isset($key);
                return xcache_get($key);
            case 'file':
                $file=RES.'cache'.DS.$this->cache.DS.md5($key);
                if ($this->iscached=file_exists($file)) {
                    $data=unserialize(file_get_contents($file));
                    if ($data['expire']==0||time()<$data['expire'])
                        return $data['value'];
                    else $this->iscached=false;
                }
                break;
            default: return $this->cache[$key];
        }
        return null;
    }

    /**
    *   Hapus value dari cache
    *   @param $key string
    */
    function clear($key) {
        $key=$this->keyprefix.$key;
        switch ($this->cachetype) {
            case 'memcached': return $this->cache->delete($key);
            case 'memcache': return $this->cache->delete($key);
            case 'apc': return apc_delete($key);
            case 'xcache': return xcache_unset($key);
            case 'file':
                $file=RES.'cache'.DS.$this->cache.DS.md5($key);
                if (file_exists($file))
                    return unlink($file);
                return false;
            default:
                if (isset($this->cache[$key])) {
                    unset($this->cache[$key]);
                    return true;
                }
                return false;
        }
    }

    /**
    *   Kosongkan cache
    */
    function flush() {
        switch ($this->cachetype) {
            case 'memcached':
                $this->cache->flush();
                break;
            case 'memcache':
                $this->cache->flush();
                break;
            case 'apc':
                apc_clear_cache();
                break;
            case 'xcache':
                xcache_clear_cache();
                break;
            case 'file':
                if ($handle=opendir($this->cache)) {
                    while (false!==($file=readdir($handle)))
                        if ($file!='.'&&$file!='..')
                            unlink(RES.'cache'.DS.$this->cache.DS.$file);
                    closedir($handle);
                }
                break;
            default: $this->cache=[]; break;
        }
    }

    /**
    *   Set kelas untuk objek
    *   @param $class string
    */
    function using($class) {
        if (is_string($class))
            $this->class=$class;
        else if (is_object($class))
            $this->class=get_class($class);
        $this->reset();
        return $this;
    }

    /**
    *   Muat properti untuk sebuah objek
    *   @param $object object
    *   @param $data array
    */
    function load($object,array $data) {
        foreach ($data as $key=>$value)
            if (property_exists($object,$key))
                $object->$key=$value;
        return $object;
    }

    /**
    *   Cari dan keluarkan objek
    *   @param $alue mixed
    *   @param $key string
    */
    function find($value=null,$key=null) {
        $this->checkclass();
        $properties=$this->properties();
        $this->table($properties->table,false);
        if ($value!==null) {
            if (is_int($value)&&property_exists($properties,'id_field'))
                $this->where($properties->id_field,$value);
            else if (is_string($value)&&property_exists($properties,'name_field'))
                $this->where($properties->name_field,$value);
            else if (is_array($value))
                $this->where($value);
        }
        if (empty($this->sql))
            $this->select();
        $data=$this->many($key);
        $objects=[];
        foreach ($data as $row)
            $objects[]=$this->load(new $this->class(),$row);
        return (sizeof($objects)==1)?$objects[0]:$objects;
    }

    /**
    *   Simpan objek ke database
    *   @param $object object
    *   @param $data array
    */
    function save($object,array $fields=null) {
        $this->using($object);
        $properties=$this->properties();
        $this->table($properties->table);
        $data=get_object_vars($object);
        $id=$object->{$properties->id_field};
        unset($data[$properties->id_field]);
        if ($id===null) {
            $this->insert($data)->execute();
            $object->{$properties->id_field}=$this->lastid;
        }
        else {
            if ($fields!==null) {
                $keys=array_flip($fields);
                $data=array_intersect_key($data,$keys);
            }
            $this->where($properties->id_field,$id)->update($data)->execute();
        }
        return $this->class;
    }

    /**
    *   Hapus objek dari database
    *   @param $object object
    */
    function remove($object) {
        $this->using($object);
        $properties=$this->properties();
        $this->table($properties->table);
        $id=$object->{$properties->id_field};
        if ($id!==null)
            $this->where($properties->id_field,$id)->delete()->execute();
    }

    /**
    *   Ambil properti kelas
    */
    function properties() {
        static $properties=[];
        if (!$this->class)
            return [];
        if (!isset($properties[$this->class])) {
            static $defaults=[
                'table'=>null,
                'id_field'=>null,
                'name_field'=>null
            ];
            $reflection=new \ReflectionClass($this->class);
            $config=$reflection->getStaticProperties();
            $properties[$this->class]=(object)array_merge($defaults,$config);
        }
        return $properties[$this->class];
    }
}
