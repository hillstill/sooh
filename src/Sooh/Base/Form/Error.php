<?php
namespace Sooh\Base\Form;
/**
 * 输入参数错误（可能是必填的没填，可能是格式错误）
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Error extends \ErrorException {
	const REQUIRED		= 1;
	const STR_LENGTH	= 2;
	const INT_OVERFLOW	= 3;
	public $param=null;
	
	public static function factory($fieldTitle,$errCode,$arrParam=null)
	{
		$err = new Error($fieldTitle, $errCode);
		$err->param=$arrParam;
		return $err;
	}
	
	/**
	 * 超出限制范围的情况下的参数
	 * @param type $min
	 * @param type $max
	 * @return type
	 */
	public static function factoryParam($min,$max)
	{
		return array('min'=>$min,'max'=>$max);
	}
	
	public function getMessage()
	{
		switch($this->getCode()){
			case self::REQUIRED:
				return parent::getMessage().' is required';
			case self::STR_LENGTH:
				return 'length of '.parent::getMessage().' needs to be '.$this->param['min']. ' to '.$this->param['max'];
			case self::INT_OVERFLOW:
				return parent::getMessage().' needs  between '.$this->param['min']. ' to '.$this->param['max'];
		}
	}
}
