<?php
namespace Sooh\DB\Base;

/**
 * 拼sql语句的时候记录部件的类
 *
 * @author Simon Wang <hillstill_simon@163.com> 
 */



class SQLDefine {
	public $server='';
	public $dowhat='select';
	public $field='*';
	public $tablenamefull='dbname.tbname';
	public $where=null;
	public $orderby;//rsort
	public $fromAndSize=array(0,10000);
	public $pkey=null;
	public $join='';
	public $result;//????
	public $strTrace;//'select * from tb...';
	
	/**
	 * 返回下一个循环计数的值（1 - 99999999）
	 * @param int $cur
	 * @return int
	 */
	public static function nextCircledInt($cur)
	{
	    return ($cur>=99999999)?1:$cur+1;
	}
	
	public function resetForNext()
	{
		//$this->server=null;
		$this->join='';
		$this->pkey=null;
		$this->dowhat=null;
		$this->field=null;
		$this->tablenamefull=null;
		$this->where=null;
		$this->orderby=null;
		$this->fromAndSize=array(0,10000);
	}
}
