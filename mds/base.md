Sooh\Base
===================

### **Sooh\\Base\\Ini

ȫ�ֲ�����д��װ�࣬���÷�ʽ���£�

- ��ȡ���ò���ֵ public function get($item, $default=null)
���裺`$GLOBALS[CONF][db][default]=array(host=>'127.0.0.1',...);`
��ô�� `Ini::getInstance()->get('db.default')`���ص�ֵ��array(host=>'127.0.0.1',...)
Ĭ��ֵ��`Ini::getInstance()->get('db.idNotExists','default')`���ص�ֵ��'default'
**ʹ�����ƣ����ǵ����õ�ʹ�ó�����get��$item�������֧��3������'a.b.c'��**

- �������ò���ֵ
`Ini::getInstance()->initGlobal(array('db'=>array('default=>array(host=>'127.0.0.1',...))));`

- ����
public static function registerShutdown($callback,$funcDesc)��
public function viewRenderType($newType=null)
�ο�api-doc

### **Sooh\\Base\\Time

ȫ��ʱ�䴦���װ�ࣨͨ��������Ա����ǰʱ�䣩�����÷�ʽ���£�

- ��ȡunixʱ��� public function timestamp($dayAdd=0)
��ǰ��`Time::getInstance()->timestamp()`
24Сʱǰ��`Time::getInstance()->timestamp(-1)`

- ���õ�ǰʱ�� public function mktime($h,$i,$s,$mOrYMD,$d=null,$y=null,$ms=0)
��ʽ1��`Time::getInstance()->mktime(23,59,59,12,31,2015)`
��ʽ2��`Time::getInstance()->mktime(23,59,59,20151231)`

- ���� public function sleep($seconds, $millisecond=0) and sleepTo($h,$i,$s=0,$d=null,$m=null,$y=null)

- ���� public function reset() ����֮��Ӧ������ʱ��

- �����������Ա����ͺ���
`Time::getInstance()->YmdFull` = 20151231
`Time::getInstance()->ymd`     = 151231
`Time::getInstance()->mdy`     = 123115
`Time::getInstance()->YmdH`    = 2015123123
`Time::getInstance()->hour`    = 23
`Time::getInstance()->his`     = 235959
`Time::getInstance()->isWeekend()`
`Time::getInstance()->dayToBetween($dt,$dur=1)`   return array(timestamp, timestamp):2015-03-18 00:00:00-2015-03-19 23:59:59