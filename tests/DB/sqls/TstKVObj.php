<?php
/**
 * 测试用的 TstKVObj
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class TstKVObj extends \Sooh\DB\Base\KVObj
{
	protected static function splitedTbName($n,$isCache)
	{
		if($isCache)return 'tb_'.($n % static::numToSplit());
		else return 'tb_'.($n % static::numToSplit());
	}
	protected static function numToSplit(){return 2;}
	public function fmtTestResult($ret)
	{
		$str = "";
		foreach($ret['records'] as $r){
			$str.="[".  implode(',', $r)."]";
		}
		$str = json_encode($ret['lastPage'])."\n\t\t".$str;
		return $str;
	}
	public function fmtRecords($ret)
	{
		$str = "";
		foreach($ret['records'] as $r){
			unset($r[$this->fieldName_verid]);
			$str.="[".  implode(',', $r)."]";
		}
		return $str;
	}
}
