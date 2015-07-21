<?php
namespace Sooh\Base\Rpc;
/**
 * Description of RpcHttp
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Http extends Base{

	protected function sendRequest()
	{
		if(strpos($this->hostBase, '?')){
			$url = $this->hostBase."&cmd={$this->classAndMethod}&args=".urlencode(json_encode($this->args))."&dt={$this->dt}&sign={$this->sign}";
		}else{
			$url = $this->hostBase."?cmd={$this->classAndMethod}&args=".urlencode(json_encode($this->args))."&dt={$this->dt}&sign={$this->sign}";
		}
		
		$ret = \Sooh\Base\Tools::httpGet($url);
		if(self::$debugMod){
			$trace = new \ErrorException('');
			error_log("RPC-by-HTTP-called[PID:".  getmypid()."]:{$this->classAndMethod} by {$_GET['__']}\n".$trace->getTraceAsString()."\n$url\nresponse:".var_export($ret,true));
		}
		$r = json_decode($ret,true);
		if($r){
			if($r['code']==200){
				return $r['data'];
			}else{
				error_log($r['msg'].': '.$url);
				throw new \ErrorException($r['msg']);
			}
		}else{
			error_log('internal error found: '.$url);
			throw new \ErrorException('internal error found');
		}
	}
}
