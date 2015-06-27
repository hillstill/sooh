<?php
namespace Sooh\Base;
/**
 * session 类，替换默认的session机制
 * 
 * $GLOBALS['CONF']['dbConf']=array(
	'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'Aa111111','type'=>'mysql','port'=>'3306',
					'dbEnums'=>array('default'=>'db_rpt','TestObj'=>'test','Session'=>'db_ram')),
 * 
 * $GLOBALS['CONF']['SESSION']=array(
			'mode'=>'Sooh',
			'identifier'=>array('func'=>'\\Sooh\\Base\\Sess::idByCookie','args'=>array('cookieId'=>'PHPSESSID','cookieDomain'=>'127.0.0.1')),
			'Storage'=>array('class'=>'\\Sooh\\DB\\Cases\\SessionStorage','args'=>array('db'=>'db_ram','tb'=>'tb_ramSession_')),
			'keepOnline'=>600,
 * )
 *
 *  $sess = \Sooh\Base\Sess::getInstance();
	$sess->get('notExists','defaultV');
	$sess->login('u001', 'qq.com');
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Sess {
	private static $_instance=null;
	/**
	 * 
	 * @return \Sooh\Base\Sess
	 */
	public static function getInstance()
	{
		if(self::$_instance===null){
			self::$_instance = new Sess;
		}
		return self::$_instance;
	}
	
	protected $ini;
	public function __construct() {
		$this->ini = \Sooh\Base\Ini::getInstance()->get('SESSION');
		if(empty($this->ini)){
			$this->ini=array();
		}
		if(empty($this->ini['identifier'])){
			$tmp = str_replace('.', '', $_SERVER['SERVER_NAME']);
			if(is_numeric($tmp)){
				$tmp= $_SERVER['SERVER_NAME'];
			}else{
				$tmp = explode('.',  $_SERVER['SERVER_NAME']);
				$tmp[0]='';
				$tmp = implode('.', $tmp);
			}
			$this->ini['identifier'] = array('func'=>'\\Sooh\\Base\\Sess::idByCookie','args'=>array('cookieId'=>'PHPSESSID','cookieDomain'=>$tmp));
		}
		if(empty($this->ini['Storage'])){
			$this->ini['Storage'] = array('class'=>'\\Sooh\\DB\\Cases\\SessionStorage','args'=>array('db'=>'db_ram','tb'=>'tb_ramSession_'));
		}
		if(empty($this->ini['keepOnline'])){
			$this->keepOnline = 1800;
		}else{
			$this->keepOnline = 1800+$this->ini['keepOnline'];
		}
	}
	
	public static function idByCookie($newVal,$cookieId,$cookieDomain)
	{
		if(empty($newVal)){
			return $_COOKIE[$cookieId];
		}else{
			setcookie($cookieId, $newVal, time()+86400*30, null, $cookieDomain);
		}
	}
	
	protected function load()
	{
		if($this->r!==null){
			return;
		}
		if(false===$this->id){
			if(!empty($this->ini['identifier']['func'])){
				$r = $this->ini['identifier']['args'];
				array_unshift($r, '');
				$this->id=call_user_func_array($this->ini['identifier']['func'], $r);
			}
		}
		
		if($this->ini['mode']=='Sooh'){
			$r = $this->ini['Storage']['class'];
			if(!empty($r)){
				$this->storage = $r::getInstance($this->id,$this->ini['Storage']['args']);
				$this->r = $this->decode($this->storage->getSessionData());
			}else{
				throw new \ErrorException('storage not set for session(mode:Sooh)');
			}
		}else{
			$r = $this->ini['Storage']['class'];
			$r::hookSessionHandle($this->ini['Storage']['args']);
			session_start();
			$this->r = $_SESSION;
		}
		\Sooh\Base\Ini::registerShutdown(array($this,'shutdown'), 'sessionOnShutdown');
	}
	protected $id=false;
	/**
	 *
	 * @var \Sooh\Base\Interfaces\SessionStorage
	 */
	protected $storage;
	protected $r=null;
	public function get($k,$default=null)
	{
		$this->load();
		//var_log($this->r, "[GET]". \Sooh\DB\Broker::lastCmd());
		if(isset($this->r[$k])){
			return $this->r[$k];
		}else{
			return $default;
		}
	}
	
	public function set($k,$v)
	{
		$this->r[$k]=$v;
	}
	
	public function login($userid, $camefrom)
	{
		$this->r['userId']=$userid;
		$this->r['uCameFrom']=$camefrom;
	}
	
	public function logout()
	{
		$this->r['userId']='';
		$this->r['uCameFrom']='';
	}
	
	protected function decode($data)
	{
		if(is_array($data)){
			return $data;
		}else{
			return json_decode($data,true);
		}
	}
	protected $keepOnline=0;
	
	protected function encode()
	{
		return json_encode($this->r);
	}
	
	public function shutdown()
	{
		$this->storage->setSessionData($this->encode(),time()+$this->keepOnline);
		$this->storage->tellMeUser($this->r['userId'], \Sooh\Base\Tools::remoteIP(),$this->r['uCameFrom']);
		$this->storage->update();
		$this->storage->freeOnShutdown();
		$this->storage=null;
	}
}
