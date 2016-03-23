Sooh\DB
===================
## ���ú�����ʹ�÷�ʽ

����˵��һ��(��mysqlΪ��) 
######select * from tb where a=1 and b=1 limit 0,1
```php
$db->getRecord('tb','*', array('a'=>1,'b='=>1));
```

######select * from tb where a=array(1,2) and b is null order by a ,b desc group by c ,d limit 0,1
```php
$db->getRecord('tb','*', array('a'=>array(1,2),'b'=>null),'sort a rsort b group c group d');
```

######ÿҳ10����¼����ȡ����ҳ��select * from tb where a>10 limit 10,20
```php
$db->getRecords('tb','*', array('a>'=>10),null,10,20);
```

######��ȡpair:array('a'=>1,'b'=>1)��select f1,f2 from tb where f1 in ('a','b') and f2=1
```php
$db->getPair('tb','f1','f2', array('f1'=>array('a','b'),'f2'=>1));
```

######��ȡһ�У�2000-1-1��ע����û���userid�б�array(u1,u2,u3)����select uid from tb where ymdreg=20000101;
```php
$db->getCol('tb','uid', array('ymdreg'=>20000101));
```

######��ȡassoc��2000-1-1��ע����û�����Ϣ��array(uid1=>array(gender=>'f',age=>21),uid2=>array(gender=>'m',age=>22)))
```php
$db->getAssoc('tb','uid', 'gender,age', array('ymdreg'=>20000101));
```

######��ȡ��ֵ��userid=1�����գ���select birthday from tb where userid=1;
```php
$db->getOne('tb','birthday', array('userid'=>1));
```

######insert into tb (a,b) values(1,2)
```php
$db->addRecord('tb',array('a'=>1,'b'=>1));
//����е����ֶΣ����ﷵ�ص�Ӧ����lastInsertId,���򷵻�true
```

######delete from tb where a=1
```php
$db->delRecords('tb',array('a'=>1));
```

######update tb tb set b=1 where a=1
```php
$db->updRecords('tb',array('b'=>1),array('a'=>1));
```

######���ȭ��update tb tb_b set b=1 where a in (select id from tb_a)
```php
$db->updRecords('tb_b',array('b'=>1),array('a'=>$db->getCol('tb_a','id')));
```


######�Զ����ѯ show variables like '%char%'
```php
$result = $db->execCustom(array('sql'=>"show variables like '%char%'"));
$rs = $db->fetchAssocThenFree($result);
```


######����Ԥ���쳣���������ʱ���ּ���ͻ����update��
try{
	\Sooh\DB\Broker::errorMarkSkip(\Sooh\DB\Error::duplicateKey);
	$db->addRecord('tb_user',array('uid'=>'123123123','phone'=>'12314123'));
}catch(\ErrorException){
	if(\Sooh\DB\Broker::errorIs(\Sooh\DB\Error::duplicateKey)){
		$db->updRecords('tb_user',array('phone'=>'12314123'),array('uid'=>'123123123'));
	}else{
		error_log(Sooh\DB\Broker::lastCmd());
	}
}