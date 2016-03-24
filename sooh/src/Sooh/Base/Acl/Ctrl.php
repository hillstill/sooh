<?php
namespace Sooh\Base\Acl;
/**
 * AclCtrl
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Ctrl {
	protected function initMenu()
	{
		return array(
			'一级菜单a.二级菜单1'=>array('m','ctrl','act1', array(),array()),
			'一级菜单a.二级菜单2'=>array('m','ctrl', 'act2',array(),array()),
			'一级菜单b.二级菜单1'=>array('m','ctrl', 'act3',array(),array()),
		);
	}
	protected static $_instance=null;
	/**
	 * 
	 * @return Ctrl
	 */
	public static function getInstance()
	{
		if(self::$_instance===null){
			$cc = get_called_class();
			self::$_instance = new $cc;
			self::$_instance->allMenu = self::$_instance->initMenu();
			foreach(self::$_instance->allMenu as $k=>$r){
				if(is_array($r[3])){
					self::$_instance->allMenu[$k][3] = \Sooh\Base\Tools::uri($r[3],$r[2],$r[1],$r[0]);
				}
			}
		}
		return self::$_instance;
	}
	protected $allMenu;
	public function getMenuPath($act,$ctrl,$mod)
	{
		foreach($this->allMenu as $k=>$r){
			if(strtolower("$mod/$ctrl/$act")==  strtolower("{$r[0]}/{$r[1]}/{$r[2]}")){
				return $k;
			}
		}
		return null;
	}
	/**
	 * 获取本人可以访问的菜单
	 * @param type $chkRights
	 * @return \Sooh\DB\Acl\Menu
	 */
	public function getMenuMine()
	{
		$root = \Sooh\Base\Acl\Menu::factory('root');
		$lastMenu=null;

		foreach($this->allMenu as $menu=>$r){
			list($mainMenu,$subMenu)=explode('.',$menu);
			$mod = $r[0];
			$ctrl = $r[1];
			$act = $r[2];
			$url = $r[3];
			$options=$r[4];
			if($this->hasRightsFor($mod, $ctrl)){
				if($lastMenu===null){
					$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
				}elseif($lastMenu->capt!==$mainMenu){
					$root->addChild($lastMenu);
					$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
				}
				$options['MCA']="{$mod}_{$ctrl}_{$act}";
				$lastMenu->addChild($subMenu, $url, $options);
			}
		}
		if($lastMenu!==null){
			$root->addChild($lastMenu);
		}

		return $root;
	}
	/**
	 * 获取本人可以访问的菜单
	 * @param type $chkRights
	 * @return \Sooh\DB\Acl\Menu
	 */
	public function getMenuEnum()
	{
		$root = \Sooh\DB\Acl\Menu::factory('root');
		$lastMenu=null;

		foreach($this->allMenu as $menu=>$r){
			list($mainMenu,$subMenu)=explode('.',$menu);
			$mod = $r[0];
			$ctrl = $r[1];
			$act = $r[2];
			$url = $r[3];
			$options=$r[4];
			if($lastMenu===null){
				$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
			}elseif($lastMenu->capt!==$mainMenu){
				$root->addChild($lastMenu);
				$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
			}
			$options['_ModCtrl_'] = "$mod.$ctrl";
			$options['MCA']="{$mod}_{$ctrl}_{$act}";
			$lastMenu->addChild($subMenu, $url, $options);
		}
		if($lastMenu!==null){
			$root->addChild($lastMenu);
		}
		return $root;
	}
	
	protected $rights=array();
	public function fromString($str)
	{
		$ks = self::_fromString($str);
		foreach($ks as $k){
			$k = strtolower($k);
			$tmp = explode('.', $k);
			if(sizeof($tmp)==1){
				$this->rights[$k.'.*']=1;
			}else{
				$this->rights[$k]=1;
			}
		}
	}
	public static function _fromString($str)
	{
		return  explode(',',$str);
	}


	public function toString()
	{
		return implode(',', array_keys($this->rights));
	}
	public function hasRightsFor($module,$ctrl)
	{
		if(true===isset($this->rights[strtolower("$module.$ctrl")])){
			return true;
		}elseif(true===isset($this->rights[strtolower("$module.*")])){
			return true;
		}elseif(true===isset($this->rights[strtolower("*.*")])){
			return true;
		}else{
			return false;
		}
	}
}
