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
			'一级菜单a.二级菜单1'=>array('manage','iosnatureworth', \Sooh\Base\Tools::uri(array('__ONLY__'=>'body'), 'iosrpt', 'iosnatureworth', 'manage'),array()),
			'一级菜单a.二级菜单2'=>array('manage','iosnatureworth', \Sooh\Base\Tools::uri(array('__ONLY__'=>'body'), 'iosrpt', 'iosnatureworth', 'manage'),array()),
			'一级菜单b.二级菜单1'=>array('manage','iosnatureworth', \Sooh\Base\Tools::uri(array('__ONLY__'=>'body'), 'iosrpt', 'iosnatureworth', 'manage'),array()),
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
			self::$_instance = new Ctrl;
			self::$_instance->allMenu = self::$_instance->initMenu();
		}
		return self::$_instance;
	}
	protected $allMenu;
	/**
	 * 获取全部菜单或本人可以访问的菜单
	 * @param type $hasRights
	 * @return \Sooh\DB\Acl\Menu
	 */
	public function getMenu($hasRights=true)
	{
		$root = \Sooh\DB\Acl\Menu::factory('root');
		$lastMenu=null;
		if($hasRights){
			foreach($this->allMenu as $menu=>$r){
				list($mainMenu,$subMenu)=explode('.',$menu);
				$mod = $r[0];
				$ctrl = $r[1];
				$url = $r[2];
				$options=$r[3];
				if($this->hasRightsTo($mod, $ctrl)){
					if($lastMenu===null){
						$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
					}elseif($lastMenu->capt!==$mainMenu){
						$root->addChild($lastMenu);
						$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
					}
					$lastMenu->addChild($subMenu, $url, $options);
				}
			}
		}else{
			foreach($this->allMenu as $menu=>$r){
				list($mainMenu,$subMenu)=explode('.',$menu);
				$mod = $r[0];
				$ctrl = $r[1];
				$url = $r[2];
				$options=$r[3];
				if($lastMenu===null){
					$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
				}elseif($lastMenu->capt!==$mainMenu){
					$root->addChild($lastMenu);
					$lastMenu = \Sooh\Base\Acl\Menu::factory($mainMenu);
				}
				$lastMenu->addChild($subMenu, $url, $options);
			}
		}
		return $root;
	}
	protected $rights;
	public function fromString($str)
	{
		$v = explode(',',$str);
		$tmp = array_fill(0, sizeof($v), 1);
		$this->rights = array_combine($v, $tmp);
	}
	
	public function toString()
	{
		return implode(',', array_keys($this->rights));
	}
	public function hasRightsFor($module,$ctrl)
	{
		return isset($this->rights["$module.$ctrl"]);
	}
}
