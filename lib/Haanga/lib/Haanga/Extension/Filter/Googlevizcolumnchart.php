<?php

class Haanga_Extension_Filter_GoogleVizColumnChart{
  public $is_safe = TRUE;
  static function main($obj, $varname){
  	$data = "";
  	$i = 0;
  	$j = 0;
  	$firstColumn = true;
    $options = array();
  	$names = explode(",", $varname);

  	$fieldCounter=0;
  	$varList = array();
  	foreach($names as $v){
  	  if(strpos($v,"=")){
  	    break;
  	  }
  	  $fieldCounter++;
  	  $columnType = 'number';
  	  if($firstColumn){
  	  	$columnType = 'string';
  	  	$firstColumn = false;
  	  }
  	  array_push($varList, $v);
  	  $data .= "        data.addColumn('".$columnType."', '".$v."');\n";
  	}

  	foreach($obj as $k){  	  
  	  foreach($varList as $v){
  	    $value = ($j==0)?"'".$k->$v->value."'":$k->$v->value;
  	  	$data .="        data.setCell($i, $j, ".$value.");\n";
  	  	$j++;
  	  } 
  	  $i++;
  	  $j=0;
  	}

  	
  	//Getting options
  	$options['height'] = 400;
  	$options['width'] = 400;
    for($z=$fieldCounter; $z < count($names); $z++){
      $pair = explode("=", $names[$z]);
      $key = trim($pair[0], "\" '");
      $value = trim($pair[1], "\" '");
      $options[$key] = $value;     
    }

  	$divId = uniqid("columnchart_div");
  	$pre = "<div id='".$divId."'></div><script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
    var options_$divId = ".json_encode($options)."; 
    google.load('visualization', '1', {packages:['corechart']});
    google.setOnLoadCallback(drawChart);
    function drawChart() {
    var data = new google.visualization.DataTable();
    data.addRows(".$i.");\n
".$data."    var columnchart = new google.visualization.ColumnChart(document.getElementById('".$divId."'));
columnchart.draw(data, options_$divId);
    }
    </script>";
    return $pre;
  }
}
