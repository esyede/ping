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


class Markdown {
    private static $instance=[];
    protected $defined;
    protected $br;
    protected $escape;
    protected $link=true;
    protected $mdtag=[
        'rowtag'=>[
            '"'=>['specialchars'],'!'=>['gamln'],'&'=>['specialchars'],'*'=>['em'],
            ':'=>['url'],'<'=>['tagurl','email','markup','specialchars'],'>'=>['specialchars'],'['=>['link'],
            '_'=>['em'],'`'=>['code'],'~'=>['s'],'\\'=>['esc']
            ],
        'types'=>[
            '#'=>['header'],'*'=>['rule','list'],'+'=>['list'],'-'=>['textheader','table','rule','list'],
            '0'=>['list'],'1'=>['list'],'2'=>['list'],'3'=>['list'],
            '4'=>['list'],'5'=>['list'],'6'=>['list'],'7'=>['list'],
            '8'=>['list'],'9'=>['list'],':'=>['table'],'<'=>['comment','markup'],
            '='=>['textheader'],'>'=>['quote'],'['=>['reference'],'_'=>['rule'],
            '`'=>['codelong'],'|'=>['table'],'~'=>['codelong']
        ]
    ];
    protected $chars=[
        'level'=>[
            'a','br','bdo','abbr','blink','nextid','acronym',
            'basefont','b','em','big','cite','small','spacer',
            'listing','i','rp','del','code','strike','marquee',
            'q','rt','ins','font','strong','s','tt','sub','mark',
            'u','xm','sup','nobr','var','ruby','wbr','span','time'
        ],
        'void'=>[
            'area','base','br','col','command','embed',
            'hr','img','input','link','meta','param','source'
        ],
        'special'=>['\\','`','*','_','{','}','[',']','(',')','>','#','+','-','.','!','|'],
        'raw'=>['code'],
        'regex'=>[
            'strong'=>[
                '*'=>'/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
                '_'=>'/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us'
            ],
            'em'=>[
                '*'=>'/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
                '_'=>'/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us'
            ],
            'html'=>'[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?',
            'row'=>'!"*_&[:<>`~\\'
        ]
    ];


    /**
    *   Konstruktor kelas
    */
    function __construct() {}

    /**
    *   Instance kelas
    *   @param $name string
    */
    static function instance($name='default') {
        if (isset(cellf::$instance[$name]))
            return cellf::$instance[$name];
        $static=new static();
        cellf::$instance[$name]=$static;
        return $instance;
    }

    /**
    *   Cetak halaman html
    *   @param $name string
    */
    function render($text) {
        if (strpos($text,'@BASE')!==false)
            $text=preg_replace('/@BASE/',summon('loader')->sysinfo('base'),$text);
        return $this->translate($text);
    }

    /**
    *   Terjemahkan sintaks markdown ke html
    *   @param $text string
    */
    function translate($text) {
        $this->defined=[];
        return trim($this->raws(explode("\n",trim(str_replace(["\r\n","\r"],"\n",$text)))),"\n");
    }


    function br($br) {
        $this->br=$br;
        return $this;
    }


    function escape($escape) {
        $this->escape=$escape;
        return $this;
    }


    function link($link) {
        $this->link=$link;
        return $this;
    }


    function ln($text) {
        $markup='';
        while ($excerpt=strpbrk($text,$this->chars['regex']['row'])) {
            $tagger=strpos($text,$excerpt[0]);
            $excrpt=['text'=>$excerpt,'context'=>$text];
            foreach ($this->mdtag['rowtag'][$excerpt[0]] as $tpil) {
                $il=$this->{'ln'.$tpil}($excrpt);
                if (!isset($il))
                    continue;
                if (isset($il['position'])&&$il['position']>$tagger)
                    continue;
                if (!isset($il['position']))
                    $il['position']=$tagger;
                $markup.=$this->rawtext(substr($text,0,$il['position']));
                $markup.=isset($il['markup'])?$il['markup']:$this->element($il['element']);
                $text=substr($text,$il['position']+$il['ext']);
                continue 2;
            }
            $markup.=$this->rawtext(substr($text,0,$tagger+1));
            $text=substr($text,$tagger+1);
        }
        $markup.=$this->rawtext($text);
        return $markup;
    }


    protected function raws(array $raws) {
        $current=null;
        foreach ($raws as $line) {
            if (chop($line)==='') {
                if (isset($current))
                    $current['interrupt']=true;
                continue;
            }
            if (strpos($line,"\t")!==false) {
                $part=explode("\t",$line);
                $line=$part[0];
                unset($part[0]);
                foreach ($part as $str) {
                    $abbr=4-mb_strlen($line,'utf-8')%4;
                    $line.=str_repeat(' ',$abbr);
                    $line.=$str;
                }
            }
            $indent=0;
            while (isset($line[$indent])&&$line[$indent]===' ') $indent++;
            $text=$indent>0?substr($line,$indent):$line;
            $ln=['body'=>$line,'indent'=>$indent,'text'=>$text];
            if (isset($current['canup'])) {
                $block=$this->{'_'.$current['type'].'up'}($ln,$current);
                if (isset($block)) {
                    $current=$block;
                    continue;
                }
                else if ($this->_canend($current['type']))
                    $current=$this->{'_'.$current['type'].'end'}($current);
            }
            $types=$this->chars['raw'];
            if (isset($this->mdtag['types'][$text[0]]))
                foreach ($this->mdtag['types'][$text[0]] as $bltype)
                    $types[]=$bltype;
            foreach ($types as $bltype) {
                $block=$this->{'_'.$bltype}($ln,$current);
                if (isset($block)) {
                    $block['type']=$bltype;
                    if (!isset($block['identified'])) {
                        $blocks[]=$current;
                        $block['identified']=true;
                    }
                    if ($this->_canup($bltype))
                        $block['canup']=true;
                    $current=$block;
                    continue 2;
                }
            }
            if (isset($current)
            &&!isset($current['type'])
            &&!isset($current['interrupt']))
                $current['element']['text'].="\n".$text;
            else {
                $blocks[]=$current;
                $current=$this->p($ln);
                $current['identified']=true;
            }
        }
        if (isset($current['canup'])
        &&$this->_canend($current['type']))
            $current=$this->{'_'.$current['type'].'end'}($current);
        $blocks[]=$current;
        unset($blocks[0]);
        $markup='';
        foreach ($blocks as $block) {
            if (isset($block['hidden']))
                continue;
            $markup.="\n";
            $markup.=isset($block['markup'])?$block['markup']:$this->element($block['element']);
        }
        $markup.="\n";
        return $markup;
    }


    protected function _canup($tp) {
        return method_exists($this,'_'.$tp.'up');
    }


    protected function _canend($tp) {
        return method_exists($this,'_'.$tp.'end');
    }


    protected function _code($ln,$block=null) {
        if (isset($block)
        &&!isset($block['type'])
        &&!isset($block['interrupt']))
            return;
        if ($ln['indent']>=4)
            return ['element'=>[
                    'name'=>'pre',
                    'handler'=>'element',
                    'text'=>[
                        'name'=>'code',
                        'text'=>substr($ln['body'],4)
                    ]
                ]
            ];
    }


    protected function _codeup($ln,$block) {
        if ($ln['indent']>=4) {
            if (isset($block['interrupt'])) {
                $block['element']['text']['text'].="\n";
                unset($block['interrupt']);
            }
            $block['element']['text']['text'].="\n".substr($ln['body'],4);
            return $block;
        }
    }


    protected function _codeend($block) {
        $block['element']['text']['text']=htmlspecialchars($block['element']['text']['text'],ENT_NOQUOTES,'UTF-8');
        return $block;
    }


    protected function _comment($ln) {
        if ($this->escape)
            return;
        if (isset($ln['text'][3])
        &&$ln['text'][3]==='-'
        &&$ln['text'][2]==='-'
        &&$ln['text'][1]==='!') {
            $block=['markup'=>$ln['body']];
            if (preg_match('/-->$/',$ln['text']))
                $block['closed']=true;
            return $block;
        }
    }


    protected function _commentup($ln,array $block) {
        if (isset($block['closed']))
            return;
        $block['markup'].="\n".$ln['body'];
        if (preg_match('/-->$/',$ln['text']))
            $block['closed']=true;
        return $block;
    }


    protected function _codelong($ln) {
        if (preg_match('/^['.$ln['text'][0].']{3,}[ ]*([\w-]+)?[ ]*$/',$ln['text'],$found)) {
            $elem=['name'=>'code','text'=>''];
            if (isset($found[1])) {
                $cls='language-'.$found[1];
                $elem['attribute']=['class'=>$cls];
            }
            return [
                'char'=>$ln['text'][0],
                'element'=>[
                    'name'=>'pre',
                    'handler'=>'element',
                    'text'=>$elem
                ]
            ];
        }
    }


    protected function _codelongup($ln,$block) {
        if (isset($block['end']))
            return;
        if (isset($block['interrupt'])) {
            $block['element']['text']['text'].="\n";
            unset($block['interrupt']);
        }
        if (preg_match('/^'.$block['char'].'{3,}[ ]*$/',$ln['text'])) {
            $block['element']['text']['text']=substr($block['element']['text']['text'],1);
            $block['end']=true;
            return $block;
        }
        $block['element']['text']['text'].="\n".$ln['body'];
        return $block;
    }


    protected function _codelongend($block) {
        $block['element']['text']['text']=htmlspecialchars($block['element']['text']['text'],ENT_NOQUOTES,'UTF-8');
        return $block;
    }


    protected function _header($ln) {
        if (isset($ln['text'][1])) {
            $level=1;
            while (isset($ln['text'][$level])&&$ln['text'][$level]==='#')
                $level++;
            if ($level>6)
                return;
            return [
                'element'=>[
                    'name'=>'h'.min(6,$level),
                    'text'=>trim($ln['text'],'# '),
                    'handler'=>'ln'
                ]
            ];
        }
    }


    protected function _list($ln) {
        list ($name,$rgx)=$ln['text'][0]<='-'?['ul','[*+-]']:['ol','[0-9]+[.]'];
        if (preg_match('/^('.$rgx.'[ ]+)(.*)/',$ln['text'],$found)) {
            $block=[
                'indent'=>$ln['indent'],
                'pattern'=>$rgx,
                'element'=>[
                    'name'=>$name,
                    'handler'=>'all'
                ]
            ];
            if ($name==='ol') {
                $listart=stristr($found[0],'.',true);
                if ($listart!=='1')
                    $block['element']['attribute']=['start'=>$listart];
            }
            $block['li']=[
                'name'=>'li',
                'handler'=>'li',
                'text'=>[$found[2]]
            ];
            $block['element']['text'][]=&$block['li'];
            return $block;
        }
    }


    protected function _listup($ln,array $block) {
        if ($block['indent']===$ln['indent']
        &&preg_match('/^'.$block['pattern'].'(?:[ ]+(.*)|$)/',$ln['text'],$found)) {
            if (isset($block['interrupt'])) {
                $block['li']['text'][]='';
                unset($block['interrupt']);
            }
            unset($block['li']);
            $block['li']=[
                'name'=>'li',
                'handler'=>'li',
                'text'=>[isset($found[1])?$found[1]:'']
            ];
            $block['element']['text'][]=&$block['li'];
            return $block;
        }
        if ($ln['text'][0]==='['&&$this->_reference($ln))
            return $block;
        if (!isset($block['interrupt'])) {
            $block['li']['text'][]=preg_replace('/^[ ]{0,4}/','',$ln['body']);
            return $block;
        }
        if ($ln['indent']>0) {
            $block['li']['text'][]='';
            $block['li']['text'][]=preg_replace('/^[ ]{0,4}/','',$ln['body']);
            unset($block['interrupt']);
            return $block;
        }
    }


    protected function _quote($ln) {
        if (preg_match('/^>[ ]?(.*)/',$ln['text'],$found)) {
            return [
                'element'=>[
                    'name'=>'_quote',
                    'handler'=>'raws',
                    'text'=>(array)$found[1]
                ]
            ];
        }
    }


    protected function _quoteup($ln,array $block) {
        if ($ln['text'][0]==='>'&&preg_match('/^>[ ]?(.*)/',$ln['text'],$found)) {
            if (isset($block['interrupt'])) {
                $block['element']['text'][]='';
                unset($block['interrupt']);
            }
            $block['element']['text'][]=$found[1];
            return $block;
        }
        if (!isset($block['interrupt'])) {
            $block['element']['text'][]=$ln['text'];
            return $block;
        }
    }


    protected function _rule($ln) {
        if (preg_match('/^(['.$ln['text'][0].'])([ ]*\1){2,}[ ]*$/',$ln['text'])) {
            return ['element'=>['name'=>'hr']];
        }
    }


    protected function _textheader($ln,array $block=null) {
        if (!isset($block)
        ||isset($block['type'])
        ||isset($block['interrupt']))
            return;
        if (chop($ln['text'],$ln['text'][0])==='') {
            $block['element']['name']=$ln['text'][0]==='='?'h1':'h2';
            return $block;
        }
    }


    protected function _markup($ln) {
        if ($this->escape)
            return;
        if (preg_match('/^<(\w*)(?:[ ]*'.$this->chars['regex']['html'].')*[ ]*(\/)?>/',$ln['text'],$found)) {
            if (in_array(strtolower($found[1]),$this->chars['level']))
                return;
            $block=['name'=>$found[1],'depth'=>0,'markup'=>$ln['text']];
            $rest=substr($ln['text'],strlen($found[0]));
            if (trim(substr($ln['text'],strlen($found[0])))==='') {
                if (isset($found[2])||in_array($found[1],$this->chars['void'])) {
                    $block['closed']=true;
                    $block['void']=true;
                }
            }
            else {
                if (isset($found[2])||in_array($found[1],$this->chars['void']))
                    return;
                if (preg_match('/<\/'.$found[1].'>[ ]*$/i',$rest))
                    $block['closed']=true;
            }
            return $block;
        }
    }


    protected function _markupup($ln,array $block) {
        if (isset($block['closed']))
            return;
        if (preg_match('/^<'.$block['name'].'(?:[ ]*'.$this->chars['regex']['html'].')*[ ]*>/i',$ln['text']))
            $block['depth']++;
        if (preg_match('/(.*?)<\/'.$block['name'].'>[ ]*$/i',$ln['text'],$found)) {
            if ($block['depth']>0)
                $block['depth']--;
            else $block['closed']=true;
        }
        if (isset($block['interrupt'])) {
            $block['markup'].="\n";
            unset($block['interrupt']);
        }
        $block['markup'].="\n".$ln['body'];
        return $block;
    }


    protected function _reference($ln) {
        if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/',$ln['text'],$found)) {
            $dt=['url'=>$found[2],'title'=>null];
            if (isset($found[3]))
                $dt['title']=$found[3];
            $this->defined['reference'][strtolower($found[1])]=$dt;
            $block=['hidden'=>true];
            return $block;
        }
    }


    protected function _table($ln,array $block=null) {
        if (!isset($block)
        ||isset($block['type'])
        ||isset($block['interrupt']))
            return;
        if (strpos($block['element']['text'],'|')!==false
        &&chop($ln['text'],' -:|')==='') {
            $align=[];
            $barrier=explode('|',trim(trim($ln['text']),'|'));
            foreach ($barrier as $barr) {
                $barr=trim($barr);
                if ($barr==='') continue;
                $aligns=null;
                if ($barr[0]===':')
                    $aligns='left';
                if (substr($barr,-1)===':')
                    $aligns=$aligns==='left'?'center':'right';
                $align[]=$aligns;
            }
            $elheader=[];
            $header=explode('|',trim(trim($block['element']['text']),'|'));
            foreach ($header as $index=>$hdr) {
                $elhdr=[
                    'name'=>'th',
                    'text'=>trim($hdr),
                    'handler'=>'ln'
                ];
                if (isset($align[$index])) {
                    $aligns=$align[$index];
                    $elhdr['attribute']=[
                        'style'=>'text-align: '.$aligns.';'
                    ];
                }
                $elheader[]=$elhdr;
            }
            $block=[
                'alignments'=>$align,
                'identified'=>true,
                'element'=>[
                    'name'=>'table',
                    'handler'=>'all'
                ]
            ];
            $block['element']['text'][]=[
                'name'=>'thead',
                'handler'=>'all'
            ];
            $block['element']['text'][]=[
                'name'=>'tbody',
                'handler'=>'all',
                'text'=>[]
            ];
            $block['element']['text'][0]['text'][]=[
                'name'=>'tr',
                'handler'=>'all',
                'text'=>$elheader
            ];
            return $block;
        }
    }


    protected function _tableup($ln,array $block) {
        if (isset($block['interrupt']))
            return;
        if ($ln['text'][0]==='|'
        ||strpos($ln['text'],'|')) {
            $elmn=[];
            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/',trim(trim($ln['text']),'|'),$found);
            foreach ($found[0] as $index=>$cell) {
                $elem=[
                    'name'=>'td',
                    'handler'=>'ln',
                    'text'=>trim($cell)
                ];
                if (isset($block['alignments'][$index]))
                    $elem['attribute']=[
                        'style'=>'text-align: '.$block['alignments'][$index].';'
                    ];
                $elmn[]=$elem;
            }
            $elem=[
                'name'=>'tr',
                'handler'=>'all',
                'text'=>$elmn
            ];
            $block['element']['text'][1]['text'][]=$elem;
            return $block;
        }
    }


    protected function p($ln) {
        return [
            'element'=>[
                'name'=>'p',
                'text'=>$ln['text'],
                'handler'=>'ln'
            ]
        ];
    }


    protected function lncode($excrpt) {
        if (preg_match('/^('.$excrpt['text'][0].
            '+)[ ]*(.+?)[ ]*(?<!'.$excrpt['text'][0].
            ')\1(?!'.$excrpt['text'][0].')/s',
        $excrpt['text'],$found))
            return [
                'ext'=>strlen($found[0]),
                'element'=>[
                    'name'=>'code',
                    'text'=>preg_replace("/[ ]*\n/",' ',htmlspecialchars($found[2],ENT_NOQUOTES,'UTF-8'))
                ]
            ];
    }


    protected function lnemail($excrpt) {
        if (strpos($excrpt['text'],'>')!==false
        &&preg_match('/^<((mailto:)?\S+?@\S+?)>/i',$excrpt['text'],$found)) {
            $url=$found[1];
            if (!isset($found[2]))
                $url='mailto:'.$url;
            return [
                'ext'=>strlen($found[0]),
                'element'=>[
                    'name'=>'a',
                    'text'=>$found[1],
                    'attribute'=>['href'=>$url]
                ]
            ];
        }
    }


    protected function lnem($excrpt) {
        if (!isset($excrpt['text'][1]))
            return;
        if ($excrpt['text'][1]===$excrpt['text'][0]
        &&preg_match($this->chars['regex']['strong'][$excrpt['text'][0]],$excrpt['text'],$found))
            $emp='strong';
        elseif (preg_match($this->chars['regex']['em'][$excrpt['text'][0]],$excrpt['text'],$found))
            $emp='em';
        else return;
        return [
            'ext'=>strlen($found[0]),
            'element'=>[
                'name'=>$emp,
                'handler'=>'ln',
                'text'=>$found[1]
            ]
        ];
    }


    protected function lnesc($excrpt) {
        if (isset($excrpt['text'][1])
        &&in_array($excrpt['text'][1],$this->chars['special']))
            return [
                'markup'=>$excrpt['text'][1],
                'ext'=>2
            ];
    }


    protected function lngamln($excrpt) {
        if (!isset($excrpt['text'][1])
        ||$excrpt['text'][1]!=='[')
            return;
        $excrpt['text']=substr($excrpt['text'],1);
        $lnk=$this->lnlink($excrpt);
        if ($lnk===null)
            return;
        $il=[
            'ext'=>$lnk['ext']+1,
            'element'=>[
                'name'=>'img',
                'attribute'=>[
                    'src'=>$lnk['element']['attribute']['href'],
                    'alt'=>$lnk['element']['text']
                ]
            ]
        ];
        $il['element']['attribute']+=$lnk['element']['attribute'];
        unset($il['element']['attribute']['href']);
        return $il;
    }


    protected function lnlink($excrpt) {
        $elem=[
            'name'=>'a',
            'handler'=>'ln',
            'text'=>null,
            'attribute'=>[
                'href'=>null,
                'title'=>null
            ]
        ];
        $ext=0;
        $rest=$excrpt['text'];
        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/',$rest,$found)) {
            $elem['text']=$found[1];
            $ext+=strlen($found[0]);
            $rest=substr($rest,$ext);
        }
        else return;
        if (preg_match('/^[(]((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*"|\'[^\']*\'))?[)]/',$rest,$found)) {
            $elem['attribute']['href']=$found[1];
            if (isset($found[2]))
                $elem['attribute']['title']=substr($found[2],1,-1);
            $ext+=strlen($found[0]);
        }
        else {
            if (preg_match('/^\s*\[(.*?)\]/',$rest,$found)) {
                $def=strlen($found[1])?$found[1]:$elem['text'];
                $def=strtolower($def);
                $ext+=strlen($found[0]);
            }
            else $def=strtolower($elem['text']);
            if (!isset($this->defined['reference'][$def]))
                return;
            $defi=$this->defined['reference'][$def];
            $elem['attribute']['href']=$defi['url'];
            $elem['attribute']['title']=$defi['title'];
        }
        $elem['attribute']['href']=str_replace(['&','<'],['&amp;','&lt;'],$elem['attribute']['href']);
        return [
            'ext'=>$ext,
            'element'=>$elem
        ];
    }


    protected function lnmarkup($excrpt) {
        if ($this->escape
        ||strpos($excrpt['text'],'>')===false)
            return;
        if ($excrpt['text'][1]==='/'
        &&preg_match('/^<\/\w*[ ]*>/s',$excrpt['text'],$found))
            return [
                'markup'=>$found[0],
                'ext'=>strlen($found[0])
            ];
        if ($excrpt['text'][1]==='!'
        &&preg_match('/^<!---?[^>-](?:-?[^-])*-->/s',$excrpt['text'],$found))
            return [
                'markup'=>$found[0],
                'ext'=>strlen($found[0])
            ];
        if ($excrpt['text'][1]!==' '
        &&preg_match('/^<\w*(?:[ ]*'.$this->chars['regex']['html'].')*[ ]*\/?>/s',$excrpt['text'],$found))
            return [
                'markup'=>$found[0],
                'ext'=>strlen($found[0])
            ];
    }


    protected function lnspecialchars($excrpt) {
        if ($excrpt['text'][0]==='&'
        &&!preg_match('/^&#?\w+;/',$excrpt['text']))
            return [
                'markup'=>'&amp;'
                ,'ext'=>1
            ];
        $kk=['>'=>'gt','<'=>'lt','"'=>'quot'];
        if (isset($kk[$excrpt['text'][0]]))
            return [
                'markup'=>'&'.$kk[$excrpt['text'][0]].';',
                'ext'=>1
            ];
    }


    protected function lns($excrpt) {
        if (!isset($excrpt['text'][1]))
            return;
        if ($excrpt['text'][1]==='~'
        &&preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/',$excrpt['text'],$found))
            return [
                'ext'=>strlen($found[0]),
                'element'=>[
                    'name'=>'del',
                    'text'=>$found[1],
                    'handler'=>'ln'
                ]
            ];
    }


    protected function lnurl($excrpt) {
        if ($this->link!==true
        ||!isset($excrpt['text'][2])
        ||$excrpt['text'][2]!=='/')
            return;
        if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui',$excrpt['context'],$found,PREG_OFFSET_CAPTURE)) {
            return [
                'ext'=>strlen($found[0][0]),
                'position'=>$found[0][1],
                'element'=>[
                    'name'=>'a',
                    'text'=>$found[0][0],
                    'attribute'=>['href'=>$found[0][0]]
                ]
            ];
        }
    }


    protected function lntagurl($excrpt) {
        if (strpos($excrpt['text'],'>')!==false
        &&preg_match('/^<(\w+:\/{2}[^ >]+)>/i',$excrpt['text'],$found)) {
            $url=str_replace(['&','<'],['&amp;','&lt;'],$found[1]);
            return [
                'ext'=>strlen($found[0]),
                'element'=>[
                    'name'=>'a',
                    'text'=>$url,
                    'attribute'=>['href'=>$url]
                ]
            ];
        }
    }


    protected function rawtext($text) {
        if ($this->br)
            $text=preg_replace('/[ ]*\n/',"<br />\n",$text);
        else $text=str_replace(" \n","\n",preg_replace('/(?:[ ][ ]+|[ ]*\\\\)\n/',"<br />\n",$text));
        return $text;
    }


    protected function element(array $elem) {
        $markup='<'.$elem['name'];
        if (isset($elem['attribute'])) {
            foreach ($elem['attribute'] as $k=>$v) {
                if ($v===null)
                    continue;
                $markup.=' '.$k.'="'.$v.'"';
            }
        }
        if (isset($elem['text'])) {
            $markup.='>';
            if (isset($elem['handler']))
                $markup.=$this->{$elem['handler']}($elem['text']);
            else $markup.=$elem['text'];
            $markup.='</'.$elem['name'].'>';
        }
        else $markup.=' />';
        return $markup;
    }


    protected function all(array $elements) {
        $markup='';
        foreach ($elements as $elem)
            $markup.="\n".$this->element($elem);
        $markup.="\n";
        return $markup;
    }


    protected function li($raws) {
        $markup=$this->raws($raws);
        $trim=trim($markup);
        if (!in_array('',$raws)
        &&substr($trim,0,3)==='<p>') {
            $markup=substr($trim,3);
            $markup=substr_replace($markup,'',strpos($markup,"</p>"),4);
        }
        return $markup;
    }
}
