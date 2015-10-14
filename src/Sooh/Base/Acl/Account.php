<?php
namespace Sooh\Base\Acl;

/**
 * Account Service with login() and register()
 * 默认 storage: \Sooh\DB\Cases\AccountStorage(default库的2张tb_accounts_?表)
 * 重写 setAccountStorage()替换默认的storage
 * 必要字段：
`cameFrom` varchar(36) NOT NULL,
`loginname` varchar(16) NOT NULL,
`passwd` varchar(32) DEFAULT NULL,
`passwdSalt` varchar(4) DEFAULT NULL,
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
PRIMARY KEY (`cameFrom`,`loginname`),
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
	const errAccountEmpty = 'account_emtyp';
	const errRetryLater='server_busy_retry_later';
	const errPasswdLock='password_failed_too_many_times';
	const errResetPasswdFailed='reset_password_failed';
	protected static $_instance=null;
	/**
	 *
	 * @param \Sooh\Base\Rpc\Base $rpcOnNew
	 * @return Account
	 */
	public static function getInstance($rpcOnNew=null)
	{
		if(self::$_instance===null){
			$cc = get_called_class();
			self::$_instance = new $cc;
			self::$_instance->rpc = $rpcOnNew;
		}
		return self::$_instance;
	}
	/**
	 *
	 * @var \Sooh\Base\Rpc\Broker
	 */
	protected $rpc=null;
	/**
	 * switch to the tb_account_X table(X 用于分库分表)
	 * @var \Sooh\DB\Cases\AccountStorage;
	 */
	protected $account=null;
	protected function setAccountStorage($accountId)
	{
		$this->account = \Sooh\DB\Cases\AccountStorage::getCopy($accountId);
	}
	/**
	 * 获取符合条件的账号的数量
	 * @param array $where
	 * @return int
	 */
	public function getAccountNum($where)
	{
		if ($this->rpc!==null) {
			return $this->rpc->initArgs(array('where'=>$where,))->send(__FUNCTION__);
		} else {
			$this->setAccountStorage('', 'local');
			return \Sooh\DB\Cases\AccountStorage::loopGetRecordsCount($where);
		}
	}

	/**
	 * 设置登入名--注册前准备(锁定)
	 * @param string $accountId
	 * @param string $loginName
	 * @param string $cameFrom
	 * @return boolean true:一切正常；false:已存在
	 * @throws Exception on update-db failed
	 */
	public function loginPrepare($accountId, $loginName, $cameFrom)
	{
		$objLogin = \Sooh\DB\Cases\AccountAlias::getCopy($loginName, $cameFrom);//switch to the tb_loginname_alias_X table(x 是 2的余数)
		$objLogin->load();
		if ($objLogin->exists()) {
			return false;
		} else {
			$objLogin->setField('accountId', $accountId);
			$objLogin->setField('loginName', $loginName);
			$objLogin->setField('cameFrom', $cameFrom);
			$objLogin->setField('flgStatus', 0);
			$objLogin->update();
			return true;
		}
	}

	/**
	 * 设置登入别名--rollback
	 * @param array $arrLoginName
	 * @param integer $key
	 */
	public function loginRollback($arrLoginName, $key) {
		foreach($arrLoginName as $_key => $_val) {
			if($_key <= $key) {
				$alias = \Sooh\DB\Cases\AccountAlias::getCopy($_val[0], $_val[1]);
				$alias->load();
				if ($alias->getField('flgStatus') == 0) {
					$alias->delete(true);
				}
			}
		}
	}

	/**
	 * 设置登入别名--commit
	 * @param string $aliasName
	 * @return boolean
	 * @throws Exception on update-db failed*
	 */
	protected function loginCommit($arrLoginName)
	{
		foreach($arrLoginName as $_val) {
			$alias = \Sooh\DB\Cases\AccountAlias::getCopy($_val[0], $_val[1]);
			$alias->load();
			$alias->setField('flgStatus', 1);
			$alias->update();
		}
	}

	/**
	 * 重置密码
	 * @param $accountId
	 * @param $newPwd
	 * @param $oldPwd
	 * @return mixed
	 * @throws \ErrorException
	 * @throws \Sooh\Base\ErrException
	 */
	public function resetPwd($accountId, $newPwd, $oldPwd) {
		if ($this->rpc !== null) {
			return $this->rpc->initArgs(['accountId' => $accountId, 'newPwd' => $newPwd, 'oldPwd' => $oldPwd])->send(__FUNCTION__);
		} else {
			$this->setAccountStorage($accountId);
			$this->account->load();

			$cmp = md5($oldPwd . $this->account->getField('passwdSalt'));
			if ($cmp == $this->account->getField('passwd')) {
				$passwdSalt = substr(uniqid(), -4);
				$newCmp = md5($newPwd . $passwdSalt);
				$this->account->setField('passwdSalt', $passwdSalt);
				$this->account->setField('passwd', $newCmp);
				try {
					$this->account->update();
				} catch (\Exception $ex) {
					error_log($ex->getMessage()."\n".$ex->getTraceAsString());
					throw new \Sooh\Base\ErrException(self::errResetPasswdFailed, 400);
				}
			} else {
				throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError, 400);
			}

		}
	}

	/**
	 * 获取指定用户需要的字段（指定用户的方式：[aliasName:xxx] or [accountId:xxxx] or [loginName:xxxx,cameFrom:local]）
	 * @param array $fields
	 * @param array $where [aliasName:xxx] or [accountId:xxxx] or [loginName:xxxx,cameFrom:local]
	 * @param \Sooh\DB\Interfaces\All $_ignore_
	 * @return Account
	 */
	public function getFieldsBy($fields,$where,$_ignore_=null)
	{
		if($this->rpc!==null){
			return $this->rpc->initArgs(array('fields'=>$fields,'where'=>$where,))->send(__FUNCTION__);
		}else{
			$where = $this->getAccountWhereFinal($where);
			$this->setAccountStorage($where['loginName'], $where['cameFrom']);

			$this->account->load();
			$ret = array();
			if(is_string($fields)){
				$fields = explode(',', $fields);
			}
			foreach($fields as $k){
				$ret[$k] = $this->account->getField($k);
			}
			return $ret;
		}
		return parent::getCopy();
	}
	/**
	 * 获取最终的where：[aliasName:xxx] or [accountId:xxxx] => [loginName:xxxx,cameFrom:local]
	 * @param type $where
	 * @return type
	 * @throws \Sooh\Base\ErrException
	 */
	protected function getAccountWhereFinal($where)
	{
		if(isset($where['aliasName'])){
			$alias = \Sooh\DB\Cases\AccountAlias::getCopy($where['aliasName']);
			$alias->load();
			if($alias->exists()){
				unset($where['aliasName']);
				$where['loginName'] = $alias->getField('loginName');
				$where['cameFrom'] = $alias->getField('cameFrom');
			}else{
				throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError);
			}

		}elseif(isset($where['accountId'])){
			$this->setAccountStorage($where['accountId'], 'local');
			//TODO
			$this->__accountId = $where['accountId'];
			$classname = get_class($this->account);
			$classname::loop(array($this,'__getPkeyByAccountId'));

			if(is_array($this->__accountId)){
				$where['loginName'] = $this->__accountId['loginName'];
				$where['cameFrom'] = $this->__accountId['cameFrom'];
			}else{
				throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError);
			}
		}
		return $where;
	}

	/**
	 * 设定字段（指定用户的方式：[aliasName:xxx] or [accountId:xxxx] or [loginName:xxxx,cameFrom:local]）
	 * @param array $fields
	 * @param array $where [aliasName:xxx] or [accountId:xxxx] or [loginName:xxxx,cameFrom:local]
	 * @param string  $aliasField 可选的登入名
	 * @return boolean
	 */
	public function setFieldsBy($fields,$where,$aliasField=null)
	{
		if($this->rpc!==null){
			return $this->rpc->initArgs(array('fields'=>$fields,'where'=>$where,))->send(__FUNCTION__);
		}else{
			$where = $this->getAccountWhereFinal($where);
			$this->setAccountStorage($where['loginName'], $where['cameFrom']);

			$this->account->load();
			if($aliasField!==null){
				$oldAlias = $this->account->getField($aliasField);
			}
			if($aliasField!==null){
				$alias = \Sooh\DB\Cases\AccountAlias::getCopy($fields[$aliasField]);
				$alias->load();
				if($alias->exists() && $alias->getField('flgStatus')==1){
					throw new \Sooh\Base\ErrException(self::errAccountExists);
				}
				$alias->setField('loginName', $this->account->getField('loginName'));
				$alias->setField('cameFrom', $this->account->getField('cameFrom'));
				$alias->setField('flgStatus', 1);
				$alias->update();

				if($oldAlias && $oldAlias!=$fields[$aliasField]){
					$alias = \Sooh\DB\Cases\AccountAlias::getCopy($oldAlias);
					$alias->load();
					$alias->setField('cameFrom', '__removed__');
					$alias->setField('flgStatus', 0);
					$alias->update();
				}
			}
			foreach($fields as $k=>$v){
				$this->account->setField($k,$v);
			}
			$this->account->update();
			return true;
		}
	}
	private $__accountId;
	/**
	 *
	 * @param \Sooh\DB\Interfaces\All $db
	 * @param string $tb
	 */
	public function __getPkeyByAccountId($db,$tb)
	{
		if(!is_array($this->__accountId)){
			$r = $db->getRecord($tb, 'loginName,cameFrom',array('accountId'=>$this->__accountId));
			if(!empty($r)){
				$this->__accountId = $r;
			}
		}
	}

	/**
	 * 账号登入, 失败抛出异常(密码错误，账号找不到等等)
	 * @param $loginName
	 * @param $cameFrom
	 * @param $password
	 * @param array $customArgs
	 * @return mixed
	 * @throws \ErrorException
	 * @throws \Sooh\Base\ErrException
	 * @throws array
	 */
	public function login($loginName, $cameFrom, $password, $customArgs = ['contractId']) {
		if ($this->rpc !== null) {
			return $this->rpc->initArgs(['loginName' => $loginName, 'cameFrom' => $cameFrom, 'password' => $password, 'customArgs' => $customArgs])->send(__FUNCTION__);
		} else {
			$objLogin = \Sooh\DB\Cases\AccountAlias::getCopy($loginName, $cameFrom);
			$objLogin->load();
			if ($objLogin->exists()) {
				$accountId = $objLogin->getField('accountId');
				$this->setAccountStorage($accountId);
				$this->account->load();
				if($this->account->exists()) {
					$dt = \Sooh\Base\Time::getInstance();
					$cmp = md5($password . $this->account->getField('passwdSalt'));
					$loginFailed = $this->account->getField('loginFailed');
					if ($loginFailed) {
						$cd = new \Sooh\Base\CD($loginFailed, 750, 3600);
						if ($cd->isRed()) {
							throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError);
						}
					} else {
						$cd = new \Sooh\Base\CD(0, 750, 3600);
					}
					$ymdhForbidden = $this->account->getField('dtForbidden');
					if ($ymdhForbidden) {
						if ($dt->YmdH <= $ymdhForbidden) {
							throw new \Sooh\Base\ErrException(self::errAccountLock, 404);
						}
					}
					if ($cmp != $this->account->getField('passwd')) {
						$cd->add(1);
						$ret = new \Sooh\Base\ErrException(self::errAccountOrPasswordError, 404);
					} else {
						$nickname = $this->account->getField('nickname');
						$ret = array('accountId' => $this->account->getField('accountId'), 'nickname' => $nickname,);
						if (!empty($customArgs)) {
							if (is_string($customArgs)) {
								$customArgs = explode(',', $customArgs);
							}
							foreach ($customArgs as $k) {
								$ret[$k] = $this->account->getField('contractId');
							}
						}
					}
					$this->account->setField('lastIP', \Sooh\Base\Tools::remoteIP());
					$this->account->setField('lastDt', $dt->timestamp());
					$this->account->setField('loginFailed', $cd->toString());
					try {
						$this->account->update();
					} catch (\ErrorException $ex) {
						\Sooh\Base\Log\Data::error("error on update account when login:" . $ex->getMessage() . "\n" . \Sooh\DB\Broker::lastCmd() . "\n" . $ex->getTraceAsString());
					}
					if (is_array($ret)) {
						return $ret;
					} else {
						throw $ret;
					}
				} else {
					throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError, 400);
				}
			} else {
				throw new \Sooh\Base\ErrException(self::errAccountOrPasswordError, 400);
			}

		}
	}

	/**注册账号，失败抛出异常（账号已经存在等等）
	 * @param $password
	 * @param array $arrLoginName
	 * @param array $customArgs
	 * @return array|mixed
	 * @throws \ErrorException
	 * @throws \Exception
	 * @throws \Sooh\Base\ErrException
	 * @throws \Sooh\Base\ErrException
	 */
	public function register($password, $arrLoginName = [], $customArgs = [])
	{
		if ($this->rpc !== null) {
			return $this->rpc->initArgs(array('password'=>$password, 'arrLoginName'=>$arrLoginName, 'customArgs'=>$customArgs))->send(__FUNCTION__);
		} else {
			if($arrLoginName !== null){
				//生成AccountId
				for ($retry = 0; $retry < 10; $retry++) {
					try {
						$accountId = rand(10000, 99999) . sprintf('%05d', rand(0, 99999)) . sprintf('%04d', rand(0, 9999));
						$this->setAccountStorage($accountId);
						if ($this->account->exists()) {
							throw new \Exception('account exists for registe');
						} else {
							break;
						}
					} catch (\Exception $e) {
						error_log($e->getMessage() . "[try accountId:{$accountId}]");
					}
					if ($retry == 9) {
						throw new \Sooh\Base\ErrException(self::errRetryLater, 400);
					}
				}

				//检查loginName是否存在
				foreach($arrLoginName as $_key => $_val) {
					if ($this->loginPrepare($accountId, $_val[0], $_val[1]) === false) {
						$this->loginRollback($arrLoginName, $_key);
						throw new \Sooh\Base\ErrException(self::errAccountExists, 400);
					}
				}
			} else {
				throw new \Sooh\Base\ErrException(self::errAccountEmpty, 400);
			}

			$this->account->load();
			$passwdSalt = substr(uniqid(), -4);
			if (!empty($customArgs['nickname'])) {
				$nickname = $customArgs['nickname'];
			} else {
				$nickname = $accountId;
			}
			$this->account->setField('passwdSalt', $passwdSalt);
			$cmp = md5($password . $passwdSalt);
			$this->account->setField('passwd', $cmp);
			$this->account->setField('nickname', $nickname);
			$customArgs['regClient'] = $customArgs['clientType'] - 0;
			$customArgs['regYmd'] = \Sooh\Base\Time::getInstance()->YmdFull;
			$customArgs['regHHiiss'] = \Sooh\Base\Time::getInstance()->his;
			foreach ($customArgs as $k => $v) {
				$this->account->setField($k, $v);
			}

			$this->account->update();
			$tmp = array('accountId' => $accountId, 'nickname' => $nickname);
			foreach ($customArgs as $k => $v) {
				$tmp[$k] = $v;
			}
			$this->loginCommit($arrLoginName);
			return $tmp;
		}
	}
}
