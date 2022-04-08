<?
  include_once('init.php');include_once('header.php');subheader();

  $id=getpar('id'); 
  if (preg_match("/([A-Z,a-z])(\d+)/",$id,$M)) {
    $catid=catidfromid(SU($id));
    $xid=$id;
  } else {
    $catid=getpar('catid'); if (PM("/^\d+$/",$catid)) $xid=idfromcatid($catid);
  }
    
  $cloneid=getpar('cloneid');
  $docid=getpar('docid');
  $lid=getpar('lid');
  $bid=getpar('bid');
  $pid=getpar('pid');
  $mode=getpar('mode');
  $prj=getpar('prj');
  $access=getpar('access');
  $otype=getpar('otype');
  $osctype=getpar('osctype');
  $submit=getpar('submit');
  $ctype=SL(getpar('ctype'));
  $sctype=getpar('sctype');
  $parent=SL(getpar('parent'));
  $jsondata=getpar('jsondata');

  $docoff=getpar('docoff'); if (PM("/^\s*$/",$docoff)) $docoff=0;

  $upid=id62(16);
?>

<? ///////////////////////////////////////////////////////////////////////////////////////// dropzone options ?>

<? if (PM("/^\d+$/",$catid) && chkeditaccess($catid)) { ?>
  <SCRIPT src="/dropzone.js"></SCRIPT>
  <SCRIPT>
    Dropzone.options.mydrop = {
      dictDefaultMessage:"drop files here or click to browse",
      createImageThumbnails: true,
      maxFiles: 100,
      maxFilesize: 100000,
      timeout: 180000,
      chunking: true,
      forceChunking: true,
      parallelChunkUploads: true,
      chunkSize: 10000000,
      retryChunks: true,
      retryChunksLimit: 3,
      init: function() {
        this.on("queuecomplete", function (file) {
          window.location.href = "/chunker/<?=$catid?>/<?=$upid?>";
        });  
      }
    }
  </SCRIPT>
<? } ?>

<? ////////////////////////////////////////////////////////////////////////////////// save data from new/edit ?>

<?
  
  #if (PM("/^submit$/i",$submit) && ($mode==='savenew' || $mode==='saveedit')) {
  if (PM("/^submit$/i",$submit) && $mode==='savenew' ) {
    
    $MAND=array();
    foreach($FMAP[$otype] as $n => $f) { 
        
      if ($f[1]==1) {
        $tmp=getpar("f$n");
        if (PM("/^\s*$/",$tmp)) $MAND["f$n"]="class='mand'";  
      }
    }

    if (count($MAND)>0) $mode='reedit';

  }
  
  if (PM("/^submit$/i",$submit) && ($mode==='savenew' || $mode==='saveedit' || $mode==='saveclone')) {
    if ($mode==='savenew' || $mode==='saveclone') {
      mysqli_query($DB,"BEGIN");

      $sql="SELECT max(typeid)+1 as nexttid FROM cat WHERE type='$otype'"; 
      $res=mydo($DB,$sql,1);
      $row = mysqli_fetch_assoc($res);
      $nexttid=$row['nexttid'];
      
      if (strlen($nexttid)==0) $nexttid=0;
      
      $xid=$TCHR[$otype].$nexttid;

      $sql="INSERT INTO cat SET id='$xid',catid=0,typeid=$nexttid,tent=now(),user='$USER',";
    }
    
    if ($mode==='saveedit') $sql="UPDATE cat SET ";

    $J=json_decode($STDEF[$osctype]);
    
    $I=array();
    
    if ($STDEF[$osctype]) {
      
      $I['type']=$osctype;
              
      foreach($J as $k => $v) { 
        $esc["sc_$k"]=getpar("sc_$k");
      
        $I['values'][$k]=HSC(getpar("sc_$k"));
      } 
    
      $jsonmeta=json_encode($I);
    }

    $sql.="type='$otype',rm=0,ts=now(),tcha=now(),project=$prj,jsondata='$jsondata',jsonmeta='$jsonmeta'";
          
    foreach(array('access') as $tmp) {
      $sql.=",$tmp='". mysqli_escape_string($DB,$_POST[$tmp]) ."'";
    }
        
    if ($_POST["oa"]) $sql.=",oa=1";
    else  $sql.=",oa=0";
    
    for ($i=0;$i<$FMAX;$i++) {
      if (is_array($FMAP[$otype][$i][2])) {
        $sql.=",f$i='". mysqli_escape_string($DB,$_POST["fl$i"]) ."'";
      } else {

        $tmp=$_POST["f$i"];
        if ($FMAP[$otype][$i][2]==2) $tmp=DF($tmp);

        $sql.=",f$i='". mysqli_escape_string($DB,$tmp) ."'";
      }
    }
    
    if ($mode==='saveedit') {
      $sql.=" WHERE catid=$catid";
      if ($_POST["oa"]) alog($xid,0,'saveedit');
      else alog($xid,0,'saveedit');
    }

    $res=mydo($DB,$sql);
    if ($mode=='savenew' || $mode==='saveclone') {                   // return to input form after new saved cat
      $catid=mysqli_insert_id($DB); if (PM("/^\d+$/",$catid)) $xid=idfromcatid($catid);
    }

    mydo($DB,"DELETE FROM tags WHERE id='$xid'");
    for ($i=0;$i<$FMAX;$i++) {
      if ($FMAP[$otype][$i][2]==0 || $FMAP[$otype][$i][2]==1) {
        $tmp=$_POST["f$i"];
        if (!PM("/^\s*$/",$tmp)) {
          
          if (preg_match_all("/\#[A-Za-z]+\w+\b/",$tmp,$M)) {
            if (is_array($M[0])) {
              foreach ($M[0] as $tag)  {
                $tag=SL(PR("/^\s*#\s*/","",$tag));
                mydo($DB,"INSERT INTO tags SET id='$xid',tag='$tag',user='$USER'");
              }
            }
          }
          
        }
      }      
    }

    if ($mode=='savenew' || $mode==='saveclone') {                   // return to input form after new saved cat
      alog($xid,0,'savenew');

      if (PM("/[A-Z]\d+/i",$parent)) $parent=catidfromid($parent);

      if (PM("/^\d+$/i",$parent)) {
        $sql="INSERT INTO gen VALUES ";
        $res=mydo($DB,"SELECT * FROM gen WHERE catid=$parent AND rm=0 ORDER BY gen",1);
        $gen="";
        while($row = mysqli_fetch_assoc($res)) {
          $gen=$row['gen'];
          $sql.="($catid,".$row['parent'].",$gen,0,now(),'$USER'),";
        }
        $gen++;$sql.="($catid,$parent,$gen,0,now(),'$USER')";
        $res= mydo($DB,$sql);
      }
          
      if ($mode==='saveclone' && PM("/^\d+$/i",$cloneid)) {                          // copy documents to clone
     
        $sql="SELECT * FROM docs WHERE catid=$cloneid AND rm=0";  
        $cres=mydo($DB,$sql,1);
        while($crow = mysqli_fetch_assoc($cres)) { 
          
          $md5=$crow['md5'];
          $fn=$crow['filename'];
          $mime=$crow['mime'];
          $size=$crow['size'];
          $dir=$crow['dir'];
          $comment=$crow['comment'];
          
          $fcatid=$crow['catid'];
          $fdocid=$crow['docid'];
          
          if (!is_null($crow['fcatid']) && !is_null($crow['fdocid'])) {
            $fcatid=$crow['fcatid'];
            $fdocid=$crow['fdocid'];
          }
          
          $sql="INSERT INTO docs SET docid=0,catid=$catid,fcatid=$fcatid,fdocid=$fdocid,ts=now(),rm=0,".
               "md5='$md5',filename='$fn',mime='$mime',size=$size,dir='$dir',comment='$comment'";
          $res= mydo($DB,$sql);
        }

        $sql="select * from cat,links where links.catid=$cloneid and cat.catid=links.link and $ACCESSQL"; 
        $lres=mydo($DB,$sql,1);
        $lnumrows =mysqli_num_rows($lres);
        if ($lnumrows>0) { 
          while($lrow = mysqli_fetch_assoc($lres)) { 
            $link=$lrow['link'];
            $sql="INSERT INTO links SET lid=0,ts=now(),catid=$catid,link=$link";
            $res= mydo($DB,$sql);

          }
        }
      }

      mysqli_query($DB,"COMMIT");
      header("Location: /$xid/edit");exit;
    }
    
    if ($mode==='saveedit') {                                                          // save document comments
      foreach( $_POST as $k => $v ) {
        if (preg_match("/docom(\d+)/",$k,$m)) {
          if (!PM("/^\s*$/",$_POST[$k])) {
            $res=mydo($DB,"UPDATE docs SET comment='".mysqli_escape_string($DB,$v)."' WHERE docid=".$m[1]);
          }
        }
      }
    }
    
    header("Location: /$xid");exit;
  }

  if ($mode==='clrcopy')  {                                                                   // clear copy list
      unset ($_SESSION["copylist"]);
      header("Location: /home");exit;
  }

  if ($mode==='delbookmark')  {                                                               // delete bookmark
      mydo($DB,"DELETE FROM bookmarks WHERE bid=$bid AND user='$USER'");
      header("Location: /more");exit;
  }

  if ($mode==='delsearch')  {                                                                   // delete search
      mydo($DB,"DELETE FROM prefs WHERE pid=$pid AND user='$USER' AND type='search'");
      header("Location: /more");exit;
  }

  if (PM("/^\d+$/",$catid) && $mode==='bookmark')  {                                      // bookmark id to list
    if (chkeditaccess($catid)) { 
      mydo($DB,"INSERT INTO bookmarks SET ts=now(),catid=$catid,user='$USER'");
      header("Location: /home");exit;
    }
  }

  if (PM("/^\d+$/",$catid) && $mode==='copy')  {                                              // copy id to list
    if (chkeditaccess($catid)) { 
      $_SESSION['copylist'][$catid]=$catid;
      header("Location: /home");exit;
    }
  }

  if (PM("/^\d+$/",$catid) && $mode==='link')  {                                                    // add links
    if (chkeditaccess($catid)) { 
      $hcom="";
      foreach ($_SESSION['copylist'] as $id) {
        mydo($DB,"INSERT INTO links SET lid=0,ts=now(),catid=$catid,link=$id");
        $hcom.=idfromcatid($id).", ";
      }
      $hcom=PR("/,\s*$/","",$hcom);
      
      alog($xid,0,'link',$hcom);
      header("Location: /$xid");exit;   
    }
  }

  if (PM("/^\d+$/",$catid) && $mode==='dellink')  {                                               // delete link
    if (chkeditaccess($catid)) { 
    
      mydo($DB,"DELETE FROM links WHERE lid=$lid AND catid=$catid");
      alog($xid,0,'dellink',"$lid");

      header("Location: /$xid");exit;   }
  }

  if (PM("/^\d+$/",$catid) && $mode==='delete')  {                                // delete entry (cat and docs)
    if (chkeditaccess($catid)) { 
      $mysqlts=date('Y-m-d H:i:s');
      mydo($DB,"UPDATE cat SET rm=1 WHERE catid=$catid");
      mydo($DB,"UPDATE docs SET rm=1 WHERE catid=$catid");
      mydo($DB,"UPDATE gen SET rm=1 WHERE catid=$catid");
      mydo($DB,"UPDATE gen SET rm=1 WHERE parent=$catid");
      mydo($DB,"UPDATE cat SET tcha='$mysqlts' WHERE catid=$catid;");
      alog($xid,0,'delete');
      header("Location: /home");exit;
    }
  }

  if (PM("/^\d+$/",$catid) && PM("/^\d+$/",$docid) && $mode==='deldoc')  {                    // delete document
    if (chkeditaccess($catid)) { 
      $mysqlts=date('Y-m-d H:i:s');
      mydo($DB,"UPDATE docs SET ts='$mysqlts',rm=1 WHERE docid=$docid");
      mydo($DB,"UPDATE cat SET tcha='$mysqlts' WHERE catid=$catid;");
      alog($xid,$docid,'deldoc');
      header("Location: /$xid/edit");exit;
    }
  }

  if (PM("/^\d+$/",$catid) && $mode==='fix')  {                                                  // fix document
    if (chkeditaccess($catid)) { 
      mydo($DB,"UPDATE cat SET fixed=1 WHERE catid=$catid");
      alog($xid,0,'fix');
      header("Location: /$xid");exit;
    }
  }

  if ($mode==='reedit')  {
    $ctype=$otype;
    $sctype=$osctype;
    $eproject=$prj;
    $eaccess=$access;
    $eparent=$parent;
    $ejsondata=$jsondata;
    $mode='new';
  }

  if (PM("/^\d+$/",$catid) && ($mode==='edit' || $mode==='clone'))  {
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL";  
    $res=mydo($DB,$sql,1);
    $numrows =mysqli_num_rows($res);

    if ($numrows==1) {
      $row = mysqli_fetch_assoc($res);
      $etypeid=$row['id'];
      $eproject=$row['project'];
      $eaccess=$row['access'];
      $ejsondata=$row['jsondata'];
      
      $eoa=$row['oa'];
      
      for ($i=0;$i<$FMAX;$i++) $ef[$i]=$efl[$i]=$row["f$i"];
      
      $J=json_decode($row['jsonmeta'],true);
    
      $sctype=$J['type'];
      
      $J=json_decode($row['jsonmeta'],true);
      $J=$J['values'];
      
      if ($STDEF[$sctype]) foreach($J as $k => $v) { 
        $esc["sc_$k"]=$v;
      }
      
      $ctype=$row['type'];
    }

  } else {                                                                                                 //new

    if (PM("/^\s*$/",$otype)) $ctype=$otype;

    if (PM("/^\s*$/",$ctype)) $ctype='data'; // ToDo: change to feault type

    if (!preg_match("/^\s*$/",$ctype)) {
      for ($i=0;$i<$FMAX;$i++) {
        if (is_array($FMAP[$ctype][$i][2])) {
          $efl[$i]=getpar("fl$i");
        } else {
          $ef[$i]=getpar("f$i");
        }
      }
    } 
    
    $J=json_decode($STDEF[$sctype]);
    
    if ($STDEF[$sctype]) foreach($J as $k => $v) { 
      $esc["sc_$k"]=getpar("sc_$k");
    }
    
    if ($submit==='submit') $ctype=$otype;
    $mode='new';  

  }
  
?>
 
<? ////////////////////////////////////////////////////////////////////////////////////// new/edit input form ?>
 
<? if ($mode==='new' || $mode==='edit' || $mode==='clone') { ?>

  <FORM action="/edit" method="post" >  
  <DIV id=head><TABLE>

  <TR>
    <? if ($mode==='edit') { ?>
       <TD WIDTH=150><H2>Id:</H2></TD><TD><?= $etypeid ?></TD>
    <? } else { ?>  
      <TD WIDTH=150><H2>Type:</H2></TD><TD>
      <? foreach($TYPES as $type) { ?>
      <? if ($ctype===$type) $tmp="button"; else $tmp="ubutton"; ?>
      <input type="submit" name="ctype" value="<?=SU($type)?>" class="<?=$tmp?>">
      <? } ?>
      </TD>
    <? } ?>
  </TR>
    
  <TR><TD HEIGHT=12></TD></TR>

  <? foreach($FMAP[$ctype] as $n => $f) { ?>
    <? if ($f[4]==0 || !PM("/^\s*$/",$ef[$n])) { ?> 
    
    <TR><TD><H2><?= $f[0] ?>:</H2></TD>
    <TD>

    <? if (is_array($f[2])) { ?>
      <DIV class="select">
        <SELECT NAME='<?="fl$n"?>' class=select>
          <? foreach($f[2] as $sel) { ?>
            <OPTION VALUE='<?= $sel ?>' <? if ($sel===$efl[$n]) {?>SELECTED<?}?>  ><?= $sel ?></OPTION>
          <? } ?>
        </SELECT>
        <DIV CLASS="select_arrow"></DIV>
      </DIV>  
    <? } else { ?>
      <? if ($f[2]==0 || $f[2]==2) { ?>
        <INPUT NAME=<?="f$n"?> <?= $MAND["f$n"] ?> VALUE="<?= HSC($ef[$n]) ?>"  style='width:600px;'
          TYPE=text SIZE=64 onkeydown="return event.key != 'Enter';">
      <? } ?>
      <? if ($f[2]==1) { ?>
        <textarea WRAP=virtual COLS=72 ROWS=3 name=<?="f$n"?> 
          style='width:600px;'><?= HSC($ef[$n]) ?></textarea></TD></TR>
      <? } ?>
      
      <? if ($f[2]==3) { ?>
        <? if (PM("/^\s*$/",$ef[$n])) { ?>
          <input type="checkbox" name=<?="f$n"?> value=1>
        <? } else { ?>
          <input type="checkbox" name=<?="f$n"?> value=1 CHECKED>
        <? } ?>
      <? } ?>

    <? } ?>
    
    </TD></TR>
    <? } ?>
  <? } ?>

  <TR><TD><H2>Json Data:</H2></TD><TD><textarea style='width:600px;' 
    WRAP=virtual COLS=72 ROWS=2 name=jsondata><?= HSC($ejsondata) 
  ?></textarea></TD></TR>

  <? if (is_array($STYPES[$ctype])) { ?>
    
    <? if ($mode==='edit') { ?>
      <? if (!PM("/^\s*$/",$sctype)) { ?>
      <TR><TD HEIGHT=12></TD></TR>
      <TR><TD WIDTH=150><H2>Subtype:</H2></TD><TD>
          <input type="submit" name="type" value="<?=$sctype?>" class="xbutton">
      </TD></TR>
      <? } ?>
    <? } else { ?>
      <TR><TD HEIGHT=12></TD></TR>
      <TR><TD WIDTH=150><H2>Subtype:</H2></TD><TD>
          <? foreach($STYPES[$ctype] as $stype) { ?>
          <? if ($sctype===$stype) $tmp="button"; else $tmp="ubutton"; ?>
          <input type="submit" name="sctype" value="<?=SU($stype)?>" class="<?=$tmp?>" >
          <? } ?>
      </TD></TR>
    <? } ?>

    <TR><TD HEIGHT=12></TD></TR>

    <? $J=json_decode($STDEF[$sctype]); ?>

    <? if ($STDEF[$sctype]) foreach($J as $k => $v) { ?>
      <TR><TD><H2><?= $k ?>:</H2></TD>
      <TD><INPUT NAME=<?="sc_$k"?> <?= $MAND["sc_$k"] ?> VALUE="<?= HSC($esc["sc_$k"]) ?>" style='width:600px;'
          TYPE=text SIZE=64 onkeydown="return event.key != 'Enter';">
    <? } ?>

  <? } ?>
  
  <TR><TD HEIGHT=20></TD></TR>
  </TABLE></DIV>

  <? if ($mode==='edit') { // ------------------------------------------------------------ doument table form ?>
    <? 
      $numrows=0;
      if (PM("/^\d+$/",$catid)) {
        $res=mydo($DB,"SELECT count(*) AS c FROM docs WHERE catid=$catid AND rm=0",1);
        $row = mysqli_fetch_assoc($res);
        $numrows=$row['c'];
      }
      if ($numrows>0) { 
        if ($numrows>150) {
        
          if ($docoff>$numrows) $docoff=0;
          if ($docoff<0) $docoff=0;
        
          $don=$docoff+100;if ($don>$numrows) $don=0;
          $dop=$docoff-100;if ($dop<0) $dop=0;
          
          $prevlink="";
          if ($docoff>0) $prevlink="<A HREF=\"/$xid/edit/do/$dop\" class=\"button\">PREV</A>";
           
          $nextlink="";
          if ($docoff+100<$numrows) $nextlink="<A HREF=\"/$xid/edit/do/$don\" class=\"button\">NEXT</A>";
          echo "</TD></TR></TABLE></DIV>";
  
          $sql="SELECT * FROM docs WHERE catid=$catid AND rm=0 LIMIT $docoff,100";  
        } else {
          $sql="SELECT * FROM docs WHERE catid=$catid AND rm=0";  
        }    
        $res=mydo($DB,$sql);
        
        if (!PM("/^\s*$/",$prevlink) || !PM("/^\s*$/",$nextlink)) 
          echo "<DIV id=head><TABLE WIDTH=100%><TR>".
               "<TD VALIGN=top HEIGHT=24 style='text-align:right;' nowrap valign=top>";
        if (!PM("/^\s*$/",$prevlink)) echo "<H3>&nbsp;$prevlink</H3>";
        if (!PM("/^\s*$/",$nextlink)) echo "<H3>&nbsp;&nbsp;$nextlink</H3>";

        if (!PM("/^\s*$/",$prevlink) || !PM("/^\s*$/",$nextlink)) echo "</TD></TR></TABLE></DIV>";
      ?>

      <TABLE WIDTH=100%>
      <TR><TH>Id</TH><TH>File</TH><TH>Comment</TH><TH>Size</TH><TH></TH></TR>
      <?
        while($row = mysqli_fetch_assoc($res)) { ?>
          <TR>
          <TD WIDTH=5><?= $row['docid'] ?></TD>
          <TD><?= $row['filename'] ?></TD>
          <TD ALIGN=center><input name=<?="docom".$row['docid'] ?> value="<?= HSC($row['comment']) ?>" 
            type=text size=64 WIDTH=100%></TD>
          <TD ALIGN=center><?= formsize($row['size']) ?></TD>
          <TD WIDTH=5 ALIGN=center>
            <? if (chkeditaccess($row['catid'])) { ?>
              <A HREF="/<?=$xid?>/deldoc/<?=$row['docid']?>"
                onclick="return confirm('Delete this document (<?=$row['docid']?>) ?')" CLASS='icon'><IMG 
                  HEIGHT=16 TITLE='Delete' SRC="/img/trash.png"></A>
            <? } ?>
          </TD>
          </TR>
        <? } ?>
      </TABLE>
    <? } ?>
  <? } ?>

  <DIV id=head><TABLE>
  <? if ($numrows>0) { ?><TR><TD HEIGHT=20></TD></TR><? } ?>

  <TR><TD COLSPAN=2><H2>Project:</H2>&nbsp;
    <DIV CLASS="select">
    <SELECT NAME='prj' class=select>
    <? foreach($USERPROJECTS as $pid => $pname) { ?>
      <option value="<?=$pid?>" <? if ($pid==$eproject) echo "SELECTED" ?>><?= SU($pname)?></option>
    <? } ?>
    </SELECT>
    <DIV class="select_arrow"></DIV>
    </DIV>  

  &nbsp;&nbsp;&nbsp;
  <H2>Access:</H2>&nbsp;
    <DIV CLASS="select">
    <SELECT NAME='access' class=select>
      <OPTION VALUE="project" <? if (!strcasecmp($eaccess,'project')) echo "SELECTED" ?>>PROJECT</option>
      <OPTION VALUE="private" <? if (!strcasecmp($eaccess,'private')) echo "SELECTED" ?>>PRIVATE</option>
      <OPTION VALUE="public" <? if (!strcasecmp($eaccess,'public')) echo "SELECTED" ?>>PUBLIC</option>
    </SELECT>
    <DIV class="select_arrow"></DIV>
    </DIV>  

  &nbsp;&nbsp;&nbsp;

  <script>
  function change() {
    var decider = document.getElementById('switch');
    if(decider.checked){
      if(!confirm('Are you sure you want to make your data publicly available worldwide?')) {
        decider.checked = false;
      }
    } 
  }
  </script>

  <h2>Open Access:</h2> 
  <? if ($eoa==1) { ?>
    <input type="checkbox" name=oa value=1 CHECKED>
  <? } else { ?>
    <input type="checkbox" id='switch' name=oa value=1 onclick="change()">
  <? } ?>

  &nbsp;&nbsp;&nbsp;

  <? if ($mode==='new' || $mode==='clone') { ?>
    <H2>Child Of:</H2>&nbsp;
    
    <? if ($mode==='clone') $eparent=$xid; ?>
    
    <input name=parent value="<?=$eparent?>" type=text size=8  onkeydown="return event.key != 'Enter';">
    &nbsp;&nbsp;&nbsp;
  <? } ?>
  
  <input type="submit" name="submit" value="SUBMIT" class="button">
  </TD></TR>

  <TR><TD HEIGHT=12></TD></TR>

  </TABLE></DIV>

  <? if ($mode==='edit') { ?>
    <INPUT TYPE=hidden NAME=mode VALUE=saveedit>
    <INPUT TYPE=hidden NAME=catid VALUE=<?= $catid ?>>
  <? } elseif ($mode==='clone') { ?>
    <INPUT TYPE=hidden NAME=mode VALUE=saveclone>
    <INPUT TYPE=hidden NAME=cloneid VALUE=<?= $catid ?>>
  <? } else { ?>
    <INPUT TYPE=hidden NAME=mode VALUE=savenew>
  <? } ?>
  <INPUT TYPE=hidden NAME=osctype VALUE="<?= $sctype ?>">
  <INPUT TYPE=hidden NAME=otype VALUE="<?= $ctype ?>">
  </FORM>

<? } ?>

<P>

<? ///////////////////////////////////////////////////////////////////////////////////////////////// dropzone ?>

<? if ($mode==='edit') { ?>
  <FORM action="/uploadcopy" id="mydrop" class="dropzone">
    <INPUT type='hidden' name='upid' value='<?= $upid ?>'>  
  </FORM>
<? } ?>

<? include_once('footer.php'); ?>
