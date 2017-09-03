<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Db\Mongo;
defined('BASE') or exit('Access Denied!');


class Mapper {
    public $wheres=[];
    public $updates=[];
    private $dsn='';
    protected $conn=null;
    protected $db=null;
    protected $dbname='';
    protected $presist=true;
    protected $pkey='connection_mongo';
    protected $repl=false;
    protected $safe='safe';
    protected $selects=[];
    protected $sorts=[];
    protected $limit=999999;
    protected $log=[];
    protected $offset=0;
    protected $dbinfo=[
        'persist'=>true,
        'persist_key'=>'mymongo',
        'replica_set'=>false,
        'query_safety'=>'safe'
    ];

    /**
    *   Konstruktor kelas
    */
    function __construct($info=[],$conn=true) {
        if (!class_exists('\Mongo'))
            abort('The mongodb pecl extension has not been installed or enabled');
        $this->dbinfo['dsn']=strtolower($info['driver']).'://'.$info['username'].':'.
            $info['password'].'@'.$info['host'].':'.
            (isset($info['port'])?$info['port']:'27017').'/'.$info['database'];
        $this->init($info,$conn);
    }

    /**
    *   Set konfigurasi database
    *   @param $info array
    *   @param $conn bool
    */
    function init($info=[],$conn=true) {
        if (is_array($info))
            $this->dbinfo=array_merge($info,$this->dbinfo);
        else abort('No config variables supplied');
        $this->parse();
        if ($conn)
            $this->connect();
    }

    /**
    *   Beralih ke database lain
    *   @param $dsn string
    */
    function jump($dsn='') {
        if (empty($dsn))
            abort('To jumping to other databases, a dsn must be specified');
        try {
            $this->dbinfo['dsn']=$dsn;
            $this->parse();
            $this->connect();
        }
        catch(\MongoConnectionException $e) {
            abort('Unable to switch databases: '.$e->getMessage());
        }
    }

    /**
    *   Hapus database
    *   @param $db string
    */
    function dropdb($db='') {
        if (empty($db))
            abort('Failed to drop database because name is empty');
        else {
            try {
                $this->conn->{$db}->drop();
                return true;
            }
            catch(\Exception $e) {
                abort('Unable to drop database `'.$db.'`: '.$e->getMessage());
            }
        }
    }

    /**
    *   Hapus collection
    *   @param $db string
    *   @param $coll string
    */
    function drop($db='',$coll='') {
        if (empty($db))
            abort('Failed to drop collection because database name is empty');
        if (empty($coll))
            abort('Failed to drop collection because collection name is empty');
        else {
            try {
                $this->conn->{$db}->{$coll}->drop();
                return true;
            }
            catch(\Exception $e) {
                abort('Unable to drop collection `'.$coll.'`: '.$e->getMessage());
            }
        }
    }

    /**
    *   Set klausa select
    *   @param $inc array
    *   @param $exc array
    */
    function select($inc=[],$exc=[]) {
        if (!is_array($inc))
            $inc=[];
        if (!is_array($exc))
            $exc=[];
        if (!empty($inc))
            foreach ($inc as $in)
                $this->selects[$in]=1;
        else foreach ($exc as $ex)
            $this->selects[$ex]=0;
        return $this;
    }

    /**
    *   Set klausa where
    *   @param $cond array
    *   @param $val mixed
    */
    function where($cond=[],$val=null) {
        if (is_array($cond))
            foreach ($cond as $where=>$val)
                $this->wheres[$where]=$val;
        else $this->wheres[$cond]=$val;
        return $this;
    }

    /**
    *   Set klausa 'or_where'
    *   @param $cond array
    */
    function orwhere($cond=[]) {
        if (count($cond)>0) {
            if (!isset($this->wheres['$or'])||!is_array($this->wheres['$or']))
                $this->wheres['$or']=[];
            foreach ($cond as $where=>$val)
                $this->wheres['$or'][]=[$where=>$val];
        }
        return $this;
    }

    /**
    *   Set klausa where in array (ambil nilai yang $col-nya ada di array $in_val)
    *   @param $col string
    *   @param $in_val array
    */
    function wherein($col='',$in_val=[]) {
        $this->initwhere($col);
        $this->wheres[$col]['$in']=$in_val;
        return $this;
    }

    /**
    *   Set klausa where all in array (ambil nilai jika $col cocok dengan semua array $in_val)
    *   @param $col string
    *   @param $in_val array
    */
    function inall($col='',$in_val=[]) {
        $this->initwhere($col);
        $this->wheres[$col]['$all']=$in_val;
        return $this;
    }

    /**
    *   Set klausa where not in array (ambil nilai jika $col tidak ada di array $in_val)
    *   @param $col string
    *   @param $in_val array
    */
    function notin($col='',$in_val=[]) {
        $this->initwhere($col);
        $this->wheres[$col]['$nin']=$in_val;
        return $this;
    }

    /**
    *   Set klausa where greater than (ambil nilai jika $col lebih besar dari $val)
    *   @param $col string
    *   @param $val int|null
    */
    function gt($col='',$val=null) {
        $this->initwhere($col);
        $this->wheres[$col]['$gt']=$val;
        return $this;
    }

    /**
    *   Set klausa where greater than or equal to (ambil nilai jika $col lebih besar atau sama dengan $val)
    *   @param $col string
    *   @param $val int|null
    */
    function gte($col='',$val=null) {
        $this->initwhere($col);
        $this->wheres[$col]['$gte']=$val;
        return $this;
    }

    /**
    *   Set klausa where less than (ambil nilai jika $col lebih kecil dari $val)
    *   @param $col string
    *   @param $val int|null
    */
    function lt($col='',$val=null) {
        $this->initwhere($col);
        $this->wheres[$col]['$lt']=$val;
        return $this;
    }

    /**
    *   Set klausa where less than or equal to (ambil nilai jika $col lebih kecil atau sama dengan $val)
    *   @param $col string
    *   @param $val int|null
    */
    function lte($col='',$val=null) {
        $this->initwhere($col);
        $this->wheres[$col]['$lte']=$val;
        return $this;
    }

    /**
    *   Set klausa between (ambil nilai jika $col berada pada range $x dan $y)
    *   @param $col string
    *   @param $x int
    *   @param $y int
    */
    function between($col='',$x=0,$y=0) {
        $this->initwhere($col);
        $this->wheres[$col]['$gte']=$x;
        $this->wheres[$col]['$lte']=$y;
        return $this;
    }

    /**
    *   Set klausa between and not equal to (ambil nilai jika $col berada
    *   pada range $x dan $y dan tidak sama dengan sama dengan $x maupun $y)
    *   @param $col string
    *   @param $x int
    *   @param $y int
    */
    function bne($col='',$x,$y) {
        $this->initwhere($col);
        $this->wheres[$col]['$gt']=$x;
        $this->wheres[$col]['$lt']=$y;
        return $this;
    }

    /**
    *   Set klausa where not equal to (ambil nilai jika $col tidak sama dengan $val)
    *   @param $col string
    *   @param $val mixed
    */
    function wne($col='',$val) {
        $this->initwhere($col);
        $this->wheres[$col]['$ne']=$val;
        return $this;
    }

    /**
    *   Set klausa near (ambil nilai $col yang terdekat dari array $coords.
    *   Note: collection anda harus punya index geospatial )
    *   @param $col string
    *   @param $coords array
    *   @param $dist int
    *   @param $sphere bool
    */
    function near($col='',$coords=[],$dist=null,$sphere=false) {
        $this->initwhere($col);
        if ($sphere)
            $this->wheres[$col]['$ns']=$coords;
        else $this->wheres[$col]['$near']=$coords;
        if ($dist!==null)
            $this->wheres[$col]['$max']=$dist;
        return $this;
    }

    /**
    *   Set klausa 'like' (Note: flags adalah flag seperti pada reguler expression)
    *   @param $col string
    *   @param $flags string
    *   @param $allstart bool
    *   @param $allend bool
    */
    function like($col='',$val='',$flags='i',$allstart=true,$allend=true) {
        $col=(string)trim($col);
        $this->initwhere($col);
        $val=quotemeta((string)trim($val));
        if ($allstart!==true)
            $val='^'.$val;
        if ($allend!==true)
            $val.='$';
        $regex='/'.$val.'/'.$flags;
        $this->wheres[$col]=new \MongoRegex($regex);
        return $this;
    }

    /**
    *   Set klausa 'order by'
    *   @param $cols array
    */
    function orderby($cols=[]) {
        foreach ($cols as $col=>$order) {
            if ($order===-1||$order===false||strtolower($order)==='desc')
                $this->sorts[$col]=-1;
            else $this->sorts[$col]=1;
        }
        return $this;
    }

    /**
    *   Set klausa 'limit'
    *   @param $limit int
    */
    function limit($limit=99999) {
        if ($limit!==null&&is_numeric($limit)&&$limit>=1)
            $this->limit=(int)$limit;
        return $this;
    }

    /**
    *   Set klausa 'offset'
    *   @param $offset int
    */
    function offset($offset=0) {
        if ($offset!==null&&is_numeric($offset)&&$offset>=1)
            $this->offset=(int)$offset;
        return $this;
    }

    /**
    *   Get where (ambil nilai berdasarkan array $where)
    *   @param $coll string
    *   @param $where array
    */
    function getwhere($coll='',$where=[]) {
        return $this->where($where)->get($coll);
    }

    /**
    *   Ambil hasil kueri
    *   @param $coll string
    *   @param $return_cursor bool
    */
    function get($coll='',$return_cursor=false) {
        if (empty($coll))
            abort('In order to retrieve documents from mongodb, a collection name must be passed');
        $cursor=$this->db->{$coll}
            ->find($this->wheres,$this->selects)
            ->limit($this->limit)
            ->skip($this->offset)
            ->sort($this->sorts);
        $this->reset($coll,'get');
        if ($return_cursor===true)
            return $cursor;
        $data=[];
        while ($cursor->hasNext()) {
            try {
                $data[]=$cursor->getNext();
            }
            catch(\MongoCursorException $e) {
                abort($e->getMessage());
            }
        }
        return $data;
    }

    /**
    *   Ambil jumlah hasil kueri
    *   @param $coll string
    */
    function count($coll='') {
        if (empty($coll))
            abort('To retrieve a count of documents, a collection name must be passed');
        $count=$this->db->{$coll}
            ->find($this->wheres)
            ->limit($this->limit)
            ->skip($this->offset)
            ->count();
        $this->reset($coll,'count');
        return $count;
    }

    /**
    *   Insert nilai ke collection
    *   @param $coll string
    *   @param $insert array
    *   @param $opt array
    */
    function insert($coll='',$insert=[],$opt=[]) {
        if (empty($coll))
            abort('No collection selected to insert into');
        if (count($insert)===0||!is_array($insert))
            abort('Nothing to insert into collection or insert is not an array');
        $opt=array_merge([$this->safe=>true],$opt);
        try {
            $this->db->{$coll}->insert($insert,$opt);
            if (isset($insert['_id']))
                return $insert['_id'];
            else return false;
        }
        catch(\MongoCursorException $e) {
            abort('Insert of data into mongo failed: '.$e->getMessage());
        }
    }

    /**
    *   Insert banyak nilai ke collection
    *   @param $coll string
    *   @param $insert array
    *   @param $opt array
    */
    function minsert($coll='',$insert=[],$opt=[]) {
        if (empty($coll))
            abort('No collection selected to insert into');
        if (count($insert)===0||!is_array($insert))
            abort('Nothing to insert into collection or insert is not an array');
        $opt=array_merge([$this->safe=>true],$opt);
        try {
            return $this->db->{$coll}->batchInsert($insert,$opt);
        }
        catch(\MongoCursorException $e) {
            abort('Insert of data into mongodb failed: '.$e->getMessage());
        }
    }

    /**
    *   Update nilai collection
    *   @param $coll string
    *   @param $opt array
    */
    function update($coll='',$opt=[]) {
        if (empty($coll))
            abort('No collection selected to update');
        if (count($this->updates)===0)
            abort('Nothing to update in collection or update is not an array');
        try {
            $opt=array_merge([$this->safe=>true,'multiple'=>false],$opt);
            $res=$this->db->{$coll}->update($this->wheres,$this->updates,$opt);
            $this->reset($coll,'update');
            if ($res['updatedExisting']>0)
                return $res['updatedExisting'];
            return false;
        }
        catch(\MongoCursorException $e) {
            abort('Update of data into mongodb failed: '.$e->getMessage());
        }
    }

    /**
    *   Update semua nilai collection
    *   @param $coll string
    *   @param $opt array
    */
    function updateall($coll='',$opt=[]) {
        if (empty($coll))
            abort('No collection selected to update');
        if (count($this->updates)===0)
            abort('Nothing to update in collection or update is not an array');
        try {
            $opt=array_merge([$this->safe=>true,'multiple'=>true],$opt);
            $res=$this->db->{$coll}->update($this->wheres,$this->updates,$opt);
            $this->reset($coll,'update_all');
            if ($res['updatedExisting']>0)
                return $res['updatedExisting'];
            return false;
        }
        catch(\MongoCursorException $e) {
            abort('Update of data into mongodb failed: '.$e->getMessage());
        }
    }

    /**
    *   Increment nilai collection (plus)
    *   @param $cols array
    *   @param $val int
    */
    function inc($cols=[],$val=0) {
        $this->initupdate('$inc');
        if (is_string($cols))
            $this->updates['$inc'][$cols]=$val;
        elseif (is_array($cols))
            foreach ($cols as $col=>$val)
                $this->updates['$inc'][$col]=$val;
        return $this;
    }

    /**
    *   Increment nilai collection (minus)
    *   @param $cols array
    *   @param $val int
    */
    function dec($cols=[],$val=0) {
        $this->initupdate('$inc');
        if (is_string($cols)) {
            $val=0-$val;
            $this->updates['$inc'][$cols]=$val;
        }
        elseif (is_array($cols))
            foreach ($cols as $col=>$val) {
                $val=0-$val;
                $this->updates['$inc'][$col]=$val;
            }
        return $this;
    }

    /**
    *   Set field ke value
    *   @param $cols string
    *   @param $val mixed
    */
    function set($cols,$val=null) {
        $this->initupdate('$set');
        if (is_string($cols))
            $this->updates['$set'][$cols]=$val;
        elseif (is_array($cols))
            foreach ($cols as $col=>$val)
                $this->updates['$set'][$col]=$val;
        return $this;
    }

    /**
    *   Devoid/Unset field
    *   @param $cols string string|array
    */
    function devoid($cols) {
        $this->initupdate('$unset');
        if (is_string($cols))
            $this->updates['$unset'][$cols]=1;
        elseif (is_array($cols))
            foreach ($cols as $col)
                $this->updates['$unset'][$col]=1;
        return $this;
    }

    /**
    *   Tambah ke set
    *   @param $col string
    *   @param $vals string|array
    */
    function add($col,$vals) {
        $this->initupdate('$add');
        if (is_string($vals))
            $this->updates['$add'][$col]=$vals;
        elseif (is_array($vals))
            $this->updates['$add'][$col]=['$each'=>$vals];
        return $this;
    }

    /**
    *   Push value ke field (Note: field harus berupa array)
    *   @param $cols string|array
    *   @param $val array
    */
    function push($cols,$val=[]) {
        $this->initupdate('$push');
        if (is_string($cols))
            $this->updates['$push'][$cols]=$val;
        elseif (is_array($cols))
            foreach ($cols as $col=>$val)
                $this->updates['$push'][$col]=$val;
        return $this;
    }

    /**
    *   Pop nilai terakhir dari field (Note: field harus berupa array)
    *   @param $cols string|array
    */
    function pop($col) {
        $this->initupdate('$pop');
        if (is_string($col))
            $this->updates['$pop'][$col]=-1;
        elseif (is_array($col))
            foreach ($col as $pop_field)
                $this->updates['$pop'][$pop_field]=-1;
        return $this;
    }

    /**
    *   Hapus nilai berdasarkan array $val
    *   @param $col string
    *   @param $val array
    */
    function pull($col='',$val=[]) {
        $this->initupdate('$pull');
        $this->updates['$pull']=[$col=>$val];
        return $this;
    }

    /**
    *   Ganti nama field
    *   @param $old string
    *   @param $old string
    */
    function mv($old,$new) {
        $this->initupdate('$mv');
        $this->updates['$mv'][$old]=$new;
        return $this;
    }

    /**
    *   Hapus nilai collection berdasarkan kriteria yang diberikan
    *   @param $coll string
    */
    function rm($coll='') {
        if (empty($coll))
            abort('No collection selected to delete from');
        try {
            $this->db->{$coll}->remove($this->wheres,[$this->safe=>true,'justOne'=>true]);
            $this->reset($coll,'delete');
            return true;
        }
        catch(\MongoCursorException $e) {
            abort('Delete of data into mongodb failed: '.$e->getMessage());
        }
    }

    /**
    *   Hapus semua nilai collection berdasarkan kriteria yang diberikan
    *   @param $coll string
    */
    function destroy($coll='') {
        if (empty($coll))
            abort('No collection selected to delete from');
        try {
            $this->db->{$coll}->remove($this->wheres,[$this->safe=>true,'justOne'=>false]);
            $this->reset($coll,'delete_all');
            return true;
        }
        catch(\MongoCursorException $e) {
            abort('Delete of data into mongodb failed: '.$e->getMessage());
        }
    }

    /**
    *   Jalankan perintah sql mongodb secara manual
    *   @param $q array
    */
    function sql($q=[]) {
        try {
            $exec=$this->db->command($q);
            return $exec;
        }
        catch(\MongoCursorException $e) {
            abort('MongoDB command failed to execute: '.$e->getMessage());
        }
    }

    /**
    *   Tambahkan index collection
    *   @param $coll string
    *   @param $cols array
    *   @param $opt array
    */
    function addindex($coll='',$cols=[],$opt=[]) {
        if (empty($coll))
            abort('No mongodb collection specified to add index to');
        if (empty($cols)||!is_array($cols))
            abort('Index could not be added to collection, no keys specified');
        foreach ($cols as $col=>$val) {
            if ($val===-1||$val===false||strtolower($val)==='desc')
                $keys[$col]=-1;
            elseif ($val===1||$val===true||strtolower($val)==='asc')
                $keys[$col]=1;
            else $keys[$col]=$val;
        }
        try {
            $this->db->{$coll}->ensureIndex($keys,$opt);
            $this->reset($coll,'add_index');
            return $this;
        }
        catch(\Exception $e) {
            abort('Error when trying to add an index to collection: '.$e->getMessage());
        }
    }

    /**
    *   Hapus index collection
    *   @param $coll string
    *   @param $keys array
    */
    function rmindex($coll='',$keys=[]) {
        if (empty($coll))
            abort('No collection specified to remove index from');
        if (empty($keys)||!is_array($keys))
            abort('Index could not be removed from collection, no keys specified');
        if ($this->db->{$coll}->deleteIndex($keys)) {
            $this->reset($coll,'remove_index');
            return $this;
        }
        else abort('Error when trying to remove an index from collection');
        return $this->db->{$coll}->deleteIndex($keys);
    }

    /**
    *   Hapus semua index collection
    *   @param $coll string
    */
    function reindex($coll='') {
        if (empty($coll))
            abort('No collection specified to remove all indexes from');
        $this->db->{$coll}->deleteIndexes();
        $this->reset($coll,'remove_all_indexes');
        return $this;
    }

    /**
    *   List semua index collection
    *   @param $coll string
    */
    function lsindex($coll='') {
        if (empty($coll))
            abort('No collection specified to remove all indexes from');
        return $this->db->{$coll}->getIndexInfo();
    }

    /**
    *   Tanggal untuk mongodb
    *   @param $timestamp string
    */
    public static function date($timestamp=null) {
        if ($timestamp===null)
            return new \MongoDate();
        return new \MongoDate($timestamp);
    }

    /**
    *   Ambil keuri terakhir
    */
    function lastquery() {
        return $this->log;
    }

    /**
    *   Buat koneksi ke mongodb
    */
    private function connect() {
        $opt=[];
        if ($this->presist===true)
            $opt['persist']=$this->pkey;
        if ($this->repl!==false)
            $opt['replicaSet']=$this->repl;
        try {
            if (phpversion('Mongo')>=1.3) {
                unset($opt['persist']);
                $this->conn=new \MongoClient($this->dsn,$opt);
                $this->db=$this->conn->{$this->dbname};
            }
            else {
                $this->conn=new \Mongo($this->dsn,$opt);
                $this->db=$this->conn->{$this->dbname};
            }
            return $this;
        }
        catch(MongoConnectionException $e) {
            abort('Unable to connect to mongodb: '.$e->getMessage());
        }
    }

    /**
    *   Parse string koneksi
    */
    private function parse() {
        $this->dsn=trim($this->dbinfo['dsn']);
        if (empty($this->dsn))
            abort('The dsn is empty');
        $this->presist=$this->dbinfo['persist'];
        $this->pkey=trim($this->dbinfo['persist_key']);
        $this->repl=$this->dbinfo['replica_set'];
        $this->safe=trim($this->dbinfo['query_safety']);
        $parts=parse_url($this->dsn);
        if (!isset($parts['path'])||str_replace('/','',$parts['path'])==='')
            abort('The database name must be set in the DSN string');
        $this->dbname=str_replace('/','',$parts['path']);
        return;
    }

    /**
    *   Reset variabel kelas ke posisi default
    *   @param $coll string
    *   @param $action string
    */
    private function reset($coll,$action) {
        $this->log=[
            'collection'=>$coll,
            'action'=>$action,
            'wheres'=>$this->wheres,
            'updates'=>$this->updates,
            'selects'=>$this->selects,
            'limit'=>$this->limit,
            'offset'=>$this->offset,
            'sorts'=>$this->sorts
        ];
        $this->selects=[];
        $this->updates=[];
        $this->wheres=[];
        $this->limit=999999;
        $this->offset=0;
        $this->sorts=[];
    }

    /**
    *   Inisialisasi statement where
    *   @param $coll string
    */
    private function initwhere($col) {
        if (!isset($this->wheres[$col]))
            $this->wheres[$col]=[];
    }

    /**
    *   Inisialisasi statement update
    *   @param $coll string
    */
    private function initupdate($col='') {
        if (!isset($this->updates[$col]))
            $this->updates[$col]=[];
    }
}
