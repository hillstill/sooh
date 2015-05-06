<?php
/**
 * Sample of crond task
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Taskb extends \Sooh\Base\Crond\Task{
	
	protected function onRun($dt) {
		$this->lastMsg = "called ".$this->_counterCalled."times";
		error_log("\tCrond ".getmypid()."#\t".__CLASS__."\tcalled ".$this->_counterCalled."times");
		$this->toBeContinue = $this->_counterCalled<15;
		return true;
	}
}
