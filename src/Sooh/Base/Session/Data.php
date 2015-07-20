<?php
namespace Sooh\Base\Session;
/**
 * Session, rpc请直接访问SessionStorage
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
		if(isset($_COOKIE[self::SessionIdName])){
			return $_COOKIE[self::SessionIdName];
		}else{
			$r = explode('.', $_SERVER['SERVER_NAME']);
			$_COOKIE[self::SessionIdName] = md5(microtime(true).\Sooh\Base\Tools::remoteIP());
			if(sizeof($r)==4 && is_numeric($r[0]) && is_numeric($r[1]) && is_numeric($r[2]) && is_numeric($r[3])){
				setcookie(self::SessionIdName, $_COOKIE[self::SessionIdName], time()+315360000, null, $_SERVER['SERVER_NAME']);
			}else{
				$r[0]='';
				setcookie(self::SessionIdName, $_COOKIE[self::SessionIdName], time()+315360000, null, implode('.',$r));
			}
		}
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
			$tmp = $this->storage->load($this->sessionId);
			if(empty($tmp)){
				$this->record=array('sessionId'=>$this->sessionId,);
				$this->sessionArr=array();
			}else{
				$this->record = $tmp['trans'];
				$this->sessionArr = $tmp['data'];
			}
			$this->timestamp = \Sooh\Base\Time::getInstance()->timestamp(); 
			\Sooh\Base\Ini::registerShutdown(array($this,'shutdown'), 'sessionOnShutdown');
			//error_log(__CLASS__.'->'.__FUNCTION__.':shutdown registered,'.\Sooh\DB\Broker::lastCmd().'and array='.  var_export($this->record,true));
		}
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
		$this->sessionArr[$k] = $v;
		if($expireAfter){
			$this->sessionArr['__eXpIrE__'][$k]=$expireAfter+$this->timestamp;
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
