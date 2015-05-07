<?php
namespace \Sooh\Rpt\Crond;
/**
 * 计划任务控制类 
 * new  \Sooh\Rpt\Crond\Ctrl(APP_PATH.'/application/Crond', '')
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Ctrl {
	protected $dirBase;
	protected $namespaceBase;
	public function __construct($dirBase,$namespaceBase) {
		$this->dirBase = $dirBase;
	}
	public function hourly()
	{
		
	}
	public function run()
	{
		
	}
	/**
	 * 遍历crond目录，找出所有的任务
	 * @return array (classname => fullpath)
	 */
	public function getTasks()
	{
		
		$subdirs=array();
		$dh = opendir($this->dirBase);
		while(false!==($subdir=  readdir($dh))){
			if($subdir[0]!='.'){
				$subdirs[]=$path.'/'.$subdir;
			}
		}
		closedir($dh);
		sort($subdirs);
		$classes=array();
		foreach($subdirs as $path){
			$dh = opendir($path);
			while(false!==($f=  readdir($dh))){
				if($f[0]!='.'){
					$classes[substr($f,0,-4)]=$path.'/'.$f;
				}
			}
			closedir($dh);
		}
		return $classes;
	}
}
