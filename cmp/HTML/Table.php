<?php
namespace Sooh\HTML;
/**
 * Description of table
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Table {
	/**
	 * 
	 * @param array $rowsWithHeader
	 * @param mix $headerAtTopOrHTMLForm boolean or string('<form ...>')
	 * @return string
	 */
	public static function std($rowsWithHeader,$headerAtTopOrHTMLForm=true)
	{
		$str= "<table border=0 cellspacing=0 cellpadding=5 class=\"tableStd\">";
		
		if($headerAtTopOrHTMLForm!==false){
			$str.= "<tr><th>".implode('</th><th>',$rowsWithHeader[0])."</th></tr>";
			unset($rowsWithHeader[0]);
			
			if(is_string($headerAtTopOrHTMLForm)){
				foreach($rowsWithHeader as $r){
					$str.= "<tr>$headerAtTopOrHTMLForm<td>".implode('</td><td>',$r)."</td></form></tr>";
				}
			}else{
				foreach($rowsWithHeader as $r){
					$str.= "<tr><td>".implode('</td><td>',$r)."</td></tr>";
				}
			}
		}else{
			foreach($rowsWithHeader as $r){
				$str.= "<tr><th>".$r[0]."</th><td>";
				unset($r[0]);
				$str.= implode('</td><td>',$r)."</td></tr>";
			}
		}
		
		$str.= "</table>";
		return $str;
	}
}
