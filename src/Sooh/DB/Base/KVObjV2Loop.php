<?php
namespace Sooh\DB\Base;

/**
 * Description of KVObjV2Loop
 *
 * @author wang.ning
 */
class KVObjV2Loop {
    /**
     * 
     * @var \Sooh\DB\Pager
     */
    public $pager;
    public $where;
    public $fields;
    public $sort_field_type;
    protected $whereOriginal=-1;
    protected $lastPage;
    public $records=[];
    public $sortGroup='';
    /**
     * 获取记录条数
     * @param \Sooh\DB\Interfaces\All $db
     * @param string $tb
     */
    public function kvoGetRecordCount($db,$tb)
    {
        $this->records[]=$db->getRecordCount($tb,$this->where);
    }

    /**
     * 获取记录集
     * @param \Sooh\DB\Interfaces\All $db
     * @param string $tb
     */    
	public function kvoGetRecordsStd($db,$tb)
	{
	    $rs = $db->getRecords($tb,$this->fields,$this->where,$this->sortGroup,$this->pager->page_size,$this->pager->rsFrom());
	    foreach($rs as $r){
	        $this->records[]=$r;
	    }
	}
	/**
	 * 分页的前期处理,靠管理where条件完成具体第几页，pager类始终是要第一页
	 * 修改pager,使pageid是上次的页
	 * 返回跟lastpage相比走几步
	 * 
	 * @param mixed $whereOrLastpage
	 * @param \Sooh\DB\Interfaces\All $db
	 */
	
	public function kvoPagePrepare($targetPage,$whereOrLastpage,$db)
	{
        if(is_string($whereOrLastpage)){
            $whereOrLastpage = json_decode($whereOrLastpage,true);
            if(!is_array($whereOrLastpage) || !isset($whereOrLastpage['_last_'])){
                throw new \ErrorException('arg-whereOrLastpage-is-not-lastpage');
            }
            $this->whereOriginal=$whereOrLastpage['where'];
            $this->lastPage = $whereOrLastpage;
        }else{
            $this->whereOriginal=$whereOrLastpage;
            $this->lastPage=['_last_'=>[]];
        }
        $lastPageId = $this->lastPage['_last_']['_pageid_']-0;
        $strSort = ' ';
//echo "[start[[lastPageId_old=$lastPageId vs targetPage=$targetPage]]]\n";
        if($targetPage==$lastPageId){//刷新当前页或首次查询
            $this->pageStep=0;
            foreach($this->sort_field_type as $field=>$type){
                $strSort.="$type $field ";
            }
            
            $this->sortGroup = $strSort;
            if($lastPageId==0){//首次
                $this->where = $this->whereOriginal;
            }else{//刷新最后一页
                $this->where = $this->loopGetRecordsPage_buildWhere($db);
            }

            return 1;
        }elseif($targetPage>$lastPageId){//正向翻页
            $this->pageStep=1;
            foreach($this->sort_field_type as $field=>$type){
                $strSort.="$type $field ";
            }
            $this->sortGroup = $strSort;
            $this->where = $this->loopGetRecordsPage_buildWhere($db);

//             $steps = $this->pager->page_id_zeroBegin-$pageIdOld;
//             $this->pager->init($this->pager->total, $this->pager->pageid()+$pageIdOld-$this->pager->page_id_zeroBegin);
//             for($i=0;$i<$steps;$i++){
//                 $this->pager->init($this->pager->total, $this->pager->pageid()+1);
//                 $this->where = $this->loopGetRecordsPage_buildWhere($whereOrLastpage);
//                 $this->sortGroup = $strSort;
//                 $ret = static::loopGetRecordsPage_oneStep($this->sort_field_type, $whereOrLastpage, $this->pager,$pageForward,$strSort);
//                 $whereOrLastpage = $ret['lastPage'];
//             }
            return $targetPage-$lastPageId;
        }else{//反向翻页
            $revertSort=array('sort'=>'rsort','rsort'=>'sort');
            $this->pageStep=-1;
            foreach($this->sort_field_type as $field=>$type){
                $strSort.=$revertSort[$type]." $field ";
            }
            $this->sortGroup = $strSort;
            $this->where = $this->loopGetRecordsPage_buildWhere($db);
//             $steps = $pageIdOld-$this->pager->page_id_zeroBegin;
//             $this->pager->init($this->pager->total, $this->pager->pageid()+$pageIdOld-$this->pager->page_id_zeroBegin);
//             for($i=0;$i<$steps;$i++){
//                 $this->pager->init($this->pager->total, $this->pager->pageid()-1);
//                 $this->where = $this->loopGetRecordsPage_buildWhere($whereOrLastpage);
//                 $this->sortGroup = $strSort;
//                 $ret = static::loopGetRecordsPage_oneStep($this->sort_field_type, $whereOrLastpage, $this->pager,$pageForward,$strSort);
//                 $whereOrLastpage = $ret['lastPage'];
//             }
            return $lastPageId-$targetPage;
        }
	}
	public $pageStep=1;
	/**
	 * 获取分页的结果
	 * @return array lastPage
	 */
	public function kvoAfterOnePageLoaded($pageIdReal)
	{
	    $records = $this->loopGetRecordsPage_sortGetPage($this->pageStep);
	    
	    $news = array();
	    if(!empty($records)){
	        reset($records);
	        $firstRow =  current($records);
	        $endRow = end($records);
	        foreach ($this->sort_field_type as $k=>$r){
	            $news['_'.$k.'_'] = array($firstRow[$k],$endRow[$k]);
	        }
	    }else{
	        foreach ($this->sort_field_type as $k=>$r){
	            $news['_'.$k.'_'] = array();
	        }
	    }
	    foreach($news as $k=>$v){
	        //$this->lastPage['_pre_'][$k] = $this->lastPage['_last_'][$k];
	        $this->lastPage['_last_'][$k]=$v;
	    }
	    if($this->whereOriginal!==-1){
	        $this->lastPage['where'] = $this->whereOriginal;
	    }
	    $this->lastPage['_last_']['_pageid_']=$pageIdReal;
	    $this->records = $records;
	    //$lastPage['_fields_'] = $sort_field_type;
	    return json_encode($this->lastPage);
	}

	/**
	 * 
	 * @param mixed $lastPage
	 * @param \Sooh\DB\Interfaces\All $db
	 */
	protected function loopGetRecordsPage_buildWhere($db){
	    $lastPage = $this->lastPage;
	    $sort_field_type = $this->sort_field_type;
		$sortMethod = array(
		    0=>array('sort'=>']','rsort'=>'['),//原地刷新
			-1=>array('sort'=>'<','rsort'=>'>'),//反向翻页
			1=>array('sort'=>'>','rsort'=>'<'),//正向翻页
		);
		$sorValIndex = array(
		    0=>0,//原地刷新
		    -1=>0,//反向翻页
		    1=>1,//正向翻页
		);
		if(sizeof($sort_field_type)==1){//单键
			$w = array();
			if(!empty($lastPage['_last_'])){
				$k = key($sort_field_type);
				$sort = current($sort_field_type);
				$w[$k.$sortMethod[$this->pageStep][$sort]]=$lastPage['_last_']['_'.$k.'_'][$sorValIndex[$this->pageStep]];
				//echo ">>$k $sort $pageForward ".$sortMethod[$pageForward][$sort]." ".$lastPage['_'.$k.'_'][$pageForward]."\n";
				//echo ">>1>>".json_encode($w)."\n";
			}
			if(is_array($lastPage['where'])){
				$w = $this->loopGetRecordsPage_mergeWhere($w,$lastPage['where']);
				//echo ">>2>>".json_encode($w)."\n";
			}
			//echo ">>3>>".json_encode($w)."\n";
			$where=$w;
		}else{//双键, 【=，>】，【>,null】
			$wEq = array();
			$wCmp = array();
			if(!empty($lastPage['_last_'])){
				$k1 = key($sort_field_type);
				$sort1 = current($sort_field_type);
				array_shift($sort_field_type);
				$k2 = key($sort_field_type);
				$sort2 = current($sort_field_type);
				
				$wEq[$k1.'=']=$lastPage['_last_']['_'.$k1.'_'][ $sorValIndex[$this->pageStep]];
				$wEq[$k2.$sortMethod[$this->pageStep][$sort2]]=$lastPage['_last_']['_'.$k2.'_'][$sorValIndex[$this->pageStep]];
				
				$wCmp[$k1.$sortMethod[$this->pageStep][$sort1]]=$lastPage['_last_']['_'.$k1.'_'][$sorValIndex[$this->pageStep]];
				if(is_array($lastPage['where'])){
					$wEq = $this->loopGetRecordsPage_mergeWhere($wEq,$lastPage['where']);
					$wCmp = $this->loopGetRecordsPage_mergeWhere($wCmp,$lastPage['where']);
				}
				$where = $db->newWhereBuilder();
				$where->init('OR');
				$where->append(null,$db->newWhereBuilder()->append($wEq));
				$where->append(null,$db->newWhereBuilder()->append($wCmp));
				$where = $where->end();
			}else{
				if(is_array($lastPage['where'])){
					$where = $lastPage['where'];
				}else{
					$where=$wEq;
				}
			}
		}
		//echo "WHERE=";
		//var_dump($where);
		return $where;
	}
	protected function loopGetRecordsPage_mergeWhere($sys,$usr)
	{
		foreach($usr as $k=>$v){
			if(isset($sys[$k])){
				$cmp = substr($k,-1);
				if($v>$sys[$k]){
					$max=$v;
					$min=$sys[$k];
				}else{
					$max=$sys[$k];
					$min=$v;
				}
				if($cmp=='['){
					$sys[$k]=$min;
				}elseif($cmp=='<'){
					$sys[$k]=$min;
				}elseif($cmp=='>'){
					$sys[$k]=$max;
				}elseif($cmp==']'){
					$sys[$k]=$max;
				}else{
					throw new \ErrorException('where merge conflict:'.$k);
				}
			}else{
				$sys[$k]=$v;
			}
		}
		return $sys;
	}
	protected function loopGetRecordsPage_sortGetPage($pageForward)
	{
		$all=array();
		switch (sizeof($this->sort_field_type)){
			case 1:
				$tmp=array();
				$k = key($this->sort_field_type);
				foreach($this->records as $r){
					$tmp[ $r[ $k ] ][] = $r;
				}
				if(current($this->sort_field_type)==='sort'){
					ksort ($tmp);
				}else{
					krsort ($tmp);
				}
				foreach($tmp as $r){
					$all = array_merge($all,$r);
				}
				break;
			case 2:
				$tmp=array();
				$sort_key=  array_keys($this->sort_field_type);
				foreach($this->records as $r){
					$tmp[ $r[ $sort_key[0] ] ][ $r[ $sort_key[1] ] ] = $r;
				}
				$cmp=$this->sort_field_type[$sort_key[0]];
				if($cmp==='sort'){
					ksort ($tmp);
				}else{
					krsort ($tmp);
				}

				$cmp = $this->sort_field_type[$sort_key[1]];
				if($cmp==='sort'){
					foreach($tmp as $rs1){
						ksort ($rs1);
						$all = array_merge($all,$rs1);
					}
				}
				else {
					foreach($tmp as $rs1){
						krsort ($rs1);
						$all = array_merge($all,$rs1);
					}
				}
				break;
			default:
				throw new \ErrorException('sort field needs to be 1-2, given:'.  json_encode($this->sort_field_type));
		}
		$pageSize = $this->pager->page_size;
		if($this->pageStep>=0){
			while(sizeof($all)>$pageSize){
				array_pop ($all);
			}
		}else{
			while(sizeof($all)>$pageSize){
				array_shift ($all);
			}
		}
		return $all;		
	}

	

}
