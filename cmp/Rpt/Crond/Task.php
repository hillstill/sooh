<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 定时任务基类
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Task {
	const continueNextMinute = 'continueNextMinute';
	//put your code here
	public function priority(){return 1;}
	public function thread(){return 'default';}
	public function intro(){return 'this task is for xxx';}
	public function free(){}
	public function run(){}
	public function init(){}
	
	protected static $staticVars=array();
}
