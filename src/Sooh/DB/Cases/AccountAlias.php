<?php
namespace Sooh\DB\Cases;
/**
 * 基础账号
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class AccountAlias extends \Sooh\DB\Base\KVObj
{
	protected static function splitedTbName($n, $isCache)
	{
//		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
//		else 
		return 'tb_loginname_' . ($n % static::numToSplit());
	}

	public static function numToSplit()
	{
		return 2;
	}

	/**
	 * @return AccountAlias
	 */
	public static function getCopy($loginName, $cameFrom)
	{
		return parent::getCopy(array('loginName' => $loginName, 'cameFrom' => $cameFrom));
	}

	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'oauth';
	}
}
