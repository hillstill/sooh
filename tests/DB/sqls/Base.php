<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-05-21 at 10:09:26.
 */
class SoohDBTestBase extends \PHPUnit_Framework_TestCase {

	protected function setUpReal($driver)
	{
		$this->driver = $driver;
		$lines = file(__DIR__.'/../sqls/connect.ini');
		foreach($lines as $line){
			$tmp = explode('=', $line);
			if($tmp[0]==$driver){
				array_shift($tmp);
				$tmp = json_decode(implode('=', $tmp),true);
				$ini = \Sooh\Base\Ini::getInstance();
				$old = $ini->get('dbConf');
				$old[$this->driver]=$tmp;
				$ini->initGobal(array('dbConf'=>$old));
				$this->dbDefault = $tmp['dbEnums']['default'].'.';
				break;
			}
		}
		$this->getDB()->delRecords('tb_0',array('autoid'=>100));
	}
	protected $driver;
	protected $dbDefault;
	/**
	 * 
	 * @return \Soob\DB\Interfaces\All
	 */
	protected function getDB()
	{
		return \Sooh\DB\Broker::getInstance($this->driver);
	}
	protected function tearDown() {
	}

//	public function testDBName()
//	{
//		$this->AssertEquals('checkDBName['.$this->driver.']', $this->dbDefault  );
//	}
	
	public function testGets() {
		$db = $this->getDB();
		$this->AssertEquals('c', $db->getOne('tb_0', 'val',array('autoid'=>6))  );
		$this->AssertEquals(12, $db->getOne($this->dbDefault.'tb_0', 'autoid',array('pkey'=>202,'val'=>'f')));
		$this->AssertEquals(null,$db->getOne('tb_0', 'pkey',array('autoid'=>6666)));
		
		$this->AssertEquals(array('val'=>'c'), $db->getRecord($this->dbDefault.'tb_0', 'val',array('autoid'=>6))  );
		$this->AssertEquals(array('autoid'=>12,'subkey'=>260), $db->getRecord('tb_0', 'autoid,subkey',array('pkey'=>202,'val'=>'f')));
		$this->AssertEquals(null,$db->getRecord('tb_0', '*',array('autoid'=>6666)));
		
		$this->AssertEquals(array(2=>201,4=>201),$db->getPair('tb_0', 'autoid','pkey',array('autoid<'=>6)));
		
		$cmp = array(
			2=>array('pkey'=>201,'subkey'=>220),
			10=>array('pkey'=>202,'subkey'=>240)
			);
		$rs =$db->getAssoc('tb_0', 'autoid','pkey,subkey',array('autoid'=>array(2,10)));
		foreach($cmp as $k=>$r){
			if(!isset($rs[$k])){
				$this->Fail('records loaded less than matched');
			}
			foreach($r as $i=>$v){
				$this->AssertEquals($v,$rs[$k][$i]);
			}
			unset($rs[$k]);
		}
		if(!empty($rs)){
			$this->Fail('records loaded more than matched');
		}
		
		$rs =$db->getRecords('tb_0', 'pkey,subkey',array('autoid'=>array(2,10)),'sort autoid');
		foreach($cmp as $k=>$r){
			$k = key($rs);
			foreach($r as $i=>$v){
				$this->AssertEquals($v,$rs[$k][$i]);
			}
			unset($rs[$k]);
		}
		if(!empty($rs)){
			$this->Fail('records loaded more than matched');
		}
		
		$this->AssertEquals(6,$db->getRecordCount('tb_0'));
		$this->AssertEquals(3,$db->getRecordCount('tb_0', array('autoid<'=>8)));
	}


	public function testAddUpdDel() {
		$db = $this->getDB();
		$db->addRecord('tb_0', array('autoid'=>100,'pkey'=>101,'subkey'=>102,'val'=>"tst"));
		$this->AssertEquals('tst', $db->getOne($this->dbDefault.'tb_0', 'val',array('autoid'=>100))  );
		
		$db->updRecords('tb_0', array('pkey'=>888,),array('autoid'=>100));
		$this->AssertEquals(888, $db->getOne($this->dbDefault.'tb_0', 'pkey',array('autoid'=>100))  );
		
		$ret = $db->delRecords('tb_0',array('autoid'=>100));
		$this->AssertEquals(1, $ret  );
		$this->AssertEquals(null, $db->getOne($this->dbDefault.'tb_0', 'val',array('autoid'=>100))  );
		
	}
	
	
}