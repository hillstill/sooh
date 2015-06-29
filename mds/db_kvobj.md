Sooh\DB
===================
## KVObj类定义的关键点：

```php
//$GLOBALS['CONF']['dbByObj']=array('TestObj'=>array('default','other'));
//该配置说明TestObj的数据平均分配到2个数据库配置中

//$GLOBALS['CONF']['dbConf']=array(
// 'default'=>array('host'=>'host1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
//                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp0')),
// 'other'=>array('host'=>'host2','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
//                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp1')),
//    );

//如果指定分10张表，那么
//会分配到：	host1: db_tmp0.tb_test_0,db_tmp0.tb_test_2,db_tmp0.tb_test_4,db_tmp0.tb_test_6,db_tmp0.tb_test_8,
//会分配到：	host2: db_tmp1.tb_test_1,db_tmp1.tb_test_3,db_tmp1.tb_test_5,db_tmp1.tb_test_7,db_tmp1.tb_test_9,

class TestClass extends Sooh\DB\Base\KVObj
{
	//指定使用什么id串定位数据库配置
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'TestObj'.($isCache?'Cache':'');
	}
	//针对缓存，非缓存情况下具体的表的名字
	protected static function splitedTbName($n,$isCache)
	{
		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
		else return 'tb_test_'.($n % static::numToSplit());
	}
	//说明分几张表
	protected static function numToSplit(){return 10;}

	/**
	 * 说明getCopy实际返回的类，同时对于只有一个主键的，可以简化写法
	 * @return TestClass
	 */
	public static function getCopy($userId)
	{
		return parent::getCopy(array('uid'=>$userId));
	}
	/**
	 * 是否启用cache机制
	 * cacheSetting=0：不启用
	 * cacheSetting=1：优先从cache表读，每次更新都先更新硬盘表，然后更新cache表
	 * cacheSetting>1：优先从cache表读，每次更新先更新cache表，如果达到一定次数，才更新硬盘表
	 */
	protected function initConstruct($cacheSetting=0,$fieldVer='iRecordVerID')
	{
		return parent::initConstruct($cacheSetting,$fieldVer)
	}
}
```

#####基本使用：
```php
$oo = TestObj::getCopy(array('ID'=>11));
$oo->load();//要执行load, 然后$oo->update()才知道该insert还是update
$oo->reload();//load执行过一次以后，再次调用不会真正的访问数据库，如果要强制重读数据库，请用$oo->reload()
$oo->exists();//判定记录是否存在
$oo->exists(fieldName);//判定指定fieldName是否存在有效值
$oo->getField(fieldName);//获取指定fieldName的值，如果是null，会丢异常，如果值可能是null,需要说明允许null:$oo->getField(fieldName, true);
$oo->setField(fieldName，fieldValue);//fieldValue如果是数组，存储时会被转化成json串，从数据库读出来的时候会还原成数组
$oo->db() 获取指定记录分配到的数据库访问类（\Sooh\DB\Interfaces\All）
$oo->tbname() 获取指定记录分配到的数据库表
$oo->lock() 锁定该记录（默认是3年）禁止其他进程写，如果不要锁那么久，比如锁3秒：$oo->lock(3)。其机制是调整iRecordVerID作为标记
$oo->unlock() 解锁
$oo->registerOn($callback,$evt)；注册监听事件回调,目前三个（KVOBJ::onAfterLoaded, KVOBJ::onBeforeSave, KVOBJ::onAfterSave, ）
$oo->update() 更新到数据库（根据情况选择insert还是update,是cache表还是正式表）
```

#####查询相关：
```php
//定义回调函数
function xxx(\Sooh\DB\Interfaces\All $db,$tb){}
//遍历所有的表，调用xxx(db,tbname);分10张表，就会回调10次
TestClass::loop(xxx);

//遍历所有表统计符合条件的记录的条数
$recordCount = $oo->loopGetRecordsCount($where);

//分页获取记录(有个限制，表要有唯一键，目前支持最多2个字段组成的唯一键):
//第一次显示第一页的数据时（或变更过滤条件时）
$ret = $oo->loopGetRecordsCount(array('autoid'=>'sort','subkey'=>'rsort'),array('where'=>array()),$pager);
//返回的$ret = array (lastPage=>array(), records=array());
//之后的代码要把返回的lastPage记录下来
//前后翻页的时候$oo->loopGetRecordsCount(array('autoid'=>'sort','subkey'=>'rsort'),$lastPage,$pager);
//同样记录下lastPage
```