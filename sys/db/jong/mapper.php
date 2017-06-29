<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Db\Jong;
defined('BASE') or exit('Access Denied!');


class Mapper {
    private $path;
    private $query;
    private $indexes;
    private $fcache;

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->setpath(RES.'jong/');
        $this->indexes=[];
        $this->fcache=[];
        if (!is_dir($this->path)) {
            if (!mkdir($this->path))
                abort("Can't create table, access denied to %s",[$this->path],E_ERROR);
            else file_put_contents($this->path."index.php","<?php /* don't remove this! */ ?>");
        }
    }

    /**
    *   Set path data jong
    *   @param $path string
    */
    protected function setpath($path) {
        $this->path=str_replace('/',DS,$path);
    }

    /**
    *   Set tabel yang akan dioperasikan
    *   @param $name string
    */
    function table($name) {
        $this->query=new Jong($name);
        return $this;
    }

    /**
    *   Insert data
    *   @param $object object|array
    */
    function insert($object) {
        if (!is_array($object))
            abort('Jong can only write array',E_ERROR);
        if ($this->query->executed())
            abort('The query already executed',E_ERROR);
        $table=$this->query->table;
        $id=0;
        $meta=null;
        if (!is_dir($this->path.$table)) {
            if (!@mkdir($this->path.$table,0777))
                abort("Can't create table directory for '%s' access denied",[$table],E_ERROR);
            else file_put_contents($this->path.$table.DS."index.php","<?php /* don't remove this! */ ?>");
            $id=1;
            $meta=[
                'lastid'=>0,
                'count'=>0,
                'index'=>[]
            ];
        }
        else {
            $meta=$this->properties();
            $id=$meta['lastid']+1;
        }
        $object['id']=$id;
        $this->write($table.DS.'_'.$id.'.php',$object);
        $meta['lastid']=$id;
        if (array_key_exists($table,$this->indexes)) {
            foreach ($this->indexes[$table] as $index) {
                if (!$object[$index])
                    abort("Table '%s' has an index on '%s', but trying to ignore that field",
                        [$table,$index],E_USER_ERROR);
                $meta['index'][$index][]=$object[$index];
            }
        }
        else $meta['index']['id'][]=$id;
        $meta['count']=$meta['count']+1;
        $this->write($table.DS.'meta.php',$meta);
        $this->fcache[$table]=$meta;
        $this->flush($table);
        $this->query->execute();
        return $object;
    }

    /**
    *   Update data
    *   @param $id string
    *   @param $val array
    */
    function update($id,$val) {
        if ($this->query->executed())
            abort("Query already executed",E_USER_ERROR);
        $table=$this->query->table;
        $files=$this->path.$table.DS.'_'.$id.'.php';
        if (!file_exists($files))
            abort("Can't find data with id '%s'",[$id],E_ERROR);
        $meta=$this->properties();
        $old=$this->read($files,false);
        $updated=false;
        $indexes=array_keys($meta['index']);
        foreach ($indexes as $index) {
            if ($index=='id')
                continue;
            if ($old[$index]!=$val[$index]) {
                $updated=true;
                break;
            }
        }
        if ($updated) {
            $key=array_search($old['id'],$meta['index']['id']);
            foreach ($indexes as $index) {
                if ($index=='id')
                    continue;
                $meta['index'][$index][$key]=$val[$index];
            }
            $this->write($table.DS.'meta.php',$meta);
            $this->fcache[$table]=$meta;
        }
        $val['id']=$old['id'];
        $this->write($files,$val,false);
        $this->flush($table);
        $this->query->execute();
        return $val;
    }

    /**
    *   Delete data
    *   @param $id string|array
    */
    function delete($id) {
        if ($this->query->executed())
            abort("Query already executed",E_USER_ERROR);
        $table=$this->query->table;
        if (is_array($id)) {
            foreach ($id as $index)
                $this->table($table)->delete($index);
            return $this;
        }
        if (!file_exists($this->path.$table.DS.'_'.$id.'.php'))
            abort("Can't find data with id '%s'",[$id],E_ERROR);
        $meta=$this->properties();
        $key=array_search($id,$meta['index']['id']);
        foreach (array_keys($meta['index']) as $index)
            unset($meta['index'][$index][$key]);
        $meta['count']=$meta['count']-1;
        unlink($this->path.$table.DS.'_'.$id.'.php');
        $this->write($table.DS.'meta.php',$meta);
        $this->fcache[$table]=$meta;
        $this->flush($table);
        $this->query->execute();
        return $this;
    }

    /**
    *   Hapus cache tabel
    *   @param $table string
    */
    private function flush($table) {
        foreach (glob($this->path.$table.DS.'cache_*') as $file)
            unlink($file);
    }

    /**
    *   Mencari data
    *   @param $val string
    *   @param $column string
    */
    function find($val,$column='id') {
        if ($column=='id') {
            $this->query->id=$val;
            return $this->findbyid();
        }
        $meta=$this->properties();
        if (!array_key_exists($column,$meta['index']))
            abort("Field '%s' is  not a table index",[$column],E_ERROR);
        $array_idx=array_search($val,$meta['index'][$column]);
        if (false===$array_idx)
            return null;
        $id=$meta['index']['id'][$array_idx];
        return $this->find($id);
    }

    /**
    *   Tambahkan klausa order by
    *   @param $key string
    *   @param $ord string
    */
    function orderby($key='id',$ord='desc') {
        $this->query->order=[
            'key'=>$key,
            'mode'=>strtoupper($ord)
        ];
        return $this;
    }

    /**
    *   Tambahkan klausa limit
    *   @param $limit int
    */
    function limit($limit) {
        $this->query->limit=$limit;
        return $this;
    }

    /**
    *   Kueri select
    *   @param $key string
    */
    function select($key) {
        $this->query->fetch=$key;
        return $this;
    }

    /**
    *   Tambahkan klausa offset
    *   @param $offset int
    */
    function offset($offset) {
        $this->query->offset=$offset;
        return $this;
    }

    /**
    *   Nama lain dari offset
    *   @param $offset int
    */
    function skip($offset) {
        return $this->offset($offset);
    }

    /**
    *   Tambahkan klausa where
    *   @param $orr array
    */
    function where($arr=null) {
        $this->query->where=$arr;
        return $this;
    }

    /**
    *   Ambil semua data hasil select
    */
    function many() {
        return $this->findall();
    }

    /**
    *   Ambil hanya satu data hasil select
    */
    function one() {
        $all=$this->many();
        if (count($all)==0)
            return null;
        return current($all);
    }

    /**
    *   Hitung jumlah data di tabel
    */
    function count() {
        $limit=$this->query->limit;
        $offset=$this->query->offset;
        $where=$this->query->where;
        if (!is_null($limit)||!is_null($offset)||!is_null($where))
            return count($this->many());
        $meta=$this->properties();
        return $meta['count'];
    }

    /**
    *   Baca info metadata database
    */
    function properties() {
        $table=$this->query->table;
        if (!array_key_exists($table,$this->fcache)) {
            $path=$this->path.$table.DS.'meta.php';
            if (!file_exists($path))
                abort("Can't find metadata for table '%s'",[$table],E_ERROR);
            $this->fcache[$table]=$this->read($path,false);
        }
        return $this->fcache[$table];
    }

    /**
    *   Set indeks tabel
    *   @param $arr array
    */
    function indexes($arr) {
        $table=$this->query->table;
        if (!$table)
            abort("Can't define an index, table is not specified",E_USER_ERROR);
        if (!is_array($arr))
            abort("Index definition muast be an array",E_USER_ERROR);
        if (!in_array('id',$arr))
            $arr[]='id';
        $this->indexes[$table]=$arr;
        $this->query->execute();
        $meta=null;
        try {
            $meta=$this->properties();
            if (array_keys($meta['index'])===$arr)
                return 2;
            foreach ($arr as $index) {
                $meta['index'][$index]=[];
                foreach ($this->table($table)->many() as $entry)
                    $meta['index'][$index][]=$entry[$index];
            }
            $this->write($table.DS.'meta.php',$meta);
            $this->fcache[$table]=$meta;
        }
        catch (\Exception $e) {}
        return 1;
    }

    /**
    *   Cari data berdasarkan id
    */
    private function findbyid() {
        if ($this->query->executed())
            abort("Query already executed",E_USER_ERROR);
        $table=$this->query->table;
        $fetch=$this->query->fetch;
        $id=$this->query->id;
        $path=$this->path.$table.DS.'_'.$id.'.php';
        $this->query->execute();
        if (file_exists($path)) {
            $entry=$this->read($path,false);
            return is_null($fetch)?$entry:$this->sortfield($fetch,$entry);
        }
        return null;
    }

    /**
    *   Sortir kolom data
    *   @param $fetch array
    *   @param $entry array
    */
    private function sortfield($fetch,$entry) {
        $result=[];
        foreach ($fetch as $key)
            if (array_key_exists($key,$entry))
                $result[$key]=$entry[$key];
        return $result;
    }

    /**
    *   Ambil semua hasil kueri
    */
    private function findall() {
        if ($this->query->executed())
            abort("Query already executed",E_USER_ERROR);
        $table=$this->query->table;
        $order=$this->query->order;
        $limit=$this->query->limit;
        $offset=$this->query->offset;
        $where=$this->query->where;
        $fetch=$this->query->fetch;
        $cache_name=sha1($table.serialize($order).$limit.$offset.serialize($where).serialize($fetch));
        $cache=$this->path.$table.DS.'cache_'.$cache_name.'.php';
        if (file_exists($cache)) {
            $this->query->execute();
            return $this->read($cache,false);
        }
        $metadata=$this->properties();
        $key=$order['key'];
        $mode=$order['mode'];
        $index=$metadata['index'][$key];
        if (empty($metadata['index']['id']))
            return null;
        $arridx=array_combine($index,$metadata['index']['id']);
        if ($mode==='DESC')
            krsort($arridx);
        else ksort($arridx);
        if ($limit>0)
            $arridx=array_slice($arridx,$offset,$limit);
        else if ($offset>0)
            $arridx=array_slice($arridx,$offset);
        $result=[];
        $entry=null;
        if (is_null($where)) {
            foreach ($arridx as $idx=>$id) {
                $entry=$this->read($table.DS.'_'.$id.'.php');
                $result[]=is_null($fetch)?$entry:$this->sortfield($fetch,$entry);
            }
        }
        else {
            foreach ($arridx as $idx=>$id) {
                $entry=$this->read($table.DS.'_'.$id.'.php');
                $add=true;
                if (is_callable($where))
                    abort("Closing with 'where' clause is not allowed",E_ERROR);
                else {
                    foreach ($where as $key=>$value) {
                        if (array_key_exists($key,$entry)&&is_array($entry[$key])) {
                            if (is_array($value)) {
                                foreach ($value as $item) {
                                    if (!in_array($item,$entry[$key])) {
                                        $add=false;
                                        break;
                                    }
                                }
                            }
                            else {
                                if (!in_array($value,$entry[$key])) {
                                    $add=false;
                                    break;
                                }
                            }
                        }
                        else {
                            if ($entry[$key]!=$value) {
                                $add=false;
                                break;
                            }
                        }
                    }
                }
                if ($add)
                    $result[]=is_null($fetch)?$entry:$this->sortfield($fetch,$entry);
            }
        }
        $this->query->execute();
        $this->write($cache,$result,false);
        return $result;
    }

    /**
    *   Tulis objek ke file
    *   @param $path string
    *   @param $object object
    *   @param $relative bool
    */
    private function write($path,$object,$relative=true) {
        if ($relative)
            $path=$this->path.$path;
        file_put_contents($path,'<?php exit(); ?>'.serialize($object),LOCK_EX);
    }

    /**
    *   Baca data objek dari file
    *   @param $path string
    *   @param $relative bool
    */
    private function read($path,$relative=true) {
        if ($relative)
            $path=$this->path.$path;
        $contents=file_get_contents($path);
        return unserialize(substr($contents,16));
    }
}


class Jong {
    public $table=null;
    public $order;
    public $limit=0;
    public $offset=0;
    public $id=0;
    public $where=null;
    public $fetch=null;
    private $executed=false;

    /**
    *   Konstruktor kelas
    */
    function __construct($name) {
        $this->table=$name;
        $this->order=[
            'key'=>'id',
            'mode'=>'ASC'
        ];
    }

    /**
    *   Tandai kueri sudah dieksekusi
    */
    function execute() {
        $this->executed=true;
    }

    /**
    *   Cek apakah kueri sudah dieksekusi
    */
    function executed() {
        return $this->executed;
    }
}
