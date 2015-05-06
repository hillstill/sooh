<?php
namespace Sooh\DB\Form;
use \Sooh\DB\Form\Definition as  sooh_formdef;
/**
 * 表单基类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Base {
	protected static $_copy=array();
	/**
	 * 
	 * @param string $id
	 * @param string $cruds  c(create) r(read) u(update) d(delete) s(search)
	 * @return \Sooh\DWZ\Form
	 */	
	public static function getCopy($id='default',$cruds='c')
	{
		if(!isset(self::$_copy[$id])){
			$nm = get_called_class();
			self::$_copy[$id] = new $nm();
			self::$_copy[$id]->guid=$id;
			self::$_copy[$id]->crudType=$cruds;
		}
		return self::$_copy[$id];
	}
	protected $guid;

	public $items=array(
		
	);
	protected $methods=array(
		'_eq'=>'=',	'_ne'=>'!',
		'_gt'=>'>',	'_g2'=>']',
		'_lt'=>'<',	'_l2'=>'[',
		'_lk'=>'*',
	);

	/**
	 * 从request中获取表单的值
	 * @param type $request
	 * @param \Sooh\DB\Form\Definition $_ignore_
	 */
	public function fillValues($request,$_ignore_=null)
	{
		
		$this->values['__formguid__']=isset($request['__formguid__'])?$request['__formguid__']:null;
		foreach($this->items as $k=>$_ignore_){
			$v = isset($request[$k])?$request[$k]:null;
			if($v!==null){
				if(!is_a($_ignore_,'\Sooh\DB\Form\Definition')){
					if($_ignore_===null)$this->values[$k]=$v;
					else $this->values[$k]=$_ignore_;
				}else $this->values[$k]=$_ignore_->value=$v;
			}
		}
		
		$this->flgIsThisForm = $this->values['__formguid__']==$this->guid;

		return $this->values;
	}
	public $flgIsThisForm=false;
	protected $values=array();
	/**
	 * 
	 * @param type $k
	 * @return \Sooh\DB\Form\Definition
	 */
	public function item($k)
	{
		return $this->items[$k];
	}
	public function cruds()
	{
		return $this->crudType;
	}
	/**
	 * 
	 * @param array $keyExclude 排除哪些值
	 * @return type
	 */
	public function getFields($keyExclude=array())
	{
		$tmp=array();
		foreach($this->values as $k=>$v){
			if($k['0']!='_' && !in_array($k, $keyExclude))$tmp[$k]=$v;
		}
		return $tmp;
	}
	
	public function getWhere()
	{
		$where=array();
		foreach($this->values as $k=>$v){
			if($v==='')continue;
			if($k[0]=='_'){
				if($k[1]!=='_'){
					$cmp=substr($k,-3);
					$k = substr($k,1,-3);
					if(isset($this->methods[$cmp])){
						if($cmp=='_lk')$where[$k.$this->methods[$cmp]]='%'.$v.'%';
						else $where[$k.$this->methods[$cmp]]=$v;
					}
				}
			}
		}
		return $where;
	}
	public function toArray()
	{
		return $this->values;
	}
	protected $crudType='c';
	/**
	 * 
	 * @param char $type [c|r|u|d]
	 * @return \Sooh\DB\Form\Base
	 */
	public function switchCRUD($type)
	{
		switch ($type){
			case 'c'://create
			case 'r'://read
			case 'u'://update
			case 'd'://delete
			case 's'://search
				$this->crudType=$type;
				break;
			default:
				throw new \ErrorException('invalid crud type');
		}
		return $this;
	}

	/**
	 * 构建输入表单列表
	 * @param string $tpl
	 * @param string $k
	 * @param \Sooh\DB\Form\Definition $_ignore_
	 * @return string
	 */
	public function renderDefault($tpl,$k=null,$_ignore_=null)
	{
		if($k===null){
			$str='<input type=hidden name=__formguid__ value='.$this->guid.'>';
			$ks = array_keys($this->items);
			foreach($ks as $k){
				$str.=$this->renderDefault($tpl,$k)."\n";
			}
			return $str;
		}else{
			$_ignore_=$this->items[$k];
			if(is_scalar($_ignore_)){
				return '<input type=hidden name="'.$k.'" value="'.htmlspecialchars($_ignore_).'">';
			}elseif($_ignore_===null){
				return '<input type=hidden name="'.$k.'" value="">';
			}elseif($tpl===null){
				return '<input type=hidden name="'.$k.'" value="'.htmlspecialchars($_ignore_->value).'">';
			}else{
				$input = $this->buildInput($k, $_ignore_);
				if($input===false){
					return '<input type=hidden name="'.$k.'" value="'.$_ignore_->value.'">';
				}
			}
			return str_replace(array('{capt}','{input}'), array($_ignore_->capt,$input), $tpl);
		}
	}
	/**
	 * 从第几个开始就是hidden的方式了,0对应第一个，1对应第2个
	 * @param int $begin 从第几个开始隐藏
	 * @param string $tpl 显示模板
	 * @return string
	 */
	public function renderHiddenAfter($begin=0,$tpl=null)
	{
		$total = sizeof($this->items);
		if($begin==0){
			$str='<input type=hidden name=__formguid__ value='.$this->guid.'>';
			$ks = array_keys($this->items);
			foreach($ks as $k){
				$str.=$this->renderDefault(null,$k)."\n";
			}
			return $str;
		}else{
			$str='<input type=hidden name=__formguid__ value='.$this->guid.'>';
			$ks = array_keys($this->items);
			for($i=0;$i<$begin && $i<$total;$i++){
				$k = $ks[$i];
				$str.=$this->renderDefault($tpl,$k)."\n";
			}
			for(;$i<$total;$i++){
				$k = $ks[$i];
				$str.=$this->renderDefault(null,$k)."\n";
			}
			return $str;
		}
		
	}
	/**
	 * 构建input
	 * @param string $k
	 * @param \Sooh\DB\Form\Definition $define
	 */
	protected function buildInput($k,$define)
	{
		switch ($define->input($this->crudType)){
			case sooh_formdef::text:
				return '<input type=text name="'.$k.'" value="'.$define->value.'">';
			case sooh_formdef::hidden:
				return false;
			case sooh_formdef::constval:
				return  $define->value.'<input type=hidden name="'.$k.'" value="'.$define->value.'">';
				
		}
	}
}
