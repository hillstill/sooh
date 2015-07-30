<?php
namespace Sooh\Base;
/**
 * ErrorException
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class ErrException extends \ErrorException{
	const msgErrorArg='arg_error';
	const msgServerBusy='server_busy';
	const msgLocked='record_locked';
	public static function factory($msg,$code)
	{
		$e = new ErrException($message, $code);
		return $e;
	}
	public $customData=null;
	protected $isWrote=false;
	public function __toString()
	{
		if($this->isWrote===false){
			$this->isWrote=true;
			return $this->getMessage()."[Sooh_Base_Error]".$this->getTraceAsString();
		}else{
			return parent::__toString();
		}
	}
	
}
