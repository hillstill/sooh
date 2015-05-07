<?php
namespace Sooh\Yaf;
/**
 * yaf 框架 初始化 以及 默认登入检查的 plugin
 * 需要 define('SOOH_ROUTE_VAR','__');
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class SoohPlugin extends \Yaf_Plugin_Abstract{
	/**
	 * @param array $loginChk 登入检查的控制逻辑定义
	 */
	public static function initRightsCheck($loginChk=array())
	{
		
	}
	/**
	 * 
	 * @param Yaf_Dispatcher $dispatcher
	 * @param  string $jqueryVer 使用的jquery文件，默认值：jquery-1.11.2.min.js
	 */
	public static function initYafBySooh($dispatcher,$jqueryVer='jquery-1.11.2.min.js')
	{
		$router = $dispatcher->getRouter();
		$router->addRoute("byVar", new \Yaf_Route_Supervar(SOOH_ROUTE_VAR));
		
		$req = $dispatcher->getRequest();
		$tmp = $req->get('__ONLY__');
		if($tmp=='body'){
			\Sooh\Yaf\Viewext::$bodyonly = true;
		}
		$tmp = $req->get('__VIEW__');//html(default),wap,  json
		\Sooh\Yaf\Viewext::$incPath = APP_PATH.'/application/views/_inc/';
		\Sooh\Yaf\Viewext::$jqueryVer=$jqueryVer;
		if(!empty($tmp)){
			$tmp=strtolower ($tmp);
			if($tmp=='json'){
				\Sooh\Yaf\Viewext::$renderType=$tmp;
			}elseif($tmp=='html' || $tmp=='wap'){
				\Sooh\Yaf\Viewext::$renderType=$tmp;
			}
		}

		$tmp = $dispatcher->getRequest()->get('__GZIP__');
		if(!empty($tmp)){
			$tmp = strtolower ($tmp);
			if($tmp=='gzip')define("ZIP_OUTPUT",$tmp);
		}
		
		$dispatcher->setView( new \Sooh\Yaf\Viewext( null ) );
		
		$dispatcher->registerPlugin(new SoohPlugin());
	}
	
	public function routerStartup (\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{

	}
	public function routerShutdown(\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{
		
		
//		if($_SERVER['REMOTE_ADDR']!='172.25.3.9')
//		error_log(__CLASS__.'->'.__FUNCTION__.":".$request->getModuleName().'/'.$request->getControllerName()."/".$request->getActionName()."#".$_SERVER['REMOTE_ADDR'].'#'.$_SERVER['REQEUST_URI']);
		$m = strtolower($request->getModuleName());
		$c = strtolower($request->getControllerName());
		if($m!='index'){
			$sess = \Sooh\DB\Acl\Acl::getInstance();
			if(!$sess->isLogined()){
				$sess->onNeedsLogin($_SERVER['REQUEST_URI']);
			}
			if(!$sess->hasRightsTo($m, $c)){
				$sess->onNeedsRights("$m.$c");
			}
		}
		
		$ini = \Sooh\Base\Ini::getInstance ();
		$tmp=$request->getBaseUri();
		if(substr($tmp,-4)=='.php'){
			$tmp = explode('/',$tmp);
			array_pop($tmp);
			$tmp = implode('/', $tmp);
		}
		if($tmp=='/')$tmp='';
		$ini->initGobal(array('request'=>array('action'=>$request->getActionName(),
												'controller'=>lcfirst($request->getControllerName()),
												'module'=>lcfirst($request->getModuleName()),
												'baseUri'=>$tmp
												)
				));
		if(Viewext::$renderType!='json'){
			\Sooh\HTML\Base::$jquery =Viewext::$jqueryVer;
		}
//		error_log(__CLASS__.'->'.__FUNCTION__);
	}
	public function dispatchLoopStartup(\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{
//		error_log(__CLASS__.'->'.__FUNCTION__);
	}
	public function preDispatch(\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{
//		error_log(__CLASS__.'->'.__FUNCTION__);
	}
	public function postDispatch(\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{
//		error_log(__CLASS__.'->'.__FUNCTION__);
	}
	public function dispatchLoopShutdown(\Yaf_Request_Abstract $request, \Yaf_Response_Abstract $response)
	{
//		error_log(__CLASS__.'->'.__FUNCTION__);
	}
}
