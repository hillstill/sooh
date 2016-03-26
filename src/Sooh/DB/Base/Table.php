<?php
namespace Sooh\DB\Base;
/**
 * 拼sql语句的时候记录部件的类
 * @author Simon Wang <hillstill_simon@163.com> 
 */
class Table
{
	public $db=null;
	public $name=null;
	public $autoInc=null;//fieldname of auto_increment
	public $pkey=null;
	public $fullname=null;//dbname.tablename
}
