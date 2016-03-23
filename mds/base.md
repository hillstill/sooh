Sooh\Base
===================

### **Sooh\\Base\\Ini

全局参数读写封装类，常用方式如下：

- 读取配置参数值 public function get($item, $default=null)
假设：`$GLOBALS[CONF][db][default]=array(host=>'127.0.0.1',...);`
那么： `Ini::getInstance()->get('db.default')`返回的值是array(host=>'127.0.0.1',...)
默认值：`Ini::getInstance()->get('db.idNotExists','default')`返回的值是'default'
**使用限制：考虑到配置的使用场景，get的$item参数最多支持3级，即'a.b.c'。**

- 设置配置参数值
`Ini::getInstance()->initGlobal(array('db'=>array('default=>array(host=>'127.0.0.1',...))));`

- 其他
public static function registerShutdown($callback,$funcDesc)；
public function viewRenderType($newType=null)
参看api-doc

### **Sooh\\Base\\Time

全局时间处理封装类（通过这个可以变更当前时间），常用方式如下：

- 获取unix时间戳 public function timestamp($dayAdd=0)
当前：`Time::getInstance()->timestamp()`
24小时前：`Time::getInstance()->timestamp(-1)`

- 设置当前时间 public function mktime($h,$i,$s,$mOrYMD,$d=null,$y=null,$ms=0)
方式1：`Time::getInstance()->mktime(23,59,59,12,31,2015)`
方式2：`Time::getInstance()->mktime(23,59,59,20151231)`

- 休眠 public function sleep($seconds, $millisecond=0) and sleepTo($h,$i,$s=0,$d=null,$m=null,$y=null)

- 重置 public function reset() 休眠之后应该重置时间

- 其他常用属性变量和函数
`Time::getInstance()->YmdFull` = 20151231
`Time::getInstance()->ymd`     = 151231
`Time::getInstance()->mdy`     = 123115
`Time::getInstance()->YmdH`    = 2015123123
`Time::getInstance()->hour`    = 23
`Time::getInstance()->his`     = 235959
`Time::getInstance()->isWeekend()`
`Time::getInstance()->dayToBetween($dt,$dur=1)`   return array(timestamp, timestamp):2015-03-18 00:00:00-2015-03-19 23:59:59