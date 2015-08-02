<?php
namespace Sooh\DB\Cases;
/**
 * 账户表存取类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class SessionStorage extends \Sooh\DB\Base\KVObj{
	public static $__id_in_dbByObj='default';
	public static $__nSplitedBy=1;
	protected static function idFor_dbByObj_InConf($isCache){	return self::$__id_in_dbByObj;}
	protected static function numToSplit(){return static::$__nSplitedBy;}
	//针对缓存，非缓存情况下具体的表的名字
	protected static function splitedTbName($n,$isCache)
	{
//		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
//		else 
		return 'tb_session_'.($n % static::numToSplit());
	}

	/**
	 * 说明getCopy实际返回的类，同时对于只有一个主键的，可以简化写法
	 * @return \Sooh\DB\Cases\SessionStorage
	 */
	public static function getCopy($sessionId)
	{
		return parent::getCopy(array('sessionId'=>$sessionId));
	}

	public function setVerId($id)
	{
		$this->r[$this->fieldName_verid]=$id;
	}
	public function setSessionData($arr) 
	{
		parent::setField('accountId', $arr['accountId']);
		parent::setField('sessionData', json_encode($arr));
	}
	public function getSessionData()
	{
		$ret = $this->getField('sessionData',true);
		if(is_array($ret)){
			return $ret;
		}elseif(is_string($ret)){
			return json_decode($ret,true);
		}else{
			return array();
		}
	}
	public function getArrayTrans()
	{
		$tmp = $this->r;
		unset($tmp['sessionData']);
		return $tmp;
	}
}
