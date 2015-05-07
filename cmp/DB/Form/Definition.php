<?php
namespace Sooh\DB\Form;
/**
 * 定义字段在不同情况下使用哪种表现形式
 * 已知缺陷：verifyPasswordCmp标记需要比较两次密码一致的时候，使用的是static变量保存的，估一个请求里只能有一次这种验证
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Definition {
	const text = 'text';
	const mulit= 'textarea';
	const passwd= 'password';
	const chkbox='checkbox';
	const radio = 'radio';
	const select= 'select';
	const hidden= 'hidden';
	const constval= 'const';
	const date = 'date';
	
	public $capt;
	public $value;
	/**
	 *
	 * @var \Sooh\DB\Form\Options
	 */
	public $options;
	protected $inputDefault;
	protected $inputWhenUpdate=false;
	/**
	 * 构建input的工厂函数
	 * 
	 * @param string $capt 标题
	 * @param string $keyname 字段内容
	 * @param mix $value 值
	 * @param string $inputDefault 默认的输入方式
	 * @return \Sooh\DB\Form\Definition
	 */
	public static function factory($capt,$value,$inputDefault) {
		$o = new Definition();
		$o->capt=$capt;
		$o->value=$value;
		$o->inputDefault = $inputDefault;
		return $o;
	}
	/**
	 * 更多设置
	 * @param \Sooh\DB\Form\Options $options
	 * @param string $inputWhenUpdate 主要用于主键在更新时不可编辑
	 * @return \Sooh\DB\Form\Definition
	 */
	public function initMore($options=null,$inputWhenUpdate=null)
	{
		$this->options = $options;
		$this->inputWhenUpdate=$inputWhenUpdate;
		return $this;
	}
	/**
	 * 
	 * @param string $crud
	 * @param array $record
	 * @return string
	 */
	public function input($crud='c')
	{
		if($crud=='c')return $this->inputDefault;
		else{
			if($this->inputWhenUpdate)return $this->inputWhenUpdate;
			else return $this->inputDefault;
		}
	}
	public $verify=null;
	/**
	 * 
	 * @param type $required
	 * @param type $min
	 * @param type $max
	 * @return \Sooh\DB\Form\Definition
	 */
	public function verifyInteger($required=0,$min=0,$max=2000000000)
	{
		$this->verify=array('required'=>$required,'type'=>'int','min'=>$min,'max'=>$max);
		return $this;
	}
	/**
	 * 
	 * @param type $required
	 * @param type $min
	 * @param type $max
	 * @return \Sooh\DB\Form\Definition
	 */
	public function verifyString($required=0,$min=0,$max=0)
	{
		$this->verify=array('required'=>$required,'type'=>'str','min'=>$min,'max'=>$max);
		return $this;
	}
	protected static $pwdCmp=null;
	/**
	 * 
	 * @param type $min
	 * @param type $max
	 * @return \Sooh\DB\Form\Definition
	 */
	public function verifyPasswordCmp($min=0,$max=0)
	{
		if(self::$pwdCmp==null){
			self::$pwdCmp= '__CSS_'.rand(10000,99999).'__';
			$this->verify=array('required'=>1,'type'=>'str','min'=>$min,'max'=>$max,'cssId'=>self::$pwdCmp);
		}else{
			$this->verify=array('required'=>1,'type'=>'str','min'=>$min,'max'=>$max,'cmpCssId'=>self::$pwdCmp);
		}
		return $this;
	}
}
