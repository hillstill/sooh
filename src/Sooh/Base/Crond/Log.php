<?php
namespace Sooh\Base\Crond;
/**
 * 记录计划任务日志
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Log {
	public function writeCrondLog($taskid,$msg)
	{
		error_log("\tCrond ".  getmypid()."#\t$taskid\t$msg");
	}
	public function updCrondStatus($ymd,$hour,$taskid,$lastStatus,$isOkFinal,$isManual=0)
	{
		try{
			\Sooh\DB\Broker::errorMarkSkip();
			\Sooh\DB\Broker::getInstance(\PrjLib\Tbname::db_log)->addRecord('db_log.tb_crond_log', array('ymdh'=>$ymd*100+$hour,'taskid'=>$taskid,'lastStatus'=>$lastStatus,'ymdhis'=>date('YmdHis'),'lastRet'=>$isOkFinal,'isManual'=>$isManual));
		} catch (\ErrorException $e) {
			if(\Sooh\DB\Broker::errorIs($e)){
				\Sooh\DB\Broker::getInstance(\PrjLib\Tbname::db_log)->updRecords('db_log.tb_crond_log', array('lastStatus'=>$lastStatus,'ymdhis'=>date('YmdHis'),'lastRet'=>$isOkFinal,'isManual'=>$isManual),array('ymdh'=>$ymd*100+$hour,'taskid'=>$taskid,));
			}else throw $e;
		}
	}
	
	public function ensureCrondTable()
	{
		\Sooh\DB\Broker::getInstance(\PrjLib\Tbname::db_log)->ensureObj('db_log.tb_crond_log', array(
			'ymdh'=>'bigint not null default 0','taskid'=>'varchar(64) not null',
			'lastStatus'=>'varchar(512)','lastRet'=>'tinyint not null default 0','isManual'=>'tinyint not null default 0',
			'ymdhis'=>'bigint not null default 0'
		),array('ymdh','taskid'));
	}
	public function remoreCrondLogExpired($dayExpired=190)
	{
		$dt = \Sooh\Base\Time::getInstance()->getInstance()->timestamp(-$dayExpired);
		\Sooh\DB\Broker::getInstance(\PrjLib\Tbname::db_log)->delRecords('db_log.tb_crond_log', array('ymdh<'=>date('YmdH',$dt)));
	}
}
