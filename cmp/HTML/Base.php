<?php
namespace Sooh\HTML;
/**
 * Description of Base
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Base {
	//put your code here
	public static $jquery='jquery-1.11.2.min.js';
	public static $baseuri='/test';
	public static function includeJS($fullnameUnderJs,$jsDir='js')
	{
		$baseuri= \Sooh\Base\Ini::getInstance()->get('request.baseUri');
		
		if($fullnameUnderJs=='jquery')$fullnameUnderJs=self::$jquery;
		return '<script  src="'.$baseuri.'/'.$jsDir.'/'.$fullnameUnderJs.'"></script>'."\n";
	}
	public static function includeCss($fullname,$media='screen')
	{
		$baseuri= \Sooh\Base\Ini::getInstance()->get('request.baseUri');
		return '<link href="'.$baseuri.'/'.$fullname.'" rel="stylesheet" type="text/css" media="'.$media.'"/>'."\n";
	}
}
