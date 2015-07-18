<?php
namespace Sooh\Base\Log\Writers;
/**
 * 默认的写文本的log writer (每天一个文件)
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class TextDailyFile {
	private $path;
	private $file;
	public function __construct($path,$filename) {
		$this->path = $path;
		$this->file = $filename;
	}
	/**
	 * 
	 * @param \Sooh\Base\Log\Data $logData
	 */
	public function write($logData)
	{
		$fullname = $this->path.'/'.$logData->ymd.'-'.  $this->file;
		file_put_contents($fullname, json_encode($logData->toArray()), FILE_APPEND);
	}
	public function free()
	{
		
	}
}
