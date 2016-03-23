<?php
namespace Sooh\DB\Cases;
/**
 * 基础账号
 * (测试环境下，0<deploymentCode<=30, 123456是万能验证码)
 * @author Simon Wang <hillstill_simon@163.com>
 */
class SMSCode extends \Sooh\DB\Base\KVObj{
	public static $maxCounterPerHour = 3;
	public static $maxErrorPerHour=5;
	public static $expiredOfCode=900;
	protected static function splitedTbName($n,$isCache)
	{
//		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
//		else 
		return 'tb_sms_valid_'.($n % static::numToSplit());
	}
	public static function numToSplit() {
		return 2;
	}
	public static function idFor_dbByObj_InConf($isCache) {
		return 'smscode';
	}
	/**
	 * @return SMSCode
	 */
	public static function getCopy($phone)
	{
		$o =  parent::getCopy(array('phone'=>$phone));
		$o->load();
		return $o;
	}
	private $dat;
	/**
	 *
	 * @var \Sooh\Base\Time; 
	 */
	private $dt;
	public function load($fields = '*') {
		parent::load($fields);
		$tmp = parent::getField('dat',true);
		$this->dt = \Sooh\Base\Time::getInstance();
		$cmp = $this->dt->timestamp()-3600;
		if(!empty($tmp)){
			if(is_string($tmp)){
				$this->dat = json_decode($tmp, true);
			}else{
				$this->dat = $tmp;
			}
			foreach($this->dat['errors'] as $k=>$v){
				if($v<$cmp){
					unset($this->dat['errors'][$k]);
				}
			}
			foreach($this->dat['codes'] as $k=>$v){
				if($v<$cmp){
					unset($this->dat['codes'][$k]);
				}
			}
		}else{
			$this->dat = array();
		}
	}
	
	public function chkCode($uInput)
	{
		$tmp = \Sooh\Base\ini::getInstance()->get('deploymentCode');
		if($tmp>0 && $tmp<=30){
			if($uInput==='123456'){
				return true;
			}
		}
		if(sizeof($this->dat['errors'])>self::$maxErrorPerHour){
			return false;
		}
		if(isset($this->dat['codes'][$uInput]) && $this->dt->timestamp()-$this->dat['codes'][$uInput]<self::$expiredOfCode){
			$this->dat['lastOk']=$this->dt->timestamp();
			$this->dat['errors']=0;
			parent::setField('dat', $this->dat);
			$this->update();
			return true;
		}else{
			$this->dat['errors'][]=$this->dt->timestamp();
			parent::setField('dat', $this->dat);
			$this->update();
			return false;
		}
	}
	/**
	 * 
	 * @param callable $funcSend
	 * @param string $msgFormat
	 * @param string $code
	 * @throws Exception on update-db failed or send sms failed
	 * @return boolean return false on too many times
	 */
	public function sendCode($funcSend,$msgFormat,$code=null)
	{
		$sentCounterInHour = sizeof($this->dat['codes']);
		if($sentCounterInHour>self::$maxCounterPerHour){
			return false;
		}
		if($code===null){
			$code = substr(uniqid(),-6);
		}
		$this->dat['codes'][$code]=$this->dt->timestamp();
		parent::setField('dat', $this->dat);
		$this->update();
		$msgFormat = str_replace('{code}', $code, $msgFormat);
		if(is_array($funcSend)||  is_string($funcSend)){
			$ret = call_user_func($funcSend, current($this->pkey),$msgFormat);
		}else{
			$ret = $funcSend(current($this->pkey),$msgFormat);
		}
		if($ret===false){
			throw new \ErrorException('send sms failed:'.$msgFormat);
		}
		return true;
	}
}
