<?php
namespace Sooh\Base;
/**
 * ErrorException
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class ErrException extends \ErrorException{
	public static function factory($msg,$code)
	{
		$e = new ErrException($message, $code);
		return $e;
	}
	
	protected $isWrote=false;
	public function getTraceAsString()
	{
		$this->isWrote=true;
		if($this->isWrote===false){
			return '';
		}else{
			return parent::getTraceAsString();
		}
	}
}
