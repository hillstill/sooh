Sooh\DB
===================
## where的构造使用方式

例子说明一切(以mysql为例) 

######select * from tb where a=1 and b=1 limit 0,1
```php
$db->getRecord('tb','*', array('a'=>1,'b='=>1));
```

######select * from tb where a=array(1,2) and b is null order by a ,b desc limit 0,1
```php
$db->getRecord('tb','*', array('a'=>array(1,2),'b'=>null),'sort a rsort b');
```

######select * from tb where a>1 and b>=2 limit 0,1
```php
$db->getRecord('tb','*', array('a>'=>1,'b]'=>2));
```

######select * from tb where a<1 and b<=2 limit 0,1
```php
$db->getRecord('tb','*', array('a<'=>1,'b['=>2));
```

######select * from tb where a<>1 and b like 'abc%' limit 0,1
```php
$db->getRecord('tb','*', array('a!'=>1,'b*'=>'abc%'));
```

######select * from tb where a=1 or b=1 limit 0,1
```php
$where = $db->newWhereBuilder();
$where->init('OR');
$where->append('a1', 1);
$where->append('a2', 1);
$db->getRecord('tb','*', $where);
```

######select * from db_addons.temp  where (a1='1' AND a2='2') OR c='1' limit 0,1
```php
$where = $db->newWhereBuilder();
$tmp = $db->newWhereBuilder();
$tmp->init('AND');
$tmp->append(array('a1'=>1,'a2'=>2));
$where->init('OR');
$where->append(null,$tmp);
$where->append('c', 1);
$db->getRecord('tb','*', $where);
```