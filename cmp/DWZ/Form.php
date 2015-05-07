<?php
namespace Sooh\DWZ;
use \Sooh\DB\Form\Definition as  sooh_formdef;
/**
 * Description of Form
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Form  extends \Sooh\DB\Form\Base{
	/**
	 * 
	 * @param string $id
	 * @param string $cruds  c(create) r(read) u(update) d(delete) s(search)
	 * @return \Sooh\DWZ\Form
	 */
	public static function getCopy($id='default',$cruds='c')
	{
		return parent::getCopy($id,$cruds);
	}

	/**
	 * 构建input
	 * @param string $k
	 * @param \Sooh\DB\Form\Definition $define
	 */
	protected function buildInput($k,$define)
	{
		if(!is_a($define, '\Sooh\DB\Form\Definition')){
			$err=new \ErrorException("defain formItem $k is NOT Form\Definition");
			error_log($err->getMessage()."\n".$err->getTraceAsString());
			throw $err;
		}
		$inputType = $define->input($this->crudType);
		if(is_array($inputType)){
			return call_user_func($inputType, $k, $define);
		}elseif(is_scalar($inputType)){
			switch ($inputType){
				case sooh_formdef::text:
					if(!empty($define->verify)){
						if(!empty($define->verify['cssId'])) $str = ' id="'.$define->verify['cssId'].'" class="';
						else $str = ' class="';
						if($define->verify['required'])$str.='required ';
						switch($define->verify['type']){
							case 'int':
								$str.='digits"';
								if($define->verify['max'])$str.=' min="'.$define->verify['min'].'" max="'.$define->verify['max'].'"';
								break;
							case 'str':
								$str.= '"';
								if($define->verify['max'])$str.=' minlength="'.$define->verify['min'].'" maxlength="'.$define->verify['max'].'"';
								break;
							default:
								$str.= '"';
								break;
						}
					}else $str='';
					$str = '<input type=text name="'.$k.'" size="30"  value="'.$define->value.'"'.$str;
					return $str.'>';
				case sooh_formdef::passwd:
					if(!empty($define->verify)){
						if(!empty($define->verify['cssId'])) $str = ' id="'.$define->verify['cssId'].'" class="';
						else $str = ' class="';
						if($define->verify['required'])$str.='required "';
						if($define->verify['max'])$str.='" minlength="'.$define->verify['min'].'" maxlength="'.$define->verify['max'].'"';
						if($define->verify['cmpCssId'])$str.='" equalto="#'.$define->verify['cmpCssId'].'"';
					}else $str='';
					return '<input type=password name="'.$k.'" size="30"  value="'.$define->value.'"'.$str .'>';
				case sooh_formdef::hidden:
					return false;
				case sooh_formdef::constval:
					if($define->options){
						$tmp = $define->options->getPair($this->values,$this->crudType=='s');
						if(isset($tmp[$define->value]))	return  $tmp[$define->value];//.'<input type=hidden name="'.$k.'" value="'.$define->value.'">';
						else {
							error_log("[Error options missing]$k:{$define->value} not found in ".  json_encode($tmp));
							return  $define->value;
							//.'<input type=hidden name="'.$k.'" value="'.$define->value.'">';
						}
					}else return  $define->value;
					//.'<input type=hidden name="'.$k.'" value="'.$define->value.'">';
				case sooh_formdef::select:
					$str = '<select class="combox" name="'.$k.'">';
					$options = $define->options->getPair($this->values,$this->crudType=='s');
					$found=false;
					foreach($options as $k=>$v){
						if($define->value==$k)	{$str.= '<option value="'.$k.'"  selected>'.$v.'</option>';$found=true;}
						else $str.= '<option value="'.$k.'">'.$v.'</option>';
					}
					if(!$found){
						throw new \ErrorException($define->value.' not found in options');
					}
					return $str.'</select>';
				case sooh_formdef::chkbox:
					$str='';
					$options = $define->options->getPair($this->values,$this->crudType=='s');
					$values = explode(',', $define->value);
					foreach($options as $i=>$v){
						if(in_array($i, $values)){
							$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" checked=true/>'.$v.'</label>';
						}else{
							$str.='<label><input type="checkbox" name="'.$k.'[]" value="'.$i.'" />'.$v.'</label>';
						}

					}
					return $str;
				case sooh_formdef::date:
					$str = '<input type="text" name="'.$k.'" class="date" dateFmt="yyyyMMdd" minDate="1900-01-01" maxDate="2038-01-01" value="'.$define->value.'"/>';
					return $str;
				default:
					throw new \ErrorException('unsupport input type found:'.$inputType);
			}
		}elseif(is_callable($inputType)){
			return $inputType($k,$define);
		}else{
			throw new \ErrorException('unsupport input type found:'.  gettype($inputType));
		}
	}
	
	public static function encodePkey($arr)
	{
		return base64_encode(json_encode($arr));
	}
	
	public static function decodePkey($str)
	{
		if(!empty($str)){
			$str = base64_decode($str);
			return json_decode($str,true);
		}else return null;
	}
}
