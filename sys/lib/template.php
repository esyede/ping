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


class Template extends Preview {
    protected $base;
    protected $cache;
    protected $format;
    protected $type=['command','comment','echo'];

    /**
    *   Konstruktor kelas
    */
    function __construct($config=[]) {
        $config=array_replace_recursive([
            'cache'=>RES.'template',
            'echo'=>'htmlspecialchars(%s,ENT_QUOTES,\'UTF-8\')'
        ],$config);
        $this->cache=isset($config['cache'])?$config['cache']:RES.'template';
        $this->format=isset($config['echo'])?$config['echo']:'%s';
        parent::__construct($config);
        $this->base=summon('loader')->sysinfo('base');
    }

    /**
    *   Include nama file
    *   @param $name string
    */
    protected function tpl($name) {
        $tpl=APP.'view'.DS.str_replace('/',DS,$name);
        $php=$this->cache.DS.md5($name).'.php';
        if (!file_exists($php)||filemtime($tpl)>filemtime($php)) {
            $text=str_replace('@BASE','<?php echo $this->base?>',file_get_contents($tpl));
            foreach ($this->type as $type)
                $text=$this->{'_'.$type}($text);
            file_put_contents($php,$text);
        }
        return $php;
    }

    /**
    *   Compile statement perintah
    *   @param $vars string
    */
    protected function _command($vars) {
        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',function ($match) {
            if (method_exists($this,$method='_'.ucfirst($match[1])))
                $match[0]=$this->$method(isset($match[3])?$match[3]:'');
            return isset($match[3])?$match[0]:$match[0].$match[2];
        },$vars);
    }

    /**
    *   Compile statement komentar
    *   @param $vars string
    */
    protected function _comment($vars) {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/','<?php /*$1*/?>',$vars);
    }

    /**
    *   Compile statement echo
    *   @param $vars string
    */
    protected function _echo($vars) {
        $vars=preg_replace_callback('/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s',function ($found) {
            $space=empty($found[2])?'':$found[2].$found[2];
            return '<?php echo htmlspecialchars('.
                $this->_echodefault($found[1]).',ENT_QUOTES,\'UTF-8\')?>'.$space;
        },$vars);
        $vars=preg_replace_callback('/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s',function ($found) {
            $space=empty($found[2])?'':$found[2].$found[2];
            return '<?php echo '.$this->_echodefault($found[1]).'?>'.$space;
        },$vars);
        $vars=preg_replace_callback('/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s',function ($found) {
            $space=empty($found[3])?'':$found[3].$found[3];
            return $found[1]?substr($found[0],1):'<?php echo '.
                sprintf($this->format,$this->_echodefault($found[2])).'?>'.$space;
        },$vars);
        return $vars;
    }

    /**
    *   Compile value default statement komentar
    *   @param $vars string
    */
    function _echodefault($vars) {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s','isset($1)?$1:$2',$vars);
    }

    /**
    *   Compile statement if
    *   @param $vars string
    */
    protected function _if($condition) {
        return "<?php if{$condition}:?>";
    }

    /**
    *   Compile statement elseif
    *   @param $vars string
    */
    protected function _elseif($condition) {
        return "<?php elseif{$condition}:?>";
    }

    /**
    *   Compile statement endif
    *   @param $vars string
    */
    protected function _endif($condition) {
        return "<?php endif;?>";
    }

    /**
    *   Compile statement unless
    *   @param $vars string
    */
    protected function _unless($condition) {
        return "<?php if(!$condition):?>";
    }

    /**
    *   Compile statement endunless
    *   @param $vars string
    */
    protected function _endunless($condition) {
        return "<?php endif;?>";
    }

    /**
    *   Compile statement for
    *   @param $vars string
    */
    protected function _for($condition) {
        return "<?php for{$condition}:?>";
    }

    /**
    *   Compile statement endfor
    *   @param $vars string
    */
    protected function _endfor($condition) {
        return "<?php endfor;?>";
    }

    /**
    *   Compile statement foreach
    *   @param $vars string
    */
    protected function _foreach($condition) {
        return "<?php foreach{$condition}:?>";
    }

    /**
    *   Compile statement endforeach
    *   @param $vars string
    */
    protected function _endforeach($condition) {
        return "<?php endforeach;?>";
    }

    /**
    *   Compile statement while
    *   @param $vars string
    */
    protected function _while($condition) {
        return "<?php while{$condition}:?>";
    }

    /**
    *   Compile statement endwhile
    *   @param $vars string
    */
    protected function _endwhile($condition) {
        return "<?php endwhile;?>";
    }

    /**
    *   Compile statement extends
    *   @param $vars string
    */
    protected function _extends($condition) {
        if (isset($condition{0})&&$condition{0}=='(')
            $condition=substr($condition,1,-1);
        return "<?php \$this->extend({$condition})?>";
    }

    /**
    *   Compile statement include
    *   @param $vars string
    */
    protected function _include($condition) {
        if (isset($condition{0})&&$condition{0}=='(')
            $condition=substr($condition,1,-1);
        return "<?php include \$this->tpl({$condition})?>";
    }

    /**
    *   Compile statement yield
    *   @param $vars string
    */
    protected function _yield($condition) {
        return "<?php echo \$this->block{$condition}?>";
    }

    /**
    *   Compile statement section
    *   @param $vars string
    */
    protected function _section($condition) {
        return "<?php \$this->beginblock{$condition}?>";
    }

    /**
    *   Compile statement endsection
    *   @param $vars string
    */
    protected function _endsection($condition) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile statement show
    *   @param $vars string
    */
    protected function _show($condition) {
        return "<?php echo \$this->block(\$this->endblock())?>";
    }

    /**
    *   Compile statement append
    *   @param $vars string
    */
    protected function _append($condition) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile statement stop
    *   @param $vars string
    */
    protected function _stop($condition) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile statement overwrite
    *   @param $vars string
    */
    protected function _overwrite($condition) {
        return "<?php \$this->endblock(true)?>";
    }
}


class Preview {
    protected $block;
    protected $stack;

    /**
    *   Konstruktor kelas
    */
    function __construct($config=[]) {
        $this->block=[];
        $this->stack=[];
    }

    /**
    *   Wrapper include template
    *   @param $name string
    */
    protected function tpl($name) {
        return APP.'view'.DS.$name;
    }

    /**
    *   Cetak template ke html
    *   @param $name string
    *   @param $data array
    */
    function render($name,$data=[]) {
        echo $this->retrieve($name,$data);
    }

    /**
    *   Ambil hasil kompilasi
    *   @param $name string
    *   @param $data array
    */
    function retrieve($name,$data=[]) {
        $this->tpl[]=$name;
        if (!empty($data))
            extract($data);
        while ($file=array_shift($this->tpl)) {
            $this->beginblock('content');
            require ($this->tpl($file));
            $this->endblock(true);
        }
        return $this->block('content');
    }

    /**
    *   Cek apkah file template ada
    *   @param $name string
    */
    function exists($name) {
        return file_exists($this->tpl($name));
    }

    /**
    *   Extend parent
    *   @param $name string
    */
    protected function extend($name) {
        $this->tpl[]=$name;
    }

    /**
    *   Blok konten
    *   @param $name string
    *   @param $default string
    */
    protected function block($name,$default='') {
        return array_key_exists($name,$this->block)?$this->block[$name]:$default;
    }

    /**
    *   Permulaan blok
    *   @param $name string
    */
    protected function beginblock($name) {
        array_push($this->stack,$name);
        ob_start();
    }

    /**
    *   Akhir blok
    *   @param $overwrite bool
    */
    protected function endblock($overwrite=false) {
        $name=array_pop($this->stack);
        if ($overwrite||!array_key_exists($name,$this->block))
            $this->block[$name]=ob_get_clean();
        else $this->block[$name].=ob_get_clean();
        return $name;
    }
}
