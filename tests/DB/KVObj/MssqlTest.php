<?php
if(!class_exists('SoohDBTestKVObj')){
	include __DIR__.'/../sqls/SoohDBTestKVObj.php';
	include __DIR__.'/../sqls/TstKVObj.php';
}
class MssqlTest extends SoohDBTestKVObj {
	protected function setUp() {
		$driver = 'sqlsrv';
		$this->setUpReal($driver);
	}
}
