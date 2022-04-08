<?
  include_once('init.php');include_once('header.php'); 

  $FSM=4;
  
  $searchmode=getpar('searchmode');
  $savesearchas=getpar('savesearchas');

  if ($searchmode==='RESET') {                                                              // reset search form
    unset($_SESSION['currenttype']);
    unset($_SESSION['currentproject']);
    unset($_SESSION['s_json1']);
    unset($_SESSION['s_json2']);
    $_SESSION['s_op']='AND';
    unset($_SESSION['s_usr']);
    unset($_SESSION['s_all']);
    unset($_SESSION['s_id']);
    unset($_SESSION['s_fn']);
    for ($fn=1;$fn<=$FSM;$fn++) {
      unset($_SESSION["s_key$fn"]);
      unset($_SESSION["s_field$fn"]);
      $_SESSION["s_fieldop$fn"]='AND';
    }
    $_SESSION['currentoffset']=0;
    header("Location: /search");exit;
  }
  
  $pid=getpar('pid'); 

  if (PM("/^\d+$/",$pid)) {                                                       // load search form from prefs
    $S=loadprefs($pid,'search');
    for ($fn=1;$fn<=$FSM;$fn++) {
      $_POST["s_key$fn"]   =$S['s_key'][$fn];
      $_POST["s_field$fn"] =$S['s_field'][$fn];
      $_POST["s_fieldop$fn"]    =$S['s_fieldop'][$fn];
    }
    foreach (array('type','project','s_op','s_json1','s_json2','s_usr','s_all','s_id','s_fn') as $val) {
      $_POST[$val]=$S[$val];
    }
    $TYPE=$S['type'];
    $PROJECT=$S['project'];
  }
  
  if (!isset($_SESSION['currenttype'])) $_SESSION['currenttype']='ALL';
  if (!PM("/^\s*$/",getpar('type'))) $_SESSION['currenttype']=SU(getpar('type'));
  $TYPE=$_SESSION['currenttype'];
  
  if (!isset($_SESSION['currentproject'])) $_SESSION['currentproject']='ALL';
  if (!PM("/^\s*$/",getpar('project'))) $_SESSION['currentproject']=getpar('project');
  $PROJECT=$_SESSION['currentproject'];

  foreach (array('s_op','s_json1','s_json2','s_usr','s_all','s_id','s_fn') as $val) {
    if (isset($_POST[$val])) $_SESSION[$val]=getpar($val);
    $S[$val]=$_SESSION[$val];
  }

  if ($S['s_op']!=='AND' && $S['s_op']!=='OR') $S['s_op']='AND';

  for ($fn=1;$fn<=$FSM;$fn++) {
    if (isset($_POST["s_key$fn"])) $_SESSION["s_key$fn"]=getpar("s_key$fn");
    $S['s_key'][$fn]=$_SESSION["s_key$fn"];

    if (isset($_POST["s_field$fn"])) $_SESSION["s_field$fn"]=getpar("s_field$fn");
    $S['s_field'][$fn]=$_SESSION["s_field$fn"];

    if (isset($_POST["s_fieldop$fn"])) $_SESSION["s_fieldop$fn"]=getpar("s_fieldop$fn");
    $S['s_fieldop'][$fn]=$_SESSION["s_fieldop$fn"];

    if (PM("/^\s*$/",$S['s_field'][$fn])) $S['s_field'][$fn]=$DEFSEARCHFIELD[$fn];
  }

  if (!PM("/^\s*$/",$savesearchas)) {
    $S['project']=$PROJECT;
    $S['type']=$TYPE;
    saveprefs($savesearchas,$S,'search');
  } 
  
  $MS='';
  $res=mydo($DB,"SELECT * FROM prefs WHERE user='$USER' ORDER BY name",1);
  while($row = mysqli_fetch_assoc($res)) { 
   $MS.="<A HREF=\"/search/".$row['pid']."\">".$row['name']."</A>, ";
  }
  $MS=PR("/,\s*$/","",$MS);

  foreach($TYPES as $type) {
    foreach($FMAP[$type] as $n => $f) {
      $ALLFIELDS[$f[0]]=1;
      for ($fn=1;$fn<=$FSM;$fn++) {
        if ($f[0]===$S['s_field'][$fn] && !PM("/^\s*$/",$S['s_key'][$fn]) ) {
          $fieldsql[$fn].="(f$n LIKE '%".$S['s_key'][$fn]."%' AND type='$type') OR ";
        }
      }
    }
  }
  ksort($ALLFIELDS);

  for ($n=1;$n<=$FSM;$n++) {
    if (!PM("/^\s*$/",$fieldsql[$n]) ) $fieldsql[$n]="(".PR("/\s*OR\s*$/","",$fieldsql[$n]).")";
  }

  $page=getpar('page');
  if (PM("/^\s*$/",$_SESSION['currentoffset'])) $_SESSION['currentoffset']=0;

  if ($page==='NEXT') $_SESSION['currentoffset']+=100;
  else if ($page==='PREV') $_SESSION['currentoffset']-=100;
  
  if (getpar('postform')==1) {
    header("Location: /search");
    exit;
  }

    $postsql="";
    if (!PM("/^ALL$/",$TYPE)) $postsql.=" AND UPPER(type)='$TYPE'";
    if (!PM("/^ALL$/",$PROJECT)) $postsql.=" AND project=".$PROJECTNAMES[$PROJECT];
    $postsql.=' ORDER BY cat.catid DESC';
    
    $selextra="";
    $searchsql="";

    $j1=preg_replace('/([\=\>\<])/', " $1 ", $S['s_json1']);
    $j1=preg_replace('/\>(\s+)\=/', ">=", $j1);
    $j1=preg_replace('/\<(\s+)\=/', ">=", $j1);
    $j1=preg_replace('/\s+/', " ", $j1);
    $selj1="";
    $searchj1="";
    if (preg_match("/^([^\s]+)\s+([\<\>\=]+)\s+([\d\.]+)/",$j1,$M)) {
      if ($M[2]==='=' || $M[2]==='<' || $M[2]==='>' || $M[2]==='<=' || $M[2]==='>=') { 
        $selj1=",json_value(jsondata, '\$.$M[1]') as s_json1";
        $searchj1="(json_value(jsondata, '\$.$M[1]')) $M[2] $M[3]";
      }
    }

    if (!PM("/^\s*$/",$S['s_json1']) && $searchj1==='') {
      $selj1=",json_extract(jsondata, '\$.".$S['s_json1']."') as s_json1";
      $searchj1="(json_extract(jsondata, '\$.".$S['s_json1']."') IS NOT NULL)";
    }   
    
    $j2=preg_replace('/([\=\>\<])/', " $1 ", $S['s_json2']);
    $j2=preg_replace('/\>(\s+)\=/', ">=", $j2);
    $j2=preg_replace('/\<(\s+)\=/', ">=", $j2);
    $j2=preg_replace('/\s+/', " ", $j2);
    $selj2="";
    $searchj2="";
    if (preg_match("/^([^\s]+)\s+([\<\>\=]+)\s+([\d\.]+)/",$j2,$M)) {
      if ($M[2]==='=' || $M[2]==='<' || $M[2]==='>' || $M[2]==='<=' || $M[2]==='>=') { 
        $selj2=",json_value(jsondata, '\$.$M[1]') as s_json2";
        $searchj2="(json_value(jsondata, '\$.$M[1]')) $M[2] $M[3]";
      }
    }

    if (!PM("/^\s*$/",$S['s_json2']) && $searchj2==='') {
      $selj2=",json_extract(jsondata, '\$.".$S['s_json2']."') as s_json2";
      $searchj2="(json_extract(jsondata, '\$.".$S['s_json2']."') IS NOT NULL)";
    }   

    if (!PM("/^\s*$/",$selj1)) $selextra.=$selj1;
    if (!PM("/^\s*$/",$selj2)) $selextra.=$selj2;

    $jmode="";

    if (!PM("/^\s*$/",$searchj1) && !PM("/^\s*$/",$searchj2)) {
      $searchj="($searchj1 ".$S['s_op']." $searchj2)";
      $jmode="json12";
    } elseif (!PM("/^\s*$/",$searchj1)) { 
      $searchj="$searchj1";
      $jmode="json1";
    } elseif (!PM("/^\s*$/",$searchj2)) { 
      $searchj="$searchj2";
      $jmode="json2";
    }

    if (!PM("/^\s*$/",$searchj))  $searchsql.=" AND ($searchj)";
    
    if (!PM("/^\s*$/",$S['s_usr'])) {
      $searchsql.=" AND (user='".$S['s_usr']."')";
    }   
    
    if (!PM("/^\s*$/",$S['s_all'])) {
      
      $tmp="";
      for ($i=0;$i<$FMAX;$i++) $tmp.="f$i LIKE '%".$S['s_all']."%' OR ";
      $tmp=PR("/\s*OR\s*$/","",$tmp);
      
      $searchsql.=" AND ($tmp)";
    }   

    if (!PM("/^\s*$/",$S['s_id'])) {
      
      if (PM("/^\s*\d+\s*$/",$S['s_id'])) {
        $searchsql.=" AND (catid=".$S['s_id']." OR typeid=".$S['s_id'].")";
      } else if (preg_match("/^\s*([A-Z])\s*(\d+)\s*$/",SU($S['s_id']),$M)) {
        $searchsql.=" AND (catid=".catidfromid($M[1].$M[2]).")";
      }
    }   
    
    $FX='';
    for ($n=1;$n<$FSM;$n++) {
      if ($S['s_fieldop'][$n]==='AND') $FX.="1";
      else $FX.="0";
    }

    for ($n=1;$n<=$FSM;$n++) if (!PM("/^\s*$/",$fieldsql[$n])) $fieldsearch.="F$n {$S['s_fieldop'][$n]} ";
    $fieldsearch=PR("/\s*AND\s*$/","",$fieldsearch);
    $fieldsearch=PR("/\s*OR\s*$/","",$fieldsearch);

    for ($n=$FSM-1;$n>=1;$n--) {
      $pat="";
      for ($j=1;$j<=$n;$j++) $pat.="F\d OR ";
      $pat.="F\d";
      $fieldsearch=PR("/($pat)/","($1)",$fieldsearch);
    }

    for ($n=1;$n<=$FSM;$n++) $fieldsearch=PR("/(F$n)/",$fieldsql[$n],$fieldsearch);

    if (!PM("/^\s*$/",$fieldsearch)) $searchsql.=" AND ($fieldsearch)";
    
    if (PM("/^\s*$/",$S['s_fn'])) {
      $sql="SELECT count(*) AS c FROM cat WHERE $ACCESSQL $searchsql $postsql";
    } else {
      $searchsql.=" AND (filename like '%".$S['s_fn']."%')";
      $sql="SELECT count(*) AS c FROM cat,docs WHERE cat.catid=docs.catid AND docs.rm=0 AND $ACCESSQL ".
           "$searchsql $postsql";
    }
    
    $res=mydo($DB,$sql);
    $row = mysqli_fetch_assoc($res); // << hä
    $numrows=$row['c'];

    if ($_SESSION['currentoffset']<0) $_SESSION['currentoffset']=0;
    if ($_SESSION['currentoffset']>$numrows) $_SESSION['currentoffset']=0;

    if (PM("/^\s*$/",$S['s_fn'])) {
      $sql="SELECT *$selextra FROM cat WHERE $ACCESSQL $searchsql $postsql LIMIT ".
            $_SESSION['currentoffset'].",100";
    } else {
      $sql="SELECT *$selextra FROM cat,docs WHERE cat.catid=docs.catid AND docs.rm=0 AND $ACCESSQL ".
           "$searchsql $postsql LIMIT ".$_SESSION['currentoffset'].",100";
    }
    $res=mydo($DB,$sql);

    $pof=$_SESSION['currentoffset']/100+1;
    $nop=0; if ($numrows>0) $nop=intval($numrows/100)+1;
    subheader("$numrows Results,&nbsp;&nbsp;&nbsp;Page $pof of $nop");

  ?>

  <DIV id=head>
  <FORM action="/search" method="post"> 
  
  <TABLE WIDTH=100%>

  <? if (!PM("/^\s*$/",$MS)) { ?>
  <TR><TD VALIGN=top><H2>My Searches:&nbsp;</H2></TD><TD><?= $MS ?></TD></TR>
      <TR><TD HEIGHT=8></TD></TR>
  <? } ?>
  
  <TR><TD VALIGN=top><H2>Type:</H2></TD><TD><? if ($TYPE==='ALL') $tmp="button"; else $tmp="ubutton";   ?>
  <input type="submit" name="type" value="ALL" class="<?=$tmp?>">
  <? foreach($TYPES as $t) { ?>
    <? if (SU($t)===SU($TYPE)) $tmp="button"; else $tmp="ubutton"; ?>
    <input type="submit" name="type" value="<?=SU($t)?>" class="<?=$tmp?>">
  <? } ?></TD>
  
  <TD style='text-align:right;' nowrap valign=top>
    <input type="submit" name="page" value="PREV" class="button">
    <input type="submit" name="page" value="NEXT" class="button">
    <INPUT type='hidden' name='postform' value='1'>
  </FORM></TD>
  </TR>
  
  <TR><TD VALIGN=top><H2>Project:</H2></TD><TD><? if ($PROJECT==='ALL') $tmp="button"; else $tmp="ubutton";   ?>
  <input type="submit" name="project" value="ALL" class="<?=$tmp?>">
  <?  foreach($USERPROJECTS as $pid => $pname) { ?>
    <? if (SU($pname)===SU($PROJECT)) $tmp="button"; else $tmp="ubutton"; ?>
    <input type="submit" name="project" value="<?=$pname?>" class="<?=$tmp?>">
  <? } ?></TD></TR>

  </TABLE></DIV>
  
  <BR>

  <script type="text/javascript">
    function onsearchsearch(input) {
      if(input.value == "") document.getElementById("searchsearch").submit();
    }
  </script>

  <form id="searchsearch" action="/search" method="post" style='display:inline;padding:0px;margin:0px;'>
    <DIV id=head><TABLE>
    
    <TR><TD><H2>All:&nbsp;</H2></TD>
    <TD><input type="search" name="s_all" value="<?=$S['s_all']?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:300px;'></TD>
    <TD style='padding-left:20px;'><? fieldsearch(1) ?></TD></TR>

    <TR><TD><H2>ID:&nbsp;</H2></TD>
    <TD><input type="search" name="s_id" value="<?=$S['s_id']?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:300px;'></TD>
    <TD style='padding-left:20px;'><? fieldsearch(2) ?></TD></TR>
  
    <TR><TD><H2>User: </H2></TD>
    <TD><input type="search" name="s_usr" value="<?=$S['s_usr']?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:300px;'></TD>
    <TD style='padding-left:20px;'><? fieldsearch(3) ?></TD></TR>
  
    <TR><TD><H2>Filename: </H2></TD>
    <TD><input type="search" name="s_fn" value="<?=$S['s_fn']?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:300px;'></TD>
               
    <TD style='padding-left:20px;'><? fieldsearch(4) ?></TD></TR>
  
    <TR><TD HEIGHT=8></TD></TR>

    <TR><TD><H2>Json:&nbsp;</H2></TD>
    <TD COLSPAN=3>
      <INPUT type="search" name="s_json1" value="<?=$S['s_json1']?>" onsearch="onsearchsearch(this)" 
        style='text-align:left;width:380px;'>
      <DIV CLASS="select">
        <SELECT NAME="s_op" class=select>
        <OPTION VALUE='AND'<? if ($S['s_op']==='AND') echo " SELECTED";?>>AND</OPTION>
        <OPTION VALUE='OR'<? if ($S['s_op']==='OR') echo " SELECTED";?>>OR</OPTION>
        </SELECT>
        <DIV class="select_arrow"></DIV>
        </DIV>
      <INPUT type="search" name="s_json2" value="<?=$S['s_json2']?>" onsearch="onsearchsearch(this)" 
        style='text-align:left;width:380px;'>
    </TD></TR>
    
    <TR><TD HEIGHT=8></TD></TR>

    <TR><TD><H2>Save As:</H2> </TD><TD colspan=2>
    
    <input type="search" name="savesearchas" value=""  style='text-align:left;width:250px;'>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="submit" name="searchmode" value="SEARCH" class="button">
    <input type="submit" name="searchmode" value="RESET" class="button">
    
    </TD></TR>
    </TABLE></DIV>
  </form>

  <BR>
  
  <CENTER><H1>Search Results</H1></CENTER>
  <TABLE WIDTH=100%>
  
  <? if (!PM("/^\s*$/",$S['s_fn'])) { ?>
    
    <TR><TH>Id</TH><TH>Filename</TH><TH></TH>
    <TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
    <? while($row = mysqli_fetch_assoc($res)) distabentry($row,'filename') ?>

  <? } else { ?>
    
    <? if (PM("/^\s*$/",$S['s_json1']) && PM("/^\s*$/",$S['s_json2'])) { ?>
      <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH>
      <TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
      <? while($row = mysqli_fetch_assoc($res)) distabentry($row) ?>
    <? } else { ?>
      <TR><TH>Id</TH><TH>Project</TH>
      <? if ($jmode==='json12') { ?><TH><?=$S['s_json1']?></TH><TH><?=$S['s_json2']?></TH><? } ?>
      <? if ($jmode==='json1') { ?><TH><?=$S['s_json1']?></TH><? } ?>
      <? if ($jmode==='json2') { ?><TH><?=$S['s_json2']?></TH><? } ?>
      <TH></TH><TH></TH>
      <TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
      <? while($row = mysqli_fetch_assoc($res)) distabentry($row,$jmode) ?>
    <? } ?>
  
  <? } ?>
  </TABLE>
  <P>

<? function fieldsearch($fn) { global $FSM,$ALLFIELDS,$S; // ------------------------------ search field macro ?>
  <DIV CLASS="select">
  <SELECT NAME="s_field<?=$fn?>" class=select>
  <? foreach($ALLFIELDS as $field => $n) { ?>
    <OPTION VALUE='<?= $field ?>' <? if ($field===$S['s_field'][$fn]) {?>SELECTED<?}?> ><?= SU($field) ?></OPTION>
  <? } ?>
  </SELECT>  <DIV class="select_arrow"></DIV></DIV></TD>
  <TD><input type="search" name="<?="s_key$fn"?>" value="<?=$S['s_key'][$fn]?>" onsearch="onsearchsearch(this)" 
             style='text-align:left;width:300px;'>
  <? if ($fn<$FSM) { ?>           
      <DIV CLASS="select">
                     <SELECT NAME="s_fieldop<?=$fn?>" class=select>
        <OPTION VALUE='AND'<? if ($S['s_fieldop'][$fn]==='AND') echo " SELECTED";?>>AND</OPTION>
        <OPTION VALUE='OR'<? if ($S['s_fieldop'][$fn]==='OR') echo " SELECTED";?>>OR</OPTION>
        </SELECT><DIV class="select_arrow"></DIV></DIV>
  <? } ?>
             
  </TD>
<? } ?>

<? include_once('footer.php'); ?>
