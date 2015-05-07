<?php
namespace Sooh\KVObj\Cases;

use \Sooh\Base\Trace as sooh_trace;
use \Sooh\Base\Time as sooh_time;
use \Sooh\DB\Broker as sooh_broker;
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
	/**

	 * @return SessionStorage
	 */
	public static function initInstance($secondTimeout=600,$funcLogout=null)
	{
		self::$timeout = $secondTimeout;
		self::$funcOnLogout = $funcLogout;
		self::$dtNow = sooh_time::getInstance()->timestamp();
		return parent::getCopy(array('sID'=>0));
	}

	//without cache table, field _ver_id(int) defined in table as sequence lock
	protected function initConstruct()
	{
		session_module_name('user');
		session_set_save_handler(
			array($this, 'sess_open'),
			array($this, 'sess_close'),
			array($this, 'sess_read'),
			array($this, 'sess_write'),
			array($this, 'sess_destroy'),
			array($this, 'sess_gc')
		);
		return parent::initConstruct(0,'iVerID');
	}
	protected static $timeout=0;
	protected static $dtNow=0;
	protected $md5=null;
	
	protected static function splitedTbName($n,$isCache)
	{
		return 'ram_session';
	}
	protected function setSessionFields($str)
	{
		$this->setField('sVal', $str);
		$this->setField('sUGRP', 'userGrp');
		$this->setField('sUID', 'uid');
	}
	
	public function sess_open($save_path, $session_name){return true;}
	public function sess_close() {return true;}
	public function sess_read($SessionKey){
		if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('try load session:'.$SessionKey);
		$this->initPkey(array('sID'=>$SessionKey));
		$this->sess_gc_one ();
		//if(rand(1,1000)<10)$this->sess_gc_one ();
		$exists = $this->load();
		if($exists===null || $this->getField('iLastDt')+self::$timeout<self::$dtNow){
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('missing or expire session:'.$SessionKey.':'. json_encode($this->r));
			return false;
		}else {
			$s = $this->getField('sVal');
			$this->md5 = md5($s);
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::obj('find session:'.$SessionKey.':',$this->r);
			return $s;
		}
	}
	public function sess_write($SessionKey, $VArray) {
		//if(class_exists('\Sooh\Base\Trace',false)){
		if(class_exists(sooh_trace,false)){
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('try save session:'.$SessionKey.' with Pkey='.  json_encode($this->pkey).' flgNeedSave='.  var_export(self::$flgNeedSave,true));
		}
		if(empty($this->pkey))return false;
		if(self::$flgNeedSave==true){
			if($this->md5 != md5($VArray) || self::$dtNow>$this->getField('iLastDt', true)+60) {
				$this->setSessionFields($VArray);
				$this->setField('sIP', $_SERVER['REMOTE_ADDR']);
				if(!$this->getField('iLogin',true))	$this->setField('iLogin', self::$dtNow);
				$this->setField('iLastDt', self::$dtNow);
				try{
					$this->update();
				}catch(\ErrorException $e){
					sooh_trace::exception($e);
					return false;
				}
			}
		}
		return true;
	}
	function sess_destroy($SessionKey) {
		if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('try destory session:'.$SessionKey.' with Pkey='.  json_encode($this->pkey).' flgNeedSave='.  var_export(self::$flgNeedSave,true));
		$tb = null;
		$db = self::getDBAndTbName($tb, $this->pkey, false);
		try{
			if(!empty(self::$funcOnLogout)){
				if(is_array(self::$funcOnLogout) || is_string(self::$funcOnLogout))
					call_user_func($this->funcOnLogout, $this->r);
				else{
					$f = self::$funcOnLogout;
					$f($this->r);
				}
			}
			$db->kvoDelete($tb,$this->pkey);
			return true;
		}catch(\ErrorException $e){
			sooh_trace::exception($e);
			return false;
		}

	}
 
	protected function sess_gc_one()
	{
		$tb = null;
		$db = self::getDBAndTbName($tb, $this->pkey, false);
		$retry = 5;
		$ks = array_keys($this->pkey);
		while($retry>0){
			$retry--;
			$rs = $db->getRecords($tb, '*', array('iLastDt<'=>self::$dtNow-self::$timeout), null, 5);
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::obj('try  session-gc one by:'.sooh_broker::lastCmd(), $rs);
			if(empty($rs)) break;
			foreach( $rs as $r){
				if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('try  session-gc one >>>>>>>>>>>:'.$r['sID']);
				try{
					if(!empty(self::$funcOnLogout)){
						if(is_array(self::$funcOnLogout) || is_string(self::$funcOnLogout))
							call_user_func($this->funcOnLogout, $r);
						else{
							$f = self::$funcOnLogout;
							$f($r);
						}
					}
					$pkey = array();
					foreach ($ks as $k)$pkey[$k]=$r[$k];
					$db->kvoDelete($tb,$pkey);
					return true;
				}catch(\ErrorException $e){
					sooh_trace::exception($e);
					return false;
				}
			}
		}
	}
	
	function sess_gc($maxlifetime) {
		if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str('try session gc-all called:'. $maxlifetime);
		return true;
	}	


	protected static $funcOnLogout;
}
