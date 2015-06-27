<?php
namespace Sooh\Base\Form;
use \Sooh\Base\Form\Item as  sooh_formdef;
/**
 * 表单基类, items可以设置非\Sooh\Base\Form\Item：表示hidden模式的
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Base {
	/**
	 * return "<form action='...' method='get'  ....>" 
	 * 
	 * @param type $url
	 * @param string $id
	 * @param string $method [get post upload]
	 * @param string $other "style='xxx' onsubmit='xxx'"
	 * @return string 
	 */
	public static function renderFormTag($url,$id=null,$method=null,$other=null)
	{
		$str = "<form action=\"$url\"".(empty($id)?'':" id=\"$id\"");
		$method=  strtolower($method);
		if($method=='get'||$method=='post'){
			$str.=" method=\"$method\"";
		}elseif($method=='upload'){
			$str.=" method=\"post\" enctype=\"multipart/form-data\"";
		}else{
			$str.=" method=\"get\"";
		}
		if($other!==null){
			$str.=" $other";
		}
		return $str.">";
	}
	
	/**
	 * return "<input type=submit|reset|button value=title .....>"
	 * @param string $title title-displayed
	 * @param string $type  [submit reset button]
	 * @param string $other
	 * @return string
	 */
	public static function renderFormButton($title,$type="submit",$other=null)
	{
		$type=  strtolower($type);
		if($type=='button'||$type=='reset'||$type='submit'){
			return "<input type=\"$type\" value=\"$title\" $other>";
		}else throw new \ErrorException('button type error:'.$type);
	}
		
	
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
	 * @param array $request
	 * @param string $errClassName
	 * @param \Sooh\Base\Form\Item $_ignore_
	 * @return \Sooh\Base\Form\Error
	 */
	public function fillValues($request,$errClassName=null,$_ignore_=null)
	{
		$this->flgIsThisForm=isset($request['__formguid__'])?$request['__formguid__']:null;
		$this->flgIsThisForm = $this->flgIsThisForm===$this->guid;
		foreach($this->items as $k=>$_ignore_){
			$inputVal = isset($request[$k])?$request[$k]:null;
			if($inputVal!==null){
				if(!is_a($_ignore_,'\Sooh\Base\Form\Item')){
					$this->values[$k]=$inputVal;
				}else{
					if($_ignore_->input()==\Sooh\Base\Form\Item::constval)$this->values[$k]=$_ignore_->value;
					else {
						$err = $_ignore_->checkUserInput($inputVal,$_ignore_->capt,$errClassName);
						if($err===null)$this->values[$k]=$inputVal;
						else return $err;
					}
				}
			}
		}
		return null;
	}
	public $flgIsThisForm=false;
	protected $values=array();

	/**
	 * 获取一个输入item
	 * @param type $k
	 * @return \Sooh\Base\Form\Item
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
	 * 获取非_开头的参数，排除指定的那些参数（默认排除pageid,pagesize）
	 * @param array $keyExclude 排除哪些值
	 * @return type
	 */
	public function getFields($keyExclude=array('pageid','pagesize','orderField'))
	{
		$tmp=array();
		foreach($this->values as $k=>$v){
			if($k['0']!=='_' && !in_array($k, $keyExclude))$tmp[$k]=$v;
		}
		return $tmp;
	}
	/**
	 * 以_开头的参数构建where数组
	 * @param \Sooh\Base\Form\Item $_ignore_
	 * @return array
	 */
	public function getWhere($_ignore_=null)
	{
		$where=array();
		if($this->flgIsThisForm){
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
			foreach($this->items as $k=>$_ignore_){
				if(is_scalar($_ignore_) && $k[0]=='_'){
					if($k[1]!=='_'){
						$cmp=substr($k,-3);
						$k = substr($k,1,-3);
						if(isset($this->methods[$cmp])){
							if($cmp=='_lk')$where[$k.$this->methods[$cmp]]='%'.$_ignore_.'%';
							else $where[$k.$this->methods[$cmp]]=$_ignore_;
						}
					}
				}
			}
		}else{
			foreach($this->items as $k=>$_ignore_){
				if($k[0]=='_'){
					$cmp=substr($k,-3);
					$k = substr($k,1,-3);
					if(isset($this->methods[$cmp])){
						if(is_scalar($_ignore_) || $_ignore_===null)$where[$k.$this->methods[$cmp]]=$_ignore_;
						else $where[$k.$this->methods[$cmp]]=$_ignore_->value;
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
	 * @return \Sooh\Base\Form\Base
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
	 * @param \Sooh\Base\Form\Item $_ignore_
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
			if(!is_a($_ignore_, '\Sooh\Base\Form\Item')){//给出其他值代表hidden
				if($_ignore_===null){
					return '<input type=hidden name="'.$k.'" value="'.(isset($this->values[$k])?htmlspecialchars($this->values[$k]):'').'">';
				}elseif(is_scalar($_ignore_)){
					return '<input type=hidden name="'.$k.'" value="'.htmlspecialchars($_ignore_).'">';
				}
			}
			else{
				$inputType = $_ignore_->input($this->crudType);
				if(is_array($inputType))$input = call_user_func($inputType, $k, $_ignore_);
				elseif($tpl===null || $inputType===\Sooh\Base\Form\Item::hidden)return '<input type=hidden id="'.$k.'" name="'.$k.'" value="'.htmlspecialchars($this->valForInput($_ignore_->value, $k)).'">';
				else $input = $this->buildInput($k, $_ignore_, $inputType);
			}
			return str_replace(array('{capt}','{input}'), array($_ignore_->capt,$input), $tpl);
		}
	}
	/**
	 * 从第几个开始就是强制hidden的方式了,0对应第一个，1对应第2个
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
	protected function valForInput($valDefined,$keyInput)
	{
		if(isset($this->values[$keyInput]))$ret= $this->values[$keyInput];
		else $ret= $valDefined;
		return $ret;
	}
	/**
	 * 构建input
	 * @param string $k
	 * @param \Sooh\Base\Form\Item $define
	 * @param string $inputType
	 */
	protected function buildInput($k,$define,$inputType)
	{
		$val4Input=$this->valForInput($define->value,$k);
		switch ($inputType){
			case sooh_formdef::text:
				return '<input id="'.$k.'" type=text name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::passwd:
				return '<input id="'.$k.'" type=password name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::constval:
				if($define->options){
					$options = $define->options->getPair($this->values,$this->crudType=='s');
					return  $options[$val4Input].'<input type=hidden name="'.$k.'" value="'.$val4Input.'">';
				}else return  $val4Input.'<input type=hidden name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::select:
				$str = '<select id="'.$k.'" name="'.$k.'">';
				$options = $define->options->getPair($this->values,$this->crudType=='s');
				$found=false;
				foreach($options as $k=>$v){
					if($val4Input==$k)	{$str.= '<option value="'.$k.'"  selected>'.$v.'</option>';$found=true;}
					else $str.= '<option value="'.$k.'">'.$v.'</option>';
				}
				if(!$found){
					throw new \ErrorException($val4Input.' not found in options');
				}
				return $str.'</select>';
			case sooh_formdef::date:
				return '<input id="'.$k.'" type=text name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::mulit:
				return '<textarea id="'.$k.'" name="'.$k.'">'.$val4Input.'</textarea>';
			case sooh_formdef::chkbox:
				$str='';
				$options = $define->options->getPair($this->values,$this->crudType=='s');
				$values = explode(',', $val4Input);
				foreach($options as $i=>$v){
					if(in_array($i, $values)){
						$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" checked=true/>'.$v.'</label>';
					}else{
						$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" />'.$v.'</label>';
					}
				}
				return $str;
			case sooh_formdef::radio:
				$str='';
				$options = $define->options->getPair($this->values,$this->crudType=='s');
				$values = explode(',', $val4Input);
				foreach($options as $i=>$v){
					if(in_array($i, $values)){
						$str.='<label><input type="radio" name="'.$k.'" value="'.$i.'" checked=true/>'.$v.'</label>';
					}else{
						$str.='<label><input type="radio" name="'.$k.'" value="'.$i.'" />'.$v.'</label>';
					}
				}
				return $str;
			default:
				throw new \ErrorException("unknown input type:".var_export($inputType,true));
		}
	}
}
