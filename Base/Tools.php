<?php
namespace Sooh\Base;
/**
 * 通用工具类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Tools {
	public static function uri($args=null,$actNew=null,$ctrlNew=null,$modNew=null)
	{
		$ini = \Sooh\Base\Ini::getInstance ();
		if($actNew==null)$actNew= $ini->get ('request.action');
		if($ctrlNew==null)$ctrlNew=$ini->get ('request.controller');
		if($modNew==null)$modNew= $ini->get ('request.module');
		if(empty($args))$uri='';
		elseif(is_array($args))$uri=  http_build_query ($args);
		elseif(is_string($args))$uri=$args;
		else throw new ErrorException('unsupport args for sooh_uri');
		if(!defined('SOOH_INDEX_FILE'))define ('SOOH_INDEX_FILE', 'index.php');
		if(defined('SOOH_ROUTE_VAR')){//!empty($_REQUEST[SOOH_ROUTE_VAR]) && count(explode('/', $_REQUEST[SOOH_ROUTE_VAR]))>1
			$uri = $ini->get ('request.baseUri').'/'.SOOH_INDEX_FILE.'?'.SOOH_ROUTE_VAR."=$modNew/$ctrlNew/$actNew&$uri";
		}else{
			$uri = $ini->get ('request.baseUri').'/'."$modNew/$ctrlNew/$actNew?$uri";
		}
		return $uri;
	}
	public static function uriTpl($args=null,$actNew=null,$ctrlNew=null,$modNew=null)
	{
		$uri = self::uri($args,$actNew,$ctrlNew,$modNew);
		return str_replace(array('%7B','%7D'),array('{','}'),$uri);
	}
	public static function remoteIP()
	{
		$proxyIP = \Sooh\Base\Ini::getInstance()->get('inner_nat');
		if(!empty($proxyIP)){
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			return $_SERVER['REMOTE_ADDR'];
		}
	}
}
