<?php
namespace Sooh\Yaf;
/**
 * 扩展的支持模板选择的view
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Viewext extends \Yaf_View_Simple{
	/**
	 * 
	 */
	public static $jqueryVer='jquery-1.11.2.min.js';
	/**
	 * 项目内公用的模板的路径
	 * @var string
	 */
	public static $incPath='';
	/**
	 * 输出数据使用的模板类型
	 * @var string
	 */
	public static $renderType='html';
	/**
	 * 是否禁用默认的HTML头和尾
	 * @var boolean
	 */
	public static $bodyonly=false;
	public function render ( $strTpl , $arrTplVars=null)
	{
		if(self::$renderType=='json')$ret = json_encode($this->_tpl_vars);
		elseif(self::$renderType=='echo')$ret='';
		else $ret = parent::render($this->fixTplPath($strTpl) , $arrTplVars);

		return $ret;
	}
	protected function fixTplPath($strTpl)
	{
		switch (self::$renderType){
			case 'html':return str_replace('.phtml', '.www.phtml', $strTpl);
			case 'wap':return str_replace('.phtml', '.wap.phtml', $strTpl);
			case 'echo':return;
			case 'json':return ;
		}
	}
	public function display (  $strTpl , $tpl_vars =array() )
	{
		return parent::display($this->fixTplPath($strTpl),$tpl_vars);
	}
	
	public function getScriptPath()
	{
		return $this->fixTplPath(parent::getScriptPath());
	}
	
	public function setScriptPath ( $strTpl )
	{
		return parent::setScriptPath($this->fixTplPath($strTpl));
	}
	public function renderInc($part)
	{
		return $this->render(self::$incPath.$part.'.phtml');
	}
	protected $headParts=array();
	public function htmlHeadPart($str=null)
	{
		if($str==null)return $this->headParts;
		else  $this->headParts[]=$str;
	}
}
