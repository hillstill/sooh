Sooh\DB
===================
## KVObj�ඨ��Ĺؼ��㣺

```php
//$GLOBALS['CONF']['dbByObj']=array('TestObj'=>array('default','other'));
//������˵��TestObj������ƽ�����䵽2�����ݿ�������

//$GLOBALS['CONF']['dbConf']=array(
// 'default'=>array('host'=>'host1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
//                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp0')),
// 'other'=>array('host'=>'host2','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
//                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp1')),
//    );

//���ָ����10�ű���ô
//����䵽��	host1: db_tmp0.tb_test_0,db_tmp0.tb_test_2,db_tmp0.tb_test_4,db_tmp0.tb_test_6,db_tmp0.tb_test_8,
//����䵽��	host2: db_tmp1.tb_test_1,db_tmp1.tb_test_3,db_tmp1.tb_test_5,db_tmp1.tb_test_7,db_tmp1.tb_test_9,

class TestClass extends Sooh\DB\Base\KVObj
{
	//ָ��ʹ��ʲôid����λ���ݿ�����
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'TestObj'.($isCache?'Cache':'');
	}
	//��Ի��棬�ǻ�������¾���ı������
	protected static function splitedTbName($n,$isCache)
	{
		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
		else return 'tb_test_'.($n % static::numToSplit());
	}
	//˵���ּ��ű�
	protected static function numToSplit(){return 10;}

	/**
	 * ˵��getCopyʵ�ʷ��ص��࣬ͬʱ����ֻ��һ�������ģ����Լ�д��
	 * @return TestClass
	 */
	public static function getCopy($userId)
	{
		return parent::getCopy(array('uid'=>$userId));
	}
	/**
	 * �Ƿ�����cache����
	 * cacheSetting=0��������
	 * cacheSetting=1�����ȴ�cache�����ÿ�θ��¶��ȸ���Ӳ�̱�Ȼ�����cache��
	 * cacheSetting>1�����ȴ�cache�����ÿ�θ����ȸ���cache������ﵽһ���������Ÿ���Ӳ�̱�
	 */
	protected function initConstruct($cacheSetting=0,$fieldVer='iRecordVerID')
	{
		return parent::initConstruct($cacheSetting,$fieldVer)
	}
}
```

#####����ʹ�ã�
```php
$oo = TestObj::getCopy(array('ID'=>11));
$oo->load();//Ҫִ��load, Ȼ��$oo->update()��֪����insert����update
$oo->reload();//loadִ�й�һ���Ժ��ٴε��ò��������ķ������ݿ⣬���Ҫǿ���ض����ݿ⣬����$oo->reload()
$oo->exists();//�ж���¼�Ƿ����
$oo->exists(fieldName);//�ж�ָ��fieldName�Ƿ������Чֵ
$oo->getField(fieldName);//��ȡָ��fieldName��ֵ�������null���ᶪ�쳣�����ֵ������null,��Ҫ˵������null:$oo->getField(fieldName, true);
$oo->setField(fieldName��fieldValue);//fieldValue��������飬�洢ʱ�ᱻת����json���������ݿ��������ʱ��ỹԭ������
$oo->db() ��ȡָ����¼���䵽�����ݿ�����ࣨ\Sooh\DB\Interfaces\All��
$oo->tbname() ��ȡָ����¼���䵽�����ݿ��
$oo->lock() �����ü�¼��Ĭ����3�꣩��ֹ��������д�������Ҫ����ô�ã�������3�룺$oo->lock(3)��������ǵ���iRecordVerID��Ϊ���
$oo->unlock() ����
$oo->registerOn($callback,$evt)��ע������¼��ص�,Ŀǰ������KVOBJ::onAfterLoaded, KVOBJ::onBeforeSave, KVOBJ::onAfterSave, ��
$oo->update() ���µ����ݿ⣨�������ѡ��insert����update,��cache������ʽ��
```

#####��ѯ��أ�
```php
//����ص�����
function xxx(\Sooh\DB\Interfaces\All $db,$tb){}
//�������еı�����xxx(db,tbname);��10�ű��ͻ�ص�10��
TestClass::loop(xxx);

//�������б�ͳ�Ʒ��������ļ�¼������
$recordCount = $oo->loopGetRecordsCount($where);

//��ҳ��ȡ��¼(�и����ƣ���Ҫ��Ψһ����Ŀǰ֧�����2���ֶ���ɵ�Ψһ��):
//��һ����ʾ��һҳ������ʱ��������������ʱ��
$ret = $oo->loopGetRecordsCount(array('autoid'=>'sort','subkey'=>'rsort'),array('where'=>array()),$pager);
//���ص�$ret = array (lastPage=>array(), records=array());
//֮��Ĵ���Ҫ�ѷ��ص�lastPage��¼����
//ǰ��ҳ��ʱ��$oo->loopGetRecordsCount(array('autoid'=>'sort','subkey'=>'rsort'),$lastPage,$pager);
//ͬ����¼��lastPage
```