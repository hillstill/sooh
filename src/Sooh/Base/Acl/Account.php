<?php
namespace Sooh\Base\Acl;

/**
 * Account Service with login() and register()
 * 默认 storage: \Sooh\DB\Cases\AccountStorage(default库的2张tb_accounts_?表)
 * 重写 setAccountStorage()替换默认的storage
 * 必要字段：
  `camefrom` varchar(36) NOT NULL,
  `loginname` varchar(16) NOT NULL,
  `passwd` varchar(32) DEFAULT NULL,
  `passwd_salt` varchar(4) DEFAULT NULL,
  `accountId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `regYmd` int(11) NOT NULL DEFAULT '0',
  `regHHiiss` int(11) NOT NULL DEFAULT '0',
  `regClient` tinyint(4) NOT NULL DEFAULT '0',
  `regIP` varchar(16) NOT NULL DEFAULT '',
  `dtForbidden` int(11) NOT NULL DEFAULT '0' COMMENT '状态（0表示正常）',
  `loginFailed` bigint(36) unsigned NOT NULL DEFAULT '0' COMMENT '密码错误后的CD',
  `nickname` varchar(36) DEFAULT NULL,
  `lastIP` varchar(16) NOT NULL DEFAULT '' COMMENT '最后访问IP',
  `lastDt` int(11) NOT NULL DEFAULT '0' COMMENT '最后访问时间',
  `iRecordVerID` int(20) unsigned DEFAULT '0',
  PRIMARY KEY (`camefrom`,`loginname`),
  UNIQUE KEY `accountId` (`accountId`)
 * 
 * 可自行增加一些字段，诸如contractId,rights
 *  
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Account {
	const errAccountOrPasswordError='account_or_password_error';
	const errAccountExists='account_exists';
	const errAccountLock='account_locked';
	const errRetryLater='server_busy_retry_later';
	const errPasswdLock='password_failed_too_many_times';
	protected static $_instance=null;
	/**
	 * 
	 * @param \Sooh\Base\Rpc\Base $rpcOnNew
	 * @return Account
	 */
	public static function getInstance($rpcOnNew=null)
	{
		if(self::$_instance===null){
			self::$_instance = new Account;
			self::$_instance->rpc = $rpcOnNew;
		}
		return self::$_instance;
	}
	/**
	 *
	 * @var \Sooh\Base\Rpc\Base 
	 */
	protected $rpc=null;
	/**
	 *
	 * @var \Prj\Data\Account;
	 */
	protected $account=null;
	protected function setAccountStorage($accountname, $camefrom)
	{
		\Sooh\DB\Cases\AccountStorage::$__nSplitedBy=2;
		\Sooh\DB\Cases\AccountStorage::$__id_in_dbByObj='default';
		$this->account = \Sooh\DB\Cases\AccountStorage::getCopy($accountname, $camefrom);
	}
	/**
	 * 账号登入, 失败抛出异常(密码错误，账号找不到等等)
	 * @param string $accountname
	 * @param string $password
	 * @param string $camefrom
	 * @return array(accountId=>xxxx, nickname=>xxxx)
	 * @throws ErrorException (on login failed)
	 */
	public function login($accountname,$password,$camefrom='local',$customArgs=array('contractId'))
	{
		if($this->rpc===null){
			$this->setAccountStorage($accountname, $camefrom);
			$this->account->load();
			if($this->account->exists()){
				$dt = \Sooh\Base\Time::getInstance();
				$cmp = md5($password.$this->account->getField('passwd_salt'));
				$loginFailed = $this->account->getField('loginFailed');
				if($loginFailed){
					$cd = new \Sooh\Base\CD($loginFailed, 750, 3600);
					if($cd->isRed()){
						throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError);
					}
				}else{
					$cd = new \Sooh\Base\CD(0, 750, 3600);
				}
				$ymdhForbidden = $this->account->getField('dtForbidden');
				if($ymdhForbidden){
					if($dt->timestamp() <= $ymdhForbidden){
						throw new \Sooh\Base\ErrException(self::errAccountLock,404);
					}
				}
				if($cmp!=$this->account->getField('passwd')){
					$cd->add(1);
//					$tmp = $cd->timesCount();
//					if($tmp>=3)	throw new \Sooh\Base\ErrException('密码错误（您已经输错'.$tmp.'次）',404);
//					else throw new \Sooh\Base\ErrException('密码错误',404);
					$ret = new \Sooh\Base\ErrException(self::errAccountOrPasswordError,404);
				}else{
					$nickname = $this->account->getField('nickname');
					if($nickname){
						$ret = array('accountId'=>  $this->account->getField('accountId'), 'nickname'=>$nickname,);
					}else{
						$ret = array('accountId'=>  $this->account->getField('accountId'), 'nickname'=>$this->account->getField('loginname'),);
					}
					if(!empty($customArgs)){
						if(is_string($customArgs)){
							$customArgs = explode(',', $customArgs);
						}
						foreach($customArgs as $k){
							$ret[$k] = $this->account->getField('contractId');
						}
					}
				}
				$this->account->setField('lastIP', \Sooh\Base\Tools::remoteIP());
				$this->account->setField('lastDt', $dt->timestamp());
				$this->account->setField('loginFailed',$cd->toString());
				try{
					$this->account->update();
				} catch (\ErrorException $ex) {
					\Sooh\Base\Log\Data::error("error on update account when login:".$e->getMessage()."\n".\Sooh\DB\Broker::lastCmd()."\n".$ex->getTraceAsString());
				}
				if(is_array($ret)){
					return $ret;
				}else{
					throw $ret;
				}

			}else{
				throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError,404);
			}
		}else{
			return $this->rpc->call('Account/'.__FUNCTION__, array($accountname,$password,$camefrom));
		}
	}
	/**
	 * 注册账号，失败抛出异常（账号已经存在等等）
	 * @param type $accountname
	 * @param type $password
	 * @param type $camefrom
	 * @param type $customArgs
	 * @return type
	 * @throws \Sooh\Base\ErrException
	 */
	public function register($accountname,$password,$camefrom='local',$customArgs=array())
	{
		if($this->rpc===null){
			$this->setAccountStorage($accountname, $camefrom);
			$this->account->load();
			if($this->account->exists()){
				throw new \Sooh\Base\ErrException(self::errAccountExists,400);
			}else{
				$this->account->setField('passwd_salt',substr(uniqid(),0,4));
				$cmp = md5($password.$this->account->getField('passwd_salt'));
				$this->account->setField('passwd',$cmp);
				$this->account->setField('camefrom',$camefrom);
				$this->account->setField('loginname',$accountname);
				$this->account->setField('nickname','');
				$customArgs['regClient']=$customArgs['clientType']-0+$customArgs['ClientType']+$customArgs['clienttype'];
				unset($customArgs['clientType'],$customArgs['ClientType'],$customArgs['clienttype']);
				$customArgs['regYmd']=\Sooh\Base\Time::getInstance()->YmdFull;
				$customArgs['regHHiiss']=\Sooh\Base\Time::getInstance()->his;
				foreach($customArgs as $k=>$v){
					$this->account->setField($k,$v);
				}
				$modid = $this->account->idForSplit;
				for($retry=0;$retry<10;$retry++){
					$tmpid = rand(1000000,9999999).rand(1000000,9999999).  sprintf("%04d",$modid);
					try{
						$this->account->setField('accountId',$tmpid);
						$this->account->update();
						return array_merge(array('accountId'=>  $tmpid, 'nickname'=>$accountname),$customArgs['contractId']);
					}catch(\Exception $e){
						error_log($e->getMessage()."[try accountId:$tmpid]");
					}
				}
				throw new \Sooh\Base\ErrException(self::errRetryLater,400);
			}
		}else{
			return $this->rpc->call('Account/'.__FUNCTION__, array($accountname,$password,$camefrom));
		}
	}

}
