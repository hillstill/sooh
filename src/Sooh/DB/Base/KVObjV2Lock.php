<?php
namespace Sooh\DB\Base;

/**
 * 锁字段封装类
 * 锁的超时时间
 * @author wang.ning
 */
class KVObjV2Lock{
	public $msg;
	public $ip;
	/*
	 * 过期时间的时间戳
	 * @var int
	 */
	public $expire;
	/**
	 * 创建时间的时间戳
	 * @var int
	 */
	public $create;
	/**
	 * 是否是当前进程锁定的
	 * @var type 
	 */
	public $lockedByThisProcess=false;
	/**
	 * 
	 * @return string
	 */
	public function toString()
	{
		return '"msg":"'.$this->msg.'","ip":"'.$this->ip.'","expire":"'.date("YmdHis",$this->expire).'","create":"'.date("YmdHis",$this->create).'"';
	}
	/**
	 * 还原
	 * @param type $str
	 * @return \Sooh\DB\Base\KVObjV2Lock
	 */
	public static function facotry($str)
	{
		$r = json_decode($str,true);
		$o = new KVObjV2Lock;
		$r['expire']=self::recoverDt($r['expire']);
		$r['create']=self::recoverDt($r['create']);
		foreach($r as $k=>$v){
			$o->$k = $v;
		}
		return $o;
	}
	protected static function recoverDt($yyyymmddhhiiss)
	{
		return mktime(substr($yyyymmddhhiiss,8,2),substr($yyyymmddhhiiss,10,2),substr($yyyymmddhhiiss,12,2),
				substr($yyyymmddhhiiss,4,2),substr($yyyymmddhhiiss,6,2),substr($yyyymmddhhiiss,0,4));
	}
}
