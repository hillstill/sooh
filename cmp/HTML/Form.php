<?php
namespace Sooh\HTML;

/**
 * Description of Form
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Form {
	public static function post($formid=null,$actNew=null,$ctrlNew=null,$modNew=null)
	{
		$uri = \Sooh\Base\Tools::uri(null, $actNew, $ctrlNew, $modNew);
		$r = explode('?', $uri);
		$args=array();
		parse_str($r[1],$args);
		return "<form ".(empty($formid)?'':"id=\"$formid\"")." method=post action=\"$r[0]\">".self::hiddens($args);
	}
	public static function get($formid=null,$actNew=null,$ctrlNew=null,$modNew=null)
	{
		$uri = \Sooh\Base\Tools::uri(null, $actNew, $ctrlNew, $modNew);
		$r = explode('?', $uri);
		$args=array();
		parse_str($r[1],$args);
		return "<form ".(empty($formid)?'':"id=\"$formid\"")." method=get action=\"$r[0]\">".self::hiddens($args);
	}
	public static function hiddens($args)
	{
		$str = '';
		if(is_array($args))	foreach($args as $k=>$v)
			$str.="<input type=hidden name=\"$k\" value=\"".htmlspecialchars ($v)."\">";
		return $str;
	}
	public static function singleline($name,$value,$width=120)
	{
		return "<input type=text name=$name value=\"".htmlspecialchars ($value)."\" style=\"width:{$width}px;\">";
	}
	public static function password($name,$value,$width=120)
	{
		return "<input type=password name=$name value=\"".htmlspecialchars ($value)."\" style=\"width:{$width}px;\">";
	}
	public static function multiline($name,$value,$width=120,$height=30)
	{
		return "<textarea name=$name rows=$rows style=\"width:{$width}px;height:{$height}px;\">$value</textarea>";
	}
	public static function chkboxs($name,$values)
	{
		
	}
	public static function radio($name,$capts,$value)
	{
		
	}

	public static function btnStd($capt,$onclick)
	{
		return '<input type=button value="'.$capt.'" onclick="'.$onclick.'">';
	}
	public static function btnReset($capt)
	{
		return '<input type=reset value="'.$capt.'">';
	}
	public static function btnSubmit($capt,$name=null,$onclick=null)
	{
		return '<input type=submit value="'.$capt.'" '.(empty($name)?'':'name="'.$name.'"').' '.(empty($onclick)?'':'onclick="'.$onclick.'"').'>';
	}
	public static function link($capt, $uri='#', $onclick='')
	{
		return "<a href=\"$uri\" ".(empty($onclick)?'':"onclick=\"$onclick\"").">$capt</a>";
	}
	
	public static function linkBtn($capt, $uri='#', $onclick='')
	{
		return "<a href=\"$uri\" ".(empty($onclick)?'':"onclick=\"$onclick\"")." class=\"linkBtn\">$capt</a>";
	}
}
