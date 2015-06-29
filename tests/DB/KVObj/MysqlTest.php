<?php
if(!class_exists('SoohDBTestKVObj')){
	include __DIR__.'/../sqls/KVobj.php';
	include __DIR__.'/../sqls/TstKVObj.php';
}
class MysqlTest extends SoohDBTestKVObj {
	protected function setUp() {
		$driver = 'mysql';
		$this->setUpReal($driver);
	}
}
