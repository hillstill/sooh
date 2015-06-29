<?php
if(!class_exists('SoohDBTestBase')){
	include __DIR__.'/../sqls/SoohDBTestBase.php';
}
class MssqlTest extends SoohDBTestBase {
	protected function setUp() {
		$driver = 'sqlsrv'.'0';
		$this->setUpReal($driver);
	}

}
