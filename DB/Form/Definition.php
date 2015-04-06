<?php
namespace Sooh\DB\Form;
/**
 * 定义字段在不同情况下使用哪种表现形式
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
	public $required;
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
	public static function factory($capt,$value,$inputDefault,$required=0) {
		$o = new Definition();
		$o->capt=$capt;
		$o->value=$value;
		$o->required=$required-0;
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
}
