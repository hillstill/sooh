<?php
namespace Sooh\KVObj\Cases;

use \Sooh\Base\Trace as sooh_trace;
use \Sooh\Base\Time as sooh_time;
use \Sooh\DB\Broker as sooh_broker;
/**
 * 第一期只做了单表的调试，基于KVObj理论上可以改成支持分表的
 * 
 * 调试此类:
 *		sooh_trace::focusClass('Sooh\DB\Broker');
 *		sooh_trace::focusClass('Sooh\DB\Cases\SessionStorage');
 *		需要在Index.php中加入session_write_close(),提前执行相关操作
 * @author Simon Wang <sooh_simon@163.com> 
 */

class Crud extends \Sooh\DB\Base\KVObj
{
	/**
	 * 覆盖的用于定位账号表的函数
	 * @param type $n
	 * @param type $isCache
	 * @return string
	 */
	protected static function splitedTbName($n,$isCache)
	{
		return 'tb_accounts';
//		if($isCache)return 'cache_'.($n%10);
//		else return 'test_id_v';
	}
}
