<?php
namespace Sooh\DWZ;
/**
 * phtml 辅助类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class phtml
{
	/**
	 * @param \Sooh\DB\Pager;
	 */	
	public static function showPager($pager)
	{
		$str  = '<div class="panelBar">';
		if(is_array($pager->enumPagesize))$tmp = $pager->enumPagesize;
		else $tmp = explode(',', $pager->enumPagesize);
		$str .= '<div class="pages"><span>显示</span>';
		$str .=  '<select class="combox" name="numPerPage" onchange="navTabPageBreak({numPerPage:this.value})">';
		foreach($tmp as $n) $str .=  "<option value=\"$n\" ".($n==$pager->page_size?'selected':'').">$n</option>";
		$str .=  '</select><span>条，共'. $pager->total. '条</span>';
		$str .=  "</div>\n";
		
		$str .=  "<div class=\"pagination\" targetType=\"navTab\" totalCount=\"{$pager->total}\" numPerPage=\"{$pager->page_size}\" pageNumShown=\"10\" currentPage=\"".($pager->pageid())."\"></div>";
		$str .=  '</div>';
		return $str;
	}
	public static function header()
	{
		$str = \Sooh\HTML\Base::includeCss('dwz/themes/default/style.css');
		$str.= \Sooh\HTML\Base::includeCss('dwz/themes/css/core.css');
		$str.= \Sooh\HTML\Base::includeCss('dwz/themes/css/print.css','print');
		$str.= \Sooh\HTML\Base::includeCss('dwz/uploadify/css/uploadify.css');

		$str.= "<!--[if IE]>\n".\Sooh\HTML\Base::includeCss('dwz/themes/css/ieHack.css')."\n<![endif]-->\n";
		$str.= '<style type="text/css">
			#header{height:50px}
			#leftside, #container, #splitBar, #splitBarProxy{top:55px}
		</style>';
		$str.= "<!--[if lte IE 9]>\n".\Sooh\HTML\Base::includeJS('js/speedup.js','dwz')."\n<![endif]-->\n";
		$str.= \Sooh\HTML\Base::includeJS('js/jquery-1.7.2.min.js',	'dwz');
		$str.= \Sooh\HTML\Base::includeJS('js/jquery.cookie.js',		'dwz');
		$str.= \Sooh\HTML\Base::includeJS('js/jquery.validate.js',	'dwz');
		$str.= \Sooh\HTML\Base::includeJS('js/jquery.bgiframe.js',	'dwz');
		$str.= \Sooh\HTML\Base::includeJS('xheditor/xheditor-1.2.1.min.js',			'dwz');
		$str.= \Sooh\HTML\Base::includeJS('xheditor/xheditor_lang/zh-cn.js',			'dwz');
		//echo \Sooh\HTML\Base::includeJS('uploadify/scripts/jquery.uploadify.min.js','dwz');
		$str.= \Sooh\HTML\Base::includeJS('bn/dwz.min.js',		'dwz');
		$str.= \Sooh\HTML\Base::includeJS('js/dwz.regional.zh.js','dwz');
		return $str;
	}

	/**
	 * 
	 * @param \Sooh\DB\Acl\Menu $menuRoot
	 * @param \Sooh\DB\Acl\Menu $_ignore_
	 * @return string
	 */
	public static function menuleft($menuRoot,$_ignore_=null)
	{
		$str = '<div class="accordion" fillSpace="sideBar">'."\n";
		foreach($menuRoot->children as $_ignore_){
			$str .= '<div class="accordionHeader"><h2><span>Folder</span>'.$_ignore_->capt.'</h2></div>'."\n";
			$str .= '<div class="accordionContent"><ul class="tree treeFolder"><!--treeCheck-->'."\n";
			$tmp = $_ignore_->children;
			foreach($tmp as $_ignore_){
				$str .= '<li><a href="'.$_ignore_->url.'" target="navTab" ';
				if(false===strpos($_ignore_->url, '__ONLY__'))$str .= 'external="true"  rel="page_'.$_ignore_->capt.'">';
				else $str .= 'rel="page_'.$_ignore_->capt.'">';
				$str.=$_ignore_->capt.'</a></li>'."\n";
			}
			$str .= '</ul></div>';
		}
		$str.='</div>';

		return $str;
	}
}