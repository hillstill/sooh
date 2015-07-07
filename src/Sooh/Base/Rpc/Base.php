<?php
namespace Sooh\Base\Rpc;
/**
 * Description of Base
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Base {
	public static $debugMod=false;
	/**
	 * 远程调用
	 * @param string $cmd
	 * @param array $args
	 * @return mixed 
	 * @throws ErrorException
	 */
	public function call($cmd,$args)
	{
		$this->classAndMethod = $cmd;
		$this->args = $args;
		$this->createSign();
		return $this->sendRequest();
	}
	protected $args;
	protected $classAndMethod;
	protected $key;
	protected $dt;
	protected $sign;
	protected $hostBase;
	public function __construct($key,$hosts) {
		$k = array_rand($hosts);
		$this->hostBase = $hosts[$k];
		$this->key = $key;
	}
	protected function createSign()
	{
		$this->dt = \Sooh\Base\Time::getInstance()->timestamp();
		$this->sign = md5($this->dt.$this->key);
	}

	protected function sendRequest()
	{
		throw new \Sooh\Base\ErrException('todo');
	}
}
