<?php
namespace Sooh\Base\Rpc;
/**
 * rpc
 * @usage $response = \Sooh\Base\Rpc\Base::getInstance($cmd) [->initArgs()] ->send();
 * 
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Broker {
	public static $debugMod=false;
	private static $_protocols=array();
	private static $_instances=array();
	/**
	 * 获取rpc实例
	 *		1）globals里要有 CONF.RpcConfig = array(key=>'',class=>HttpGet,urls=>array(127.0.0.1/index.php?, URI2),'')
	 *		2）rpc-broker默认通过 rpcservices/fetchini?cmdid=cmd
	 *			获取cmd对应的配置：array(class=>HttpGet,key=>sss,urls=>array())
	 *		3）根据class实例化，初始化
	 * @param string $serviceName
	 * @return \Sooh\Base\Rpc\Broker
	 * @throws \Sooh\Base\ErrException
	 */
	public static function factory($serviceName)
	{
		$serviceName = self::formatCmd($serviceName);
		if(!isset(self::$_instances[$serviceName])){
			$_ini = self::getRpcIni($serviceName);
			if(empty($_ini)){
				throw new \Sooh\Base\ErrException('unknown cmd');
			}
			if($_ini['class']!='HttpGet'){
				$class = str_replace('Broker', $_ini['class'], __CLASS__);
			}else{
				$class = __CLASS__;
			}
			self::$_instances[$serviceName] = new $class;
			self::$_instances[$serviceName]->final_service=$serviceName;
			//self::$_instances[$serviceName]->final_cmd=$serviceName;
			self::$_instances[$serviceName]->final_key=$_ini['key'];
			self::$_instances[$serviceName]->final_hosts=$_ini['urls'];
		}
		return self::$_instances[$serviceName];
	}
	/**
	 * 格式化cmd
	 * @param string $cmd
	 * @return string
	 */
	protected static function formatCmd($cmd)
	{
		return $cmd;
	}
	protected $final_service;
	protected $final_cmd;
	protected $final_args;
	protected $final_key;
	protected $final_hosts;
	/**
	 * 获取PRC配置参数
	 * @param string $cmd 
	 * @return array
	 * @throws \Sooh\Base\ErrException
	 */
	protected static function getRpcIni($cmd)
	{
		$ini = \Sooh\Base\Ini::getInstance();
		if(empty(self::$_instances['_RPC_ROUTE_'])){
			$class = $ini->get('RpcConfig.class');
			self::$_instances['_RPC_ROUTE_'] = new $class;
			self::$_instances['_RPC_ROUTE_']->final_service='rpcservices';
			self::$_instances['_RPC_ROUTE_']->final_cmd='fetchini';
			self::$_instances['_RPC_ROUTE_']->final_key=$ini->get('RpcConfig.key');
			self::$_instances['_RPC_ROUTE_']->final_hosts=$ini->get('RpcConfig.urls');
		}
		return self::$_instances['_RPC_ROUTE_']->initArgs(array('service'=>$cmd))->send();
	}
	/**
	 * 设置参数
	 * @param array $args
	 * @return \Sooh\Base\Rpc\Broker
	 */
	public function initArgs($args)
	{
		$this->final_args=$args;
		return $this;
	}
	/**
	 * 发送请求
	 * @return mixed
	 */
	public function send($cmd)
	{
		$this->final_cmd=$cmd;
		$hosts = $this->final_hosts;
		while(sizeof($hosts)){
			$rand = array_rand($hosts);
			$host = $hosts[$rand];
			unset($hosts[$rand]);
			$ret = $this->_send($host,$this $this->final_cmd, $this->final_args);
			if(empty($ret)){
				\Sooh\Base\Log\Data::error("found_rpc_server_down:".$host.' with cmd');
			}else{
				$arr = json_decode($ret,true);
				if(is_array($arr)){
					return $arr;
				}
			}
		}
		throw new \Sooh\Base\ErrException('rpc_failed:'.$this->final_cmd);
	}
	/**
	 * 实际发送请求到server,默认http-get
	 * @return mixed or null 
	 */
	protected function _send($host,$service,$cmd,$args)
	{
		$dt = \Sooh\Base\Time::getInstance()->timestamp();
		$ret = \Sooh\Base\Tools::httpGet($host.$cmd.'&args='.  http_build_query($args).'&dt='.$dt.'&sign='.$this->clacSign($dt));
		if(200==\Sooh\Base\Tools::httpCodeLast()){
			return $ret;
		}else{
			return null;
		}
	}
	/**
	 * 计算签名
	 * @param int $timestamp 时间戳
	 */
	public function clacSign($timestamp)
	{
		return md5($timestamp.$this->final_key);
	}
}
