<? function distabentry($row,$mode='default') { global $PROJECTS; // ----------------------- edit table entry ?>
  <TR>
  <TD WIDTH=5 VALIGN=top><A HREF="/<?= $row['id'] ?>"><?= $row['id'] ?></A></TD>

  <? if (!PM("/filename/",$mode)) { ?>

  <TD WIDTH=5 VALIGN=top style='font-size:80%;padding-top:3px;'><?= $PROJECTS[$row['project']] ?></TD>

	<? } ?>
  <? if (PM("/filename/",$mode)) { ?>
    <TD VALIGN=top ALIGN=left><?= $row['filename'] ?></TD>

  <? } ?>

  <? if ($mode==='json1') { ?>
    <TD VALIGN=top ALIGN=center><?= $row['s_json1'] ?></TD>
  <? } ?>
  <? if ($mode==='json2') { ?>
    <TD VALIGN=top ALIGN=center><?= $row['s_json2'] ?></TD>
  <? } ?>
  <? if ($mode==='json12') { ?>
    <TD VALIGN=top ALIGN=center><?= $row['s_json1'] ?></TD>
    <TD VALIGN=top ALIGN=center><?= $row['s_json2'] ?></TD>
  <? } ?>
  <TD VALIGN=top><?= AL($row['f0']) ?></TD>
  <? if (!PM("/filename/",$mode)) { ?>
		<TD VALIGN=top><?= AL($row['f1']) ?></TD>
  <? } ?>
  <TD VALIGN=center ALIGN=center style="white-space:nowrap">
  <?  if (chkeditaccess($row)) { ?>
    <A HREF="/<?= $row['id'] ?>/edit" CLASS='icon'>
      <IMG HEIGHT=14 TITLE='Edit' SRC="/img/pencil.png"></A>
    <A HREF="/<?= $row['id'] ?>/copy" CLASS='icon'>
      <IMG HEIGHT=14 TITLE='Copy' SRC="/img/copy.png"></A>
    <? if ($mode==='bookmark') { ?>
    	<A HREF="/delbookmark/<?= $row['bid'] ?>" CLASS='icon'>
      <IMG HEIGHT=14 TITLE='Delete' SRC="/img/trash.png"></A>
    <? } else { ?>
      <A HREF="/<?= $row['id'] ?>/bookmark" CLASS='icon'>
      <IMG HEIGHT=14 TITLE='Bookmark' SRC="/img/bookmark.png"></A>
    <? } ?>
    <? if ($mode==='default') { ?>
    	<A HREF="/<?= $row['id'] ?>/delete"
      	onclick="return confirm('Delete this entry (<?=$row['id']?>) ?')" CLASS='icon'>
       	<IMG HEIGHT=14 TITLE='Delete' SRC="/img/trash.png"></A>
    <? } ?>
  <? } ?>
  <? if ($row['fixed']==1) { ?>
    <IMG HEIGHT=14 TITLE='' SRC="/img/lock_grey.png">
  <? } ?>
  </TD>
  </TR>
<? } ?>
<? function userinfo() { global $USER,$ROLENAMES,$USERTYPE,$ROLENAMES,$ROLE; // -------------------- userinfo ?>
  <? if(isset($_SESSION['username'])) { ?>
    <? if($_SESSION['usertype']!=='oa') { ?>
      <I>User: <? echo $USER ?></I>
      &nbsp;
      <I>Role: <? echo $ROLENAMES[$ROLE]  ?></I>
      &nbsp;
      <I>Type: <? echo $USERTYPE  ?></I>
    <? } else { ?>
      &nbsp;
      <I>Role: <? echo $ROLENAMES[$ROLE]  ?></I>
    <? } ?>
  <? } ?>
<? } ?>
<?

  function saveprefs($name,$prefs,$type='') { global $DB,$USER; // ---------------------------------- save prefs
    mydo($DB,"DELETE FROM prefs WHERE user='$USER' AND type='$type' AND name='".
             mysqli_escape_string($DB,$name)."'");

    mydo($DB,"INSERT INTO prefs SET pid=0,user='$USER',ts=now(),".
     "type='$type',name='".mysqli_escape_string($DB,$name)."',".
     "prefs='".json_encode($prefs,JSON_UNESCAPED_UNICODE)."'");
    return(mysqli_insert_id($DB));
  }
  
  function loadprefs($id,$type='') { global $DB,$USER; // ------------------------------------------- load prefs
    $row=myrow($DB,"SELECT * from prefs WHERE pid=$id AND type='search'",1);
    return(json_decode($row['prefs'],1));
  }
  
	function AL($str) { // ----------------------------------------------------------------------------- add links
		global $IDPAT;
	  $str=PR("/#([A-Za-z]+\w+)\b/","<SPAN id=tags><A HREF=\"/tag/$1\">#$1</A></SPAN>",$str);
    $str=PR("/\b(https*:\/\/[^\b]+)\b/","<A HREF=\"$1\">$1</A>",$str);
    $str=PR("/\b([$IDPAT]\d\d\d\d\d+)\b/","<A HREF=\"/$1\">$1</A>",$str);
    $str=PR("/\/search\/(\d+)/","<A HREF=\"/search/$1\">/search/$1</A>",$str);
		return($str);
	}

  function alog($id,$docid,$action,$comment="") { // ----------------------------------------------- log history
    global $ROLE,$USER,$DB,$TAG;

    $ip=$_SERVER['REMOTE_ADDR'];
    $src=$_SERVER['REQUEST_URI'];
    mydo($DB,"INSERT INTO log SET id='$id',docid=$docid,tag='$TAG',user='$USER',role=$ROLE,".
         "ip='$ip',ts=now(),src='$src',action='$action',comment='".
          mysqli_real_escape_string($DB,$comment)."'",1);
	  
	  $tmp="LOG: $action, $id";
	  if ($docid!=0) $tmp.="/$docid";
    if (!PM("/^\s*$/",$comment)) $tmp.=", $comment";

	  phplog($tmp,'log');	
  }

  function idencode($cid,$did) { // --------------------------------------------------------- encode catid/docid
    return gmp_strval(gmp_init(sprintf("%02d%08d",intval(rand(11,99)),$cid),10),62).
           gmp_strval(gmp_init(sprintf("%02d%08d",intval(rand(11,99)),$did),10),62);
  }
  
  function iddecode($cdid) { // ------------------------------------------------------------- decode catid/docid
    $c= gmp_strval(gmp_init( substr($cdid,0,6), 62), 10);
    $d= gmp_strval(gmp_init( substr($cdid,-6), 62), 10);
    return([intval(substr($c,-8)),intval(substr($d,-8))]);
  }

  function catidfromid($id) { // ------------------------------------------------------------- get catid from id
    global $DB,$ACCESSQL,$TCHR;
    if (preg_match("/([A-Z])(\d+)/",SU($id),$M)) {
      $key = array_search ($M[1], $TCHR);
      $sql="SELECT * FROM cat WHERE typeid=$M[2] and type='$key'";
      if (!PM("/^\s*$/",$ACCESSQL)) $sql.=" AND $ACCESSQL";  
      $res=mydo($DB,$sql,1);
      $numrows =mysqli_num_rows($res);
      if ($numrows==1) {
        $row = mysqli_fetch_assoc($res);
        
        return $row['catid'];   
      }
    } 
    return -1;
  }

  function idfromcatid($catid) { // ---------------------------------------------------------- get id from catid
    global $DB,$ACCESSQL,$TCHR;
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL";  
    $res=mydo($DB,$sql,1);
    $numrows=mysqli_num_rows($res);
    if ($numrows==1) {
      $row = mysqli_fetch_assoc($res);
      return $TCHR[$row['type']].$row['typeid'];
    }
    return "";
  }

  function id62($length = 16) { // generate base 62 uniq id
    return substr(gmp_strval(gmp_random_bits(8*$length),62),-$length);
  }

  function formsize($b) { // ------------------------------------------------------------------ format file size
    if ($b>1024*1024*1024*1024) {
      $s=sprintf("%.1f Tb",$b/1024/1024/1024/1024);
    } elseif ($b>1024*1024*1024) {
      $s=sprintf("%.1f Gb",$b/1024/1024/1024);
    } elseif ($b>1024*1024) {
      $s=sprintf("%.1f Mb",$b/1024/1024);
    } elseif ($b>1024) {
      $s=sprintf("%.1f Kb",$b/1024);
    } else {
      $s=sprintf("%.0f b",$b);
    }
    return $s;
  }

  function mydo($db,$sql,$quiet=0) { // ------------------------------------------------------------ mysql query
    if (!$quiet) phplog($sql,'sql');
    $res=mysqli_query($db,$sql);
    myerr($db,$sql);
    return $res;
  }
  
  function myrow($db,$sql) { // ------------------------------------------------- get one row mysql query result
    if (!$quiet) phplog($sql,'sql');
    $res=mysqli_query($db,$sql);
    myerr($db,$sql);
    $row=mysqli_fetch_assoc($res);
    return $row;
  }
  
  function myerr($db,$sql) { // --------------------------------------------------------------- show mysql error
    if (mysqli_errno($db)!=0) {
      echo '<BR><font color=#700099 style=\'font-size:12pt;\'><tt><B>MySQL error: </B>' .
           mysqli_error($db) . '</tt></font>';
      phplog(mysqli_error($db),'error');
      exit;
    }
  }

  function getpar($name) { // ------------------------------------------------------- get POST/GET value by name
    if (isset($_POST[$name])) return $_POST[$name];
    if (isset($_GET[$name])) return $_GET[$name];
    return '';
  }

  function shortstr($str,$len) { // --------------------------------------------------------------- short string
    if (strlen($str)>$len) {
      return substr($str,0,$len-2)."..";
    } else {
      return $str;
    }
  }

  function phplog($msg,$type='info',$debuglevel=1) { // ------------------------------------------ write php log
    global $PHPLOG,$TAG,$USER,$DEBUG;
    if ($DEBUG>=$debuglevel) {
    	$ip=$_SERVER['REMOTE_ADDR'];
    	$url=$_SERVER['REQUEST_URI'];
    	
    	if (PM("/^\s*$/",$msg)) {
    		error_log(date("Y-m-d H:i:s")."|$type|$TAG|$ip|$USER|$url\n", 3,$PHPLOG);
    	} else {
    		error_log(date("Y-m-d H:i:s")."|$type|$TAG|$ip|$USER|$url|$msg\n", 3,$PHPLOG);
  		}
  	}
  }

  function HSC($str) { // -------------------------------------------------------- shortcut for htmlspecialchars
    return htmlspecialchars($str);
  }

  function SU($str) { // --------------------------------------------------------------- shortcut for strtoupper
    return strtoupper($str);
  }

  function SL($str) { // --------------------------------------------------------------- shortcut for strtolower
    return strtolower($str);
  }

  function PM($pattern,$str) { // ------------------------------------------------------ shortcut for preg_match
    return preg_match($pattern,$str);
  }
  
  function PR($pattern,$replace,$str) { // ------------------------------------------- shortcut for preg_replace
    return preg_replace($pattern,$replace,$str);
  }

  function EM($str) { // --------------------------------------------------------------- check from empty string
    if (preg_match("/^\s*$/",$str)) return TRUE;
    else return FALSE;
  }
  
  function DF($str) { // ------------------------------------------------------------------------- date formater
    $t=$str;
    if (preg_match('/\b(\d+)\s*\.\s*(\d+)\s*\.\s*(\d+)\b/',$str,$m)) {
      if ($m[3]<100) $m[3]+=2000;
      $r=sprintf("%02d.%02d.%04d",$m[1],$m[2],$m[3]);
      $t=preg_replace('/\b(\d+)\s*\.\s*(\d+)\s*\.\s*(\d+)\b/', $r, $str);
    } else if (preg_match('/\b(\d+)\s*\.\s*(\d+)\b/',$str,$m)) {
      $r=sprintf("%02d.%02d.%04d",$m[1],$m[2],date("Y"));
      $t= preg_replace('/\b(\d+)\s*\.\s*(\d+)\b/', $r, $str);
    } 
  
    $r = array(  // ''=>'',
      'montag'=>'monday','dienstag'=>'tuesday','mittwoch'=>'wednesday','donnerstag'=>'thursday',
      'freitag'=>'friday','samstag'=>'saturday','sonntag'=>'sunday','mo'=>'monday','di'=>'tuesday',
      'mi'=>'wednesday','do'=>'thursday','fr'=>'friday','sa'=>'saturday','so'=>'sunday','stunden*'=>'hour',
      'erster'=>'first','zweiter'=>'second','dritter'=>'third','vierter'=>'fourth','fŸnfter'=>'fifth',
      'sechster'=>'sixth','siebenter'=>'seventh','achter'=>'eighth','neunter'=>'ninth','zehnter'=>'tenth',
      'elfter'=>'eleventh','zwšlfter'=>'twelfth','nŠchster*'=>'next','dez'=>'dec','letzter'=>'last',
      'vorheriger'=>'previous','dieser'=>'this','okt'=>'oct','gestern'=>'yesterday','heute'=>'today',
      'morgen'=>'tomorrow','jetzt'=>'now','januar'=>'January','februar'=>'February','mŠrz'=>'march',
      'mai'=>'may','juni'=>'june','juli'=>'july','oktober'=>'october','dezember'=>'december','tage*'=>'day',
      'monate*'=>'months','wochen*'=>'weeks','jahre*'=>'years','sekunden*'=>'sec','minuten*'=>'min'
    );
    
    foreach($r as $rf => $rt) $t=preg_replace("/\b$rf\b/i",$rt,$t);
  
    if (strtotime($t)) {
      $d = date("Y-m-d", strtotime($t));
      return $d;
    }
    return "";
  }

?>