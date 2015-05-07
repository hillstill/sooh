<?php
namespace Sooh\Base;
/**
 * 执行结果
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class RetSimple {
	const ok=0;
	const errDefault=-1;
	public function __construct($ret=0,$msg='') {
		$this->ret=$ret-0;
		$this->msg=$msg;
	}
	public $ret=0;
	public $msg='';
}
