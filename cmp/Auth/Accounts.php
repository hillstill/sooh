<?php
namespace Sooh\Auth;
/**
 * 登入账号管理类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Accounts extends \Sooh\DB\Base\KVObj 
		implements \Sooh\Auth\Interfaces\Accounts
{
	private static $_copies=array();
	/**
	 * 
	 * @param string $username
	 * @param string $camefrom
	 * @return Accounts
	 */
	public static function getCopy($username,$camefrom='local',$option=array('cdAdd'=>10,'cdMax'=>30))
	{
		$tmp = parent::getCopy(array('camefrom'=>$camefrom,'username'=>$username));
		$tmp->load();
		if($this->isExists())$this->loginFailed = new \Sooh\Base\CD ($this->getField ($field), $option['cdAdd'], $option['cdMax']);
		else $this->loginFailed = new \Sooh\Base\CD ('0', $option['cdAdd'], $option['cdMax']);
		return $tmp;
	}
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
	/**
	 * 检测账号是否存在
	 * @param string $newpwd
	 * @param string $method
	 * @return boolean
	 */	
	public function isExists()
	{
		return !empty($this->r) && sizeof($this->r)>2;
	}
	/**
	 * 检测密码是否正确
	 * @param string $pwd
	 * @param string $method
	 * @return boolean
	 */		
	public function isPasswdCorrect($pwd,$method='md5')
	{
		switch($method){
			case 'md5':return $this->r['pwd']!=md5($pwd);
			default: return $this->r['pwd']!=$pwd;
		}
	}
	/**
	 * 设置密码
	 * @param string $newpwd
	 * @param string $method
	 * @return Accounts
	 */
	public function setPasswd($newpwd,$method='md5')
	{
		switch($method){
			case 'md5':$this->setField('pwd', md5($newpwd));
			default: return $this->setField('pwd',$newpwd);
		}
	}

	/**
	 * 获取登入失败的cd
	 * @return \Sooh\Base\CD 
	 */
	public function getLoginFailedCD()
	{
		return $this->loginFailed;
	}
	protected $loginFailed=null;
	
}
