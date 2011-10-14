#!/usr/bin/env php
<?php

$items_count = $total_size = 0;

$slabs = execute("stats slabs");

$ids = array();
foreach($slabs as $s){
  if(strpos($s, "STAT") === 0){
    list($first,) = explode(":", $s);
    $first = str_replace("STAT ", "", $first);
    $id = (int)$first;
    if($id > 0){
      $ids[$id] = true;
    }
  }
}

$ids = array_keys($ids);
$output = array();
foreach($ids as $i){
  $keys = execute("stats cachedump $i 99999999");
  foreach($keys as $k){
    if(strpos($k, "ITEM") === 0){
      $item = str_replace("ITEM ", "", $k);
      $item_parts = explode(" ", $item);
      
      $items_count++;
      $o = process_item($item_parts[0]);
      $output[] = $o;
      show_line(str_pad($o['size'], 15, " ")." ".$o['key']);
      
    }
  }
}

show_line('');
show_line('');
show_line('');

show_line("ITEM COUNT : $items_count");
show_line("TOTAL SIZE: $total_size, " . bsize($total_size));

//echo execute("stats cachedump 32 10");

function process_item($i){
  $name = $i;
  $content = sendMemcacheCommand("get $i");
  if(isset($content['VALUE'])){
    $size = ($content['VALUE'][$i]['stat']['size']);
    global $total_size;
    $total_size += $size;

  }else{
    $size = "?";
  }
  return array(
    "size" => $size,
    "key" => $i
  );
}

function execute($q){
  $out = array();
  $raw = shell_exec('(echo "'.$q.'" && echo "quit") | nc 127.0.0.1 11211');
  $out = explode("\n", $raw);
  return $out;
}

function show_line($l){
  echo $l."\n";
}

function bsize($s) {
	foreach (array('','K','M','G') as $i => $k) {
		if ($s < 1024) break;
		$s/=1024;
	}
	return sprintf("%5.1f %sBytes",$s,$k);
}

function sendMemcacheCommand($command){

	$s = @fsockopen('127.0.0.1',11211);
	if (!$s){
		die("Cant connect to:".'127.0.0.1:11211');
	}

	fwrite($s, $command."\r\n");

	$buf='';
	while ((!feof($s))) {
		$buf .= fgets($s, 256);
		if (strpos($buf,"END\r\n")!==false){ // stat says end
		    break;
		}
		if (strpos($buf,"DELETED\r\n")!==false || strpos($buf,"NOT_FOUND\r\n")!==false){ // delete says these
		    break;
		}
		if (strpos($buf,"OK\r\n")!==false){ // flush_all says ok
		    break;
		}
	}
    fclose($s);
    return parseMemcacheResults($buf);
}

function parseMemcacheResults($str){
    
	$res = array();
	$lines = explode("\r\n",$str);
	$cnt = count($lines);
	for($i=0; $i< $cnt; $i++){
	    $line = $lines[$i];
		$l = explode(' ',$line,3);
		if (count($l)==3){
			$res[$l[0]][$l[1]]=$l[2];
			if ($l[0]=='VALUE'){ // next line is the value
			    $res[$l[0]][$l[1]] = array();
			    list ($flag,$size)=explode(' ',$l[2]);
			    $res[$l[0]][$l[1]]['stat']=array('flag'=>$flag,'size'=>$size);
			    $res[$l[0]][$l[1]]['value']=$lines[++$i];
			}
		}elseif($line=='DELETED' || $line=='NOT_FOUND' || $line=='OK'){
		    return $line;
		}
	}
	return $res;

}

?>
