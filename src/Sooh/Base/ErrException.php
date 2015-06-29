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
	public function __toString()
	{
		if($this->isWrote===false){
			$this->isWrote=true;
			return $this->getMessage()."[Sooh\Base\Error]".$this->getTraceString();
		}else{
			return parent::__toString();
		}
	}
	
}
