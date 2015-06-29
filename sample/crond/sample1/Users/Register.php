<?php
/**
 * sample of Crond\Task
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Register extends \Sooh\Base\Crond\Task{
	protected function onRun($dt) {
		$this->lastMsg = "called ".$this->_counterCalled."times";
		error_log("\tCrond ".getmypid()."#\t".__CLASS__."\tcalled ".$this->_counterCalled."times");
		if($this->_counterCalled<=3)throw new \ErrorException('ErrorException test');
		error_log("\tCrond ".getmypid()."#\t".__CLASS__."\tSHOULD not HERE");
		$this->toBeContinue = false;
		return true;
	}
	/**
	 * 当调用run()时，如果抛出异常，则调用此方法处理异常（设置最后状态，返回算执行成功还是算执行失败）
	 * @param \ErrorException $e
	 * @return boolean
	 */
	public function onError(\ErrorException $e)
	{
		$this->lastMsg = "[Error]".$e->getMessage();
		return false;
	}	
}
