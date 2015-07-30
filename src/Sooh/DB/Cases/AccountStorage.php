<?php
namespace Sooh\DB\Cases;
/**
 * 账户表存取类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class AccountStorage extends \Sooh\DB\Base\KVObj{
	public static $__tbname='accounts';
	//针对缓存，非缓存情况下具体的表的名字
	protected static function splitedTbName($n,$isCache)
	{
//		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
//		else 
		return 'tb_'.self::$__tbname.'_'.($n % static::numToSplit());
	}

	/**
	 * @return AccountStorage
	 */
	public static function getCopy($account,$camefrom='local')
	{
		return parent::getCopy(array('camefrom'=>$camefrom,'loginname'=>$account));
	}
}
