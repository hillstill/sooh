<?php
namespace Sooh\Auth\Interfaces;
/**
 * 账号存取类接口定义
 * @author Simon Wang <hillstill_simon@163.com>
 */
interface Accounts {
	/**
	 * 获取账号存取类实例
	 * @param string $username
	 * @param string $camefrom default is 'local'
	 * @return Accounts 
	 */
	public static function getCopy($username,$camefrom='local');
	/**
	 * 设置账号密码
	 * @param string $newpwd
	 * @param string $method
	 * @return Accounts
	 */
	public function setPasswd($newpwd,$method='md5');
	/**
	 * 检测账号是否存在
	 * @param string $newpwd
	 * @param string $method
	 * @return boolean
	 */	
	public function isExists();
	/**
	 * 检测密码是否正确
	 * @param string $pwd
	 * @param string $method
	 * @return boolean
	 */		
	public function isPasswdCorrect($pwd,$method='md5');
	/**
	 * 获取登入失败的cd
	 * @return \Sooh\Base\CD 
	 */
	public function getLoginFailedCD();
	
	/**
	 * 获取某个字段的值
	 * @param string $field 字段名
	 * @param boolean $nullAccepted 当取得的值是null的时候，是否应该丢出异常
	 */
	public function getField($field,$nullAccepted=false);
	/**
	 * 设置某个字段的值
	 * @param string $field 字段名
	 * @param mixed $val  值
	 */
	public function setField($field,$val);
}
