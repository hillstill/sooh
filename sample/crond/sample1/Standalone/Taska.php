<?php
/**
 * Sample of crond task
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Taska extends \Sooh\Base\Crond\Task{
	public function init() {
		parent::init();
		$this->_secondsRunAgain=300;
	}
	/**
	 * 定时调用，返回true表示可以被释放了，返回false表示当前这个小时内还需要再次调用的
	 * @param \Sooh\Base\Time $dt
	 * @return boolean
	 */
	protected function onRun($dt) {
		$this->lastMsg = "called ".$this->_counterCalled."times(".date('m-d H:i:s',$dt->timestamp()).")";
		error_log("\tCrond ".getmypid()."#\t".__CLASS__."\t".$this->lastMsg);
		$this->toBeContinue = $this->_counterCalled<3;
		return true;
	}
}
