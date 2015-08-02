<?php
namespace Sooh\Base\Form;
use \Sooh\Base\Form\Item as sooh_formdef;
/** 
 * form 渲染
 */

class Renderer
{
	/**
	 * 构建input
	 * @param string $k
	 * @param \Sooh\Base\Form\Item $define
	 * @param string $inputType
	 */
	public function render($k,$define,$inputType)
	{
		$val4Input=$define->valForInput;
		switch ($inputType){
			case sooh_formdef::text:
				return '<input id="'.$k.'" type=text name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::passwd:
				return '<input id="'.$k.'" type=password name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::constval:
				if($define->options){
					$options = $define->options->pairVals;
					return  $options[$val4Input].'<input type=hidden name="'.$k.'" value="'.$val4Input.'">';
				}else{
					return  $val4Input.'<input type=hidden name="'.$k.'" value="'.$val4Input.'">';
				}
			case sooh_formdef::select:
				$str = '<select id="'.$k.'" name="'.$k.'">';
				$options = $define->options->pairVals;
				$found=false;
				foreach($options as $k=>$v){
					if($val4Input==$k){
						$str.= '<option value="'.$k.'"  selected>'.$v.'</option>';
						$found=true;
					}else{
						$str.= '<option value="'.$k.'">'.$v.'</option>';
					}
				}
				if(!$found){
					throw new \ErrorException($val4Input.' not found in options');
				}
				return $str.'</select>';
			case sooh_formdef::date:
				return '<input id="'.$k.'" type=text name="'.$k.'" value="'.$val4Input.'">';
			case sooh_formdef::mulit:
				return '<textarea id="'.$k.'" name="'.$k.'">'.$val4Input.'</textarea>';
			case sooh_formdef::chkbox:
				$str='';
				$options = $define->options->pairVals;
				$values = explode(',', $val4Input);
				foreach($options as $i=>$v){
					if(in_array($i, $values)){
						$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" checked=true/>'.$v.'</label>';
					}else{
						$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" />'.$v.'</label>';
					}
				}
				return $str;
			case sooh_formdef::radio:
				$str='';
				$options = $define->options->pairVals;
				$values = explode(',', $val4Input);
				foreach($options as $i=>$v){
					if(in_array($i, $values)){
						$str.='<label><input type="radio" name="'.$k.'" value="'.$i.'" checked=true/>'.$v.'</label>';
					}else{
						$str.='<label><input type="radio" name="'.$k.'" value="'.$i.'" />'.$v.'</label>';
					}
				}
				return $str;
			default:
				throw new \ErrorException("unknown input type:".var_export($inputType,true));
		}
	}
	/**
	 * 返回  <form action=xxx method=get ....>
	 * @param Broker $form
	 * @return type
	 */
	public function htmlFormTag($form)
	{
		$str = "<form action=\"{$form->url}\"".(empty($form->html_id)?'':" id=\"{$form->html_id}\"");
		if($form->method=='get'||$form->method=='post'){
			$str.=" method=\"$form->method\"";
		}elseif($form->method=='upload'){
			$str.=" method=\"post\" enctype=\"multipart/form-data\"";
		}else{
			$str.=" method=\"get\"";
		}
		return $str.">";
	}
	
	public function htmlFormButton($title,$type="submit",$other=null)
	{
		$type=  strtolower($type);
		if($type=='button'||$type=='reset'||$type='submit'){
			return "<input type=\"$type\" value=\"$title\" $other>";
		}else{
			throw new \ErrorException('button type error:'.$type);
		}
	}
}