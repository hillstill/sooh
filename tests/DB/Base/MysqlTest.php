<?php
if(!class_exists('SoohDBTestBase')){
	include __DIR__.'/../sqls/Base.php';
}
class MysqlTest extends SoohDBTestBase {
	protected function setUp() {
		$driver = 'mysql'.'0';
		$this->setUpReal($driver);
	}
}
