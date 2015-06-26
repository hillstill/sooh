<?php
namespace Sooh\DB\Cases;

/**
 * 记录日志的控制类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class LogDefault extends \Sooh\DB\Base\KVObj {
	protected static $__YMD;
	protected static $__id_in_dbByObj;
	public static function initYmdAndDB($ymdToday=20150401, $id_in_dbByObj='use_db_log')
	{
		static::$__YMD = $ymdToday;
		static::$__id_in_dbByObj = $id_in_dbByObj;
	}
	protected static $__params=array();
	public static function prepareParams($arr,$index=0)
	{
		if(!isset(self::$__params[$index]))self::$__params[$index]=array();
		foreach($arr as $k=>$v){
			self::$__params[$index][$k]=$v;
		}
	}
	/**
	 * 日志记录成功后的操作，返回需要返回的值
	 */
	protected function onLogWrote()
	{

	}
	
	public static function write()
	{
		$sys=null;
		try{
			$paramsWithGuid = array_pop(self::$__params);
			//var_log($paramsWithGuid,'======================log->write');
			if(empty($paramsWithGuid)){
				return null;
			}
			if(strlen($paramsWithGuid['ret'])>100){
				$paramsWithGuid['ret'] = substr($paramsWithGuid['ret'], 0,100);
			}
			$dt = \Sooh\Base\Time::getInstance();
			$className = get_called_class();
			$sys = $className::getCopy($paramsWithGuid['guid']);
			
			$sys->fillArgs($paramsWithGuid,$dt);
			//var_log($sys->dump(),'======================log->filled for '.$sys->tbname());
			\Sooh\DB\Broker::errorMarkSkip(\Sooh\DB\Error::tableNotExists);
			$sys->update();
		} catch ( \Sooh\DB\Error $e) {
			if(\Sooh\DB\Broker::errorIs($e,\Sooh\DB\Error::tableNotExists)){
				$sys->createTable ();
				$sys->update();
			}else {
				error_log("ErrorOnWriteLog:".$e->getMessage()."\n".$e->getTraceAsString());
				throw $e;
			}
		}
		if(is_a($sys, '\\Sooh\\DB\\Cases\\LogDefault')){
			return $sys->onLogWrote();
		}else{
			$errr = new \ErrorException(" $className be found in logDefault->write");
			error_log($errr->getMessage()."\n".$errr->getTraceAsString());
			return null;
		}
	}
	
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return static::$__id_in_dbByObj;
	}
	/**
	 * 
	 * @return Writor
	 */
	public static function getCopy($guid)
	{
		return parent::getCopy(array('guid'=>$guid));
	}
	protected static function splitedTbName($n,$isCache)
	{
		return 'tblog_'.static::$__YMD.'_'.($n%static::numToSplit());
	}
	protected static function numToSplit(){return 10;}
	protected $fieldsFillByArgs=array(
		'n'=>array('islogined','opcount','clienttype','contractid','narg1','narg2','narg3','num'),
		's'=>array('userid','evt','maintype','subtype',	'target','ext','ret','sarg1','sarg2','sarg3',));
	/**
	 * 接收请求的参数（诸如evt,num……）
	 * @param array $params 
	 * @param \Sooh\Base\Time $dt 
	 */
	protected function fillArgs($params,$dt)
	{
		foreach($this->fieldsFillByArgs['s'] as $k){
			$this->setField($k, empty($params[$k])?'':$params[$k]);
		}
		foreach($this->fieldsFillByArgs['n'] as $k){
			if(is_numeric($params[$k])){
				$this->setField($k, $params[$k]);
			}else{
				$this->setField($k, 0);
			}
		}

		$this->setField('ip', \Sooh\Base\Tools::remoteIP());
		$this->setField('ymd',$dt->YmdFull);
		$this->setField('hhiiss', $dt->his);//字符串）userid, userIdentifier
	}
	/**
	 * 创建每天的日志表
	 */
	protected function createTable()
	{
		$this->db()->ensureObj($this->tbname(), array(
			'guid'=>'varchar(64) not null',
			'userid'=>'varchar(64) not null',
			'islogined'=>'smallint not null default 0',
			'opcount'=>'int not null default 0',
			'clienttype'=>'int not null default 0',
			'contractid'=>'bigint not null default 0',
			'evt'=>'varchar(64)',
			'maintype'=>'varchar(64)',
			'subtype'=>'varchar(64)',
			'target'=>'varchar(128)',
			'num'=>'int not null default 0',
			'ext'=>'varchar(64)',
			'ret'=>'varchar(128)',
			'narg1'=>'int not null default 0',
			'narg2'=>'int not null default 0',
			'narg3'=>'int not null default 0',
			'sarg1'=>'varchar(2000)',
			'sarg2'=>'varchar(128)',
			'sarg3'=>'varchar(128)',

			'ip'=>'varchar(32)',
			'ymd'=>'int not null default 0',
			'hhiiss'=>'int not null default 0',
			'iRecordVerID'=>'int not null default 0'
		));
	}
}