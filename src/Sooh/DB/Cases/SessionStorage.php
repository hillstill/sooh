<?php
namespace Sooh\DB\Cases;

/**
 * usage:
 *		conf.php
 *			$GLOBALS['CONF']['dbConf']['ram']=array(.... 参看KVObj说明);
 *			$GLOBALS['CONF']['dbByObj']['Sooh\KVObj\SessionStorage']='ram';
 *		index.php
 *			\Sooh\KVObj\SessionStorage::initInstance(超时秒数,登出回调函数包括超时(){error_log("[session-logout]".$r['sID']);});
 *			......
 *			session_start()
 *			......
 * 
 * 调试此类:
 *		sooh_trace::focusClass('Sooh\DB\Broker');
 *		sooh_trace::focusClass('Sooh\DB\Cases\SessionStorage');
 *		需要在Index.php中加入session_write_close(),提前执行相关操作
 * @author Simon Wang <sooh_simon@163.com> 
 */

class SessionStorage extends \Sooh\DB\Base\KVObj
{
	public static $flgNeedSave=true;

//	public static function initInstance($secondTimeout=600,$funcLogout=null)
//	{
//		self::$timeout = $secondTimeout;
//		self::$funcOnLogout = $funcLogout;
//		self::$dtNow = sooh_time::getInstance()->timestamp();
//		return parent::getCopy(array('sID'=>0));
//	}
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'Session';
	}
	protected static $_instance=null;
	protected static $_config=null;
	/**
	 * @return SessionStorage
	 */	
	public static function getInstance($sessionId,$arrConfig=null)
	{
		if(self::$_instance===null){
			if(!empty($arrConfig)){
				self::$_config = $arrConfig;
			}
			self::$_instance=self::getCopy(array('sessionId'=>$sessionId));
			self::$_instance->load();
		}
		return self::$_instance;
	}
	public function getSessionData()
	{
		return $this->getField('sessionData',true);
	}
	public function setSessionData($strValue,$dtExpired)
	{
		$this->setField('sessionData', $strValue);
		$this->setField('sessionExpired', $dtExpired);
	}
	public function tellMeUser($userid,$ip,$camefrom=null)
	{
		$this->setField('user', $userid);
		$this->setField('ip', $ip);
		$this->setField('camefrom', $camefrom);
	}
	public static function hookSessionHandle($arrConfig){
		session_module_name('user');
		session_set_save_handler(
			__CLASS__.'::sess_open',
			__CLASS__.'::sess_close',
			__CLASS__.'::sess_read',
			__CLASS__.'::sess_write',
			__CLASS__.'::sess_destroy',
			array($this, 'sess_gc')
		);
		self::$_config = $arrConfig;
	}

	protected static $timeout=0;
	protected static $dtNow=0;
	protected $md5=null;
	
	protected static function splitedTbName($n,$isCache)
	{
		return self::$_config['tb'].($n%self::numToSplit());
	}
//	protected function setSessionFields($str)
//	{
//		$this->setField('sVal', $str);
//		$this->setField('sUGRP', 'userGrp');
//		$this->setField('sUID', 'uid');
//	}
	
	public static function sess_open($save_path, $session_name){return true;}
	public static function sess_close() {return true;}
	public function sess_read($SessionKey){
		$tmp = self::getInstance($SessionKey);
		return $tmp->getSessionData();
	}
	public function sess_write($SessionKey, $VArray) {
		$tmp = self::getInstance($SessionKey);
		$tmp->setSessionData($VArray, $dtExpired);
		try{
			$tmp->update();
		}  catch (\ErrorException $e){
			error_log('write session failed:'.$e->getMessage()."\n".$e->getTraceAsString());
		}
		return true;
	}
	public static function sess_destroy($SessionKey) {
		$tmp = self::getInstance($SessionKey);
		$tmp->delete(true);
		return true;
	}
 	/**
	 * 
	 * @param \Sooh\DB\Interfaces\All $maxlifetime
	 * @param type $tbnameForLoop
	 * @return boolean
	 */
	public static function sess_gc($maxlifetime,$tbnameForLoop=null) {
		if($tbnameForLoop===null){
			self::loop(__CLASS__.'::sess_gc');
		}else{
			$maxlifetime->delRecords($tbnameForLoop,array('sessionExpired<'=> \Sooh\Base\Time::getInstance()->timestamp()));
		}
		return true;
	}	
 	/**
	 * 
	 * @param \Sooh\DB\Interfaces\All $db
	 * @param type $tbname
	 * @return boolean
	 */
	public static function createTable($db=null,$tbname=null)
	{
		if($db===null){
			self::loop(__CLASS__.'::createTable');
		}else{
			$db->ensureObj($tbname,array(
					'sessionId'=>"varchar(64) not null default ''",
					'sessionData'=>"varchar(4000) not null default ''",
					'sessionExpired'=>"bigint not null default 0",
					'iRecordVerID'=>"bigint not null default 0",
					'user'=>"varchar(36) not null default ''",
					'ip'=>"varchar(32) not null default ''",
					'camefrom'=>"varchar(32) not null default ''",
				),array('sessionId'));
			error_log(\Sooh\DB\Broker::lastCmd());
		}
	}

	public function freeOnShutdown()
	{
		
	}
	protected static $funcOnLogout;
}
