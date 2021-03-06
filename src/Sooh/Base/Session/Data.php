<?php
namespace Sooh\Base\Session;
/**
 * Session， 使用方式：
 * 构建一个project的storage类
 *     class \Lib\SessionStorage extends \Sooh\Base\Session\Storage{}
 * 
 * framework初始化(ctrl->action之前)
 *     不通过RPC读写
 *         \Lib\SessionStorage::setStorageIni('session', 2);
 *         \Sooh\Base\Session\Data::getInstance( \Lib\SessionStorage::getInstance(null));
 *     通过RPC读写
 *         $rpc = new \Sooh\Base\Rpc\Http($this->ini->get('SignKeyForService'), $this->ini->get('hostsOfMssqlAPI.default'));
 *         \Sooh\Base\Session\Data::getInstance( \Lib\SessionStorage::getInstance($rpc));
 * 
 * ctrl的action or lib 中
 *     \Sooh\Base\Session\Data::getInstance()->get(key, defaultVal);
 *     \Sooh\Base\Session\Data::getInstance()->set(key, value[, secondExpire]);
 * 
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Data {
	const SessionIdName = 'SoohSessId';
	protected static $_instance=null;
	/**
	 * @param \Sooh\Base\Session\Storage $storage
	 * @return \Sooh\Base\Session\Data
	 */
	public static function getInstance($storage=null)
	{
		if(self::$_instance===null){
			static::$_instance = new \Sooh\Base\Session\Data;
			static::$_instance->sessionId = self::getSessId();
			if($storage===null){
				throw new \Sooh\Base\ErrException('Session_data created on storage=null');
			}
			static::$_instance->storage = $storage;
		}
		return static::$_instance;
	}
	
	/**
	 * get session id
	 * @return string
	 */
	public static function getSessId()
	{
		if(empty($_COOKIE[self::SessionIdName])){
			$_COOKIE[self::SessionIdName] = md5(microtime(true).\Sooh\Base\Tools::remoteIP());
			$cookieDomain=  \Sooh\Base\Ini::getInstance()->cookieDomain();
			
			setcookie(self::SessionIdName, $_COOKIE[self::SessionIdName], time()+315360000, '/', $cookieDomain);
		}
		return $_COOKIE[self::SessionIdName];
	}

	protected $timestamp;
	protected $sessionId;
	protected $sessionArr=null;
	/**
	 *
	 * @var \Sooh\Base\Session\Storage  
	 */
	protected $storage=null;
	/**
	 * start session if session not init
	 */
	protected function start()
	{
		if($this->sessionArr===null){
			$this->timestamp = \Sooh\Base\Time::getInstance()->timestamp(); 
			$tmp = $this->storage->load($this->sessionId);
			if(empty($tmp) || empty($tmp['trans'])){
				$this->record=array('sessionId'=>$this->sessionId,);
				$this->sessionArr=array();
				//error_log("[TRACE-session ".$_COOKIE['SoohSessId']." data] skip init");
			}else{
				$this->record = $tmp['trans'];
				$this->sessionArr = $tmp['data'];
				if($this->get('accountId')){
					$secLast = $this->sessionArr['__dTaCcOuNt__']-0;
					$secPast = $this->timestamp-$secLast;
					if($secPast>300){
						$this->sessionArr['__eXpIrE__']['accountId']+=min([$secPast,900]);
						$this->sessionArr['__dTaCcOuNt__']=$this->timestamp;
						$this->changed=true;
						//error_log("[TRACE-session ".$_COOKIE['SoohSessId']." data] update expire as secPast=$secPast");
					}else{
						//error_log("[TRACE-session ".$_COOKIE['SoohSessId']." data] skip secPast=$secPast");
					}
				}else{
					//error_log("[TRACE-session ".$_COOKIE['SoohSessId']." data] skip not login");
				}
			}
			
			\Sooh\Base\Ini::registerShutdown(array($this,'shutdown'), 'sessionOnShutdown');
		}
	}
	/**
	 * session是否已经激活
	 * @return bool
	 */
	public function isStarted()
	{
		return !empty($this->sessionArr);
	}
	protected $record=null;
	/**
	 * get session value
	 * @param string $k
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($k,$default=null)
	{
		$this->start();
		if(isset($this->sessionArr['__eXpIrE__'][$k])){
			if($this->sessionArr['__eXpIrE__'][$k]>=$this->timestamp){
				return $this->sessionArr[$k];
			}else{
				return $default;
			}
		}else{
			if(isset($this->sessionArr[$k])){
				return $this->sessionArr[$k];
			}else{
				return $default;
			}
		}
	}
	protected $changed=false;
	/**
	 * set session value with expired-seconds (0 means never expire)
	 * @param string $k
	 * @param mixed $v
	 * @param int $expireAfter
	 */
	public function set($k,$v,$expireAfter=0)
	{
		$this->start();
		$this->changed=true;
		if($v===null){
			unset($this->sessionArr[$k],$this->sessionArr['__eXpIrE__'][$k]);
		}else{
			$this->sessionArr[$k] = $v;
			if($expireAfter){
				$this->sessionArr['__eXpIrE__'][$k]=$expireAfter+$this->timestamp;
			}else{
				unset($this->sessionArr['__eXpIrE__'][$k]);
			}
		}
	}
	/**
	 * 获取指定key的剩余有效时长（单位秒）
	 * @param type $k
	 * @return type
	 * @throws \ErrorException
	 */
	protected function expireLeft($k)
	{
		if(!isset($this->sessionArr['__eXpIrE__'][$k])){
			throw new \ErrorException('expire for '.$k.' is NOT set');
		}else{
			return $this->sessionArr['__eXpIrE__'][$k]-$this->timestamp;
		}
	}
	/**
	 * update session when shutdown
	 */
	public function shutdown()
	{
		if($this->changed ){
			$this->storage->update($this->sessionId, $this->sessionArr,$this->record);
			$this->changed=false;
		}
	}
}
