<?php
namespace Sooh\DB\Base;
/**
 * @deprecated since 1.1 use \Sooh\DB\Error instead
 * @author Simon Wang <sooh_simon@163.com> 
 */
class Error extends \Sooh\DB\Error
{
	public function __construct($code,$errOriginal,$lastSql) {
		error_log('@deprecated use \Sooh\DB\Error instead');
		parent::__construct($code, $errOriginal, $lastSql);
	}

}
