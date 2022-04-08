<?
  include_once('init.php');include_once('header.php'); 

  $mode=getpar('mode');
  $docid=getpar('docid');
  $id=getpar('id');
  $opt=getpar('opt');
  
  $docoff=getpar('docoff'); if (PM("/^\s*$/",$docoff)) $docoff=0;

  if ($mode==='home') {
    $_SESSION['currentoffset']=0;
    header("Location: /");exit;
  }

  if (preg_match("/([A-Z,a-z])(\d+)/",$id,$M)) {
    $catid=catidfromid(SU($id));
    $xid=$id;
  }

  $page=getpar('page');
  if (PM("/^\s*$/",$_SESSION['currentoffset'])) $_SESSION['currentoffset']=0;

  if ($page==='NEXT') $_SESSION['currentoffset']+=100;
  else if ($page==='PREV') $_SESSION['currentoffset']-=100;

  $numrows=0;

  if (!isset($_SESSION['currenttype'])) $_SESSION['currenttype']='ALL';
  if (!PM("/^\s*$/",getpar('type'))) $_SESSION['currenttype']=SU(getpar('type'));
  $TYPE=$_SESSION['currenttype'];
  
  if (!isset($_SESSION['currentproject'])) $_SESSION['currentproject']='ALL';
  if (!PM("/^\s*$/",getpar('project'))) $_SESSION['currentproject']=getpar('project');
  $PROJECT=$_SESSION['currentproject'];

  if (!isset($_SESSION['showhistory'])) $_SESSION['showhistory']=0;
  if ($opt==='showhistory') $_SESSION['showhistory']=1;
  if ($opt==='hidehistory') $_SESSION['showhistory']=0;

  if (!PM("/^\s*$/",$opt)) {
    header("Location: /$id");exit;
  }
  
  if (getpar('postform')==1) {
    header("Location: /");exit;
  }

  if (PM("/^\d+$/",$catid)) {
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL";  
    $sql=PR("/\s*AND\s*$/","",$sql);

    $res=mydo($DB,$sql,1);
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) $row = mysqli_fetch_assoc($res);
      
    $gen=array();
    $sql="SELECT * FROM gen WHERE catid=$catid AND rm=0 ORDER BY gen";
    $res=mydo($DB,$sql,1);
    while($r = mysqli_fetch_assoc($res)) array_push($gen,$r['parent']);
  
    $par=array();
    $sql="SELECT * FROM gen WHERE parent=$catid AND rm=0 ORDER BY gen";
    $res=mydo($DB,$sql,1);
    while($r = mysqli_fetch_assoc($res))  array_push($par,$r['catid']);
  }
?>

<? ///////////////////////////////////////////////////////////////////////////////////// display single entry ?>

<? if ($numrows==1) { ?>
  <? subheader(); ?>

  <H3>Metadata</H3>                                                                      <? // metedata table ?>
  <TABLE>
  <? if ($row['rm']==0) { ?>
    <TR><TH ALIGN=left>Action</TH><TD>
    <?  if (chkeditaccess($row)) { ?>
      <A HREF="/<?=$xid?>/edit" CLASS='icon'>
        <IMG HEIGHT=16 TITLE='Edit' SRC="/img/pencil.png"></A>

      <A HREF="/<?=$xid?>/copy" CLASS='icon'>
        <IMG HEIGHT=16 TITLE='Copy' SRC="/img/copy.png"></A>
      
      <A HREF="/<?=$xid?>/link" CLASS='icon'>
        <IMG HEIGHT=16 TITLE='Link' SRC="/img/link.png"></A>
      
      <A HREF="/<?=$xid?>/clone" CLASS='icon'>
        <IMG HEIGHT=16 TITLE='Clone' SRC="/img/clone.png"></A>
      
      <A HREF="<?=$xid?>/fix"
         onclick="return confirm('Fix this entry (<?=$row['id']?>) ?')" CLASS='icon'>
         <IMG HEIGHT=16 TITLE='Fix Entry' SRC="/img/lock.png"></A>

      <A HREF="<?=$xid?>/delete"
         onclick="return confirm('Delete this entry (<?=$row['id']?>) ?')" CLASS='icon'>
         <IMG HEIGHT=16 TITLE='Delete' SRC="/img/trash.png"></A>
    <? } ?>
    <? if ($row['fixed']==1) { ?>
      <IMG HEIGHT=11 TITLE='' SRC="/img/lock_grey.png">
      <SPAN STYLE='font-size:80%;color:#CCC;'>fixed entry</SPAN>
    <? } ?>
    </TD></TR> 
  <? } ?>
  
  <TR><TH ALIGN=left>Id</TH><TD><?= $row['id'] ?><? if ($TAG==='DEV') {?> / <?= $row['catid']?><? } ?></TD></TR>

  <? if (count($gen)) { ?>
    <TR><TH ALIGN=left>Ancestry:</TH>
    <TD>
    <?
      $tmp="";
      $lg = end($gen);
      foreach ($gen as $g) { 
        $res=mydo($DB,"SELECT * FROM cat WHERE catid=$g",1);$r=mysqli_fetch_assoc($res);
        $tmp.="<A HREF=\"/".$r['id']."\">" . $r['id']. "</A>";
        if ($g!=$lg) $tmp.=" <FONT COLOR=#777777>&gt;</FONT> ";
      }
      echo $tmp;
    ?>
    </TD></TR>
  <? } ?>

  <? if (count($par)) { ?>
    <TR><TH ALIGN=left>Descendants:</TH>
    <TD>
    <?
      $tmp="";
      $lp = end($par);
      foreach ($par as $p) { 
        $res=mydo($DB,"SELECT * FROM cat WHERE catid=$p",1);$r=mysqli_fetch_assoc($res);
        $tmp.="<A HREF=\"/".$r['id']."\">" . $r['id']. "</A>";
        if ($p!=$lp) $tmp.=", ";
      }
      echo $tmp;
    ?>
    </TD></TR>
  <? } ?>

  <TR><TH ALIGN=left>User</TH><TD><? echo htmlspecialchars($row['user']) ?></TD></TR>
  <TR><TH ALIGN=left>Project</TH><TD><?= $USERPROJECTS[$row['project']] ?></TD></TR>
  <TR><TH ALIGN=left>Access</TH><TD><?= $row['access'] ?></TD></TR>
  <TR><TH ALIGN=left>Open Access</TH><TD><? if ($row['oa']==1) echo '&#10004;' ?></TD></TR>

  <?
    $hres=mydo($DB,"SELECT * FROM log WHERE id='".$row['id']."' ORDER BY ts DESC",1);
    $hnumrows =mysqli_num_rows($hres);
    if ($hnumrows>0) {
  ?>

  <TR><TH VALIGN=top ALIGN=left>Edit History</TH><TD VALIGN=center>
  
  <SPAN STYLE='font-size:80%;'>
    <? if ($_SESSION['showhistory']==1) { ?>
      <?
        $hres=mydo($DB,"SELECT * FROM log WHERE id='".$row['id']."' ORDER BY ts DESC",1);
        while($hrow = mysqli_fetch_assoc($hres)) {
          echo $hrow['ts']." <B>".$hrow['user']."</B> ".$hrow['ip']." ➞ ".
               $hrow['action'];
          if ($hrow['docid']!=0) echo ", ".$hrow['docid'];
          if (!PM("/^\s*$/",$hrow['comment'])) echo ", ".$hrow['comment'];
          
          echo "<BR>";
        }
      ?>
      <A HREF="/<?= $row['id']?>/opt/hidehistory" class="minibutton">HIDE</A>
    <? } else { ?>
      <A HREF="/<?= $row['id']?>/opt/showhistory" class="minibutton">SHOW</A>
  
    <? } ?>
  </SPAN>
  </TD></TR>
  
  <? } ?>

  <TR><TH ALIGN=left>Date Created</TH><TD><? echo htmlspecialchars($row['tent']) ?></TD></TR>
  <TR><TH ALIGN=left>Date Modified</TH><TD><? echo htmlspecialchars($row['tcha']) ?></TD></TR>
  </TABLE>
  <P>

  <H3>Data</H3>                                                                              <? // data table ?>
  <TABLE>
  <? foreach($FMAP[$row['type']] as $n => $f) { ?>                                     <? // flex field table ?>
    <? if ($f[4]==0 || !PM("/^\s*$/",$row["f$n"])) { ?>
    <? 
      if ($FMAP[$row['type']][$n][2]==3) {
        if (PM("/^\s*$/",$row["f$n"])) $tmp='';
        else $tmp='✔︎';
      } else {
        $tmp=AL($row["f$n"]);
      }
    ?>
    <TR><TH ALIGN=left><?= $f[0] ?></TH><TD><?= $tmp ?></TD></TR>
    <? } ?>
  <? } ?>
    <? if ($TAG==='DEV') {?>
      <TR><TH ALIGN=left>29</TH><TD><?= $row["f29"] ?></TD></TR>
      <TR><TH ALIGN=left>30</TH><TD><?= $row["f30"] ?></TD></TR>
      <TR><TH ALIGN=left>31</TH><TD><?= $row["f31"] ?></TD></TR>
    <? } ?>
  </TABLE>
  <P>
  
  <? if (!is_null($row['jsonmeta']) && !PM("/^\s*$/",$row['jsonmeta'])) { // ---------------------- jasonmeta ?>
    <H3>Additional Metadata</H3>
    <TABLE>
    <? $J=json_decode($row['jsonmeta'],true);?>
    <TR><TH ALIGN=left>Subtype:</TH><TD><input type="submit" name="type" value="<?=$J['type']?>" 
        class="xbutton"></TD></TR>

    <? foreach($J['values'] as $k => $v) { ?>
      <TR><TH ALIGN=left><?=$k?></TH><TD><? echo htmlspecialchars($v) ?></TD></TR>
    <? } ?>
    </TABLE><BR>
  <? } ?>

  <? if (!is_null($row['jsondata']) && !PM("/^\s*$/",$row['jsondata'])) { // ----------------------- jsondata ?>
  
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="json-viewer/jquery.json-viewer.js"></script>
    <link href="json-viewer/jquery.json-viewer.css" type="text/css" rel="stylesheet" />
    <script>
      $(function() {
        function renderJson() {
          try {
            <? echo "var input = " . $row['jsondata'] .";" ?>
          }
          catch (error) {
            return alert("Cannot eval JSON: " + error);
          }
          var options = {
            collapsed: true,
            rootCollapsable: false,
            withQuotes: true,
            withLinks: true
          };
          $('#json-renderer').jsonViewer(input, options);
        }
      
        // Generate on click
        $('#btn-json-viewer').click(renderJson);
      
        // Display JSON sample on page load
        renderJson();
      });
    </script>

    <H3>Json Data</H3>
    <TABLE>
      <TR><TD style='padding:0px;margin:0px;'><pre id="json-renderer"></pre></TD></TR>
    </TABLE><BR>  

  <? } ?>
  
  <? // ----------------------------------------------------------------------------------------- document table

    $fres=mydo($DB,"SELECT count(*) AS c FROM docs WHERE catid=".$row['catid']." AND rm=0",1);
    $frow = mysqli_fetch_assoc($fres);
    $fnumrows=$frow['c'];

    if ($fnumrows>0) { 
     if ($fnumrows>150) {
      
        if ($docoff>$fnumrows) $docoff=0;
        if ($docoff<0) $docoff=0;
      
        $don=$docoff+100;if ($don>$fnumrows) $don=0;
        $dop=$docoff-100;if ($dop<0) $dop=0;
        $prevlink="";
        if ($docoff>0) $prevlink="<A HREF=\"/$xid/do/$dop\" class=\"button\">PREV</A>";
        $nextlink="";
        if ($docoff+100<$fnumrows) $nextlink="<A HREF=\"/$xid/do/$don\" class=\"button\">NEXT</A>";

        $sql="SELECT * FROM docs WHERE catid=".$row['catid']." AND rm=0 LIMIT $docoff,100";  
      } else {
        $sql="SELECT * FROM docs WHERE catid=".$row['catid']." AND rm=0";  
      }    
      $res=mydo($DB,$sql);

      echo "<P><H3>Files</H3> <SPAN STYLE='font-size:90%;color:#999;'>($fnumrows)</SPAN>";
      if (!PM("/^\s*$/",$prevlink) || !PM("/^\s*$/",$nextlink)) echo "&nbsp;&nbsp;&nbsp;";
      if (!PM("/^\s*$/",$prevlink)) echo "<H3>&nbsp;$prevlink&nbsp;</H3>";
      if (!PM("/^\s*$/",$nextlink)) echo "<H3>&nbsp;$nextlink&nbsp;</H3>";

  ?>
    <DIV style='height:6px;'></DIV>
    <TABLE>

    <TR><TH>Id</TH><TH>File</TH><TH>Time</TH><TH>Comment</TH><TH>Size</TH><TH>Action</TH></TR>

    <? while($row = mysqli_fetch_assoc($res)) { ?>
      <TR>
      <TD><A HREF="/send/<?= idencode($catid,$row['docid'])?>"><?= $row['docid'] ?></A></TD>
      <TD><A HREF="/send/<?= idencode($catid,$row['docid'])?>"><?= $row['filename'] ?></A></TD>
      <TD><?= $row['ts'] ?></TD>
      <TD><?= $row['comment'] ?></TD>
      <TD ALIGN=center><?= formsize($row['size']) ?></TD>
      <TD ALIGN=center>
      <? if ($row['mime']==='image/jpeg' || $row['mime']==='image/png' || $row['mime']==='text/plain' || 
             $row['mime']==='image/tiff' || $row['mime']==='image/x-ms-bmp' || 
             $row['mime']==='application/pdf'  || PM("/\.txt$/",$row['filename']) ) { ?>
        <A HREF="/preview/<?= idencode($catid,$row['docid'])?>" CLASS='icon'>
          <IMG HEIGHT=16 TITLE='Edit' SRC="/img/preview.png"></A>
      <? } ?>
      </TD>
      </TR>
      <? } ?>
    </TABLE>
  <? } ?>

    <? // ------------------------------------------------------------------------- show table with linked files
      $sql="select * from cat,links where links.catid=$catid and cat.catid=links.link and $ACCESSQL"; 
      $lres=mydo($DB,$sql,1);
      $lnumrows =mysqli_num_rows($lres);

      if ($lnumrows>0) { ?>
        <P><H3>Linked Entries</H3>
        <TABLE>
        <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH><TH>Action</TH></TR>
      <? } ?>

      <?  while($lrow = mysqli_fetch_assoc($lres)) { ?>
        <TR>
        <TD WIDTH=5><A HREF="/<?= $lrow['id'] ?>"><?= $lrow['id'] ?></A></TD>
        <TD WIDTH=5 style='font-size:80%'><?= $USERPROJECTS[$lrow['project']] ?></TD>
        <TD><?= shortstr($lrow['f0'],48) ?></TD>
        <TD><?= shortstr($lrow['f1'],48) ?></TD>
        
        <TD WIDTH=5 ALIGN=center>
        
        <?  if (chkeditaccess($lrow['catid'])) { ?>
          <A HREF="/<?= idfromcatid($lrow['catid']) ?>/dellink/<?=$lrow['lid']?>" CLASS='icon'>
            <IMG HEIGHT=16 TITLE='Edit' SRC="/img/trash.png"></A>
        <? } ?>
        </TR>
      <? } ?>

      <? if ($lnumrows>0) { ?>
        </TABLE>
      <? } ?>
    <BR><BR>
  <? } ?>

<? ///////////////////////////////////////////////////////////////////////////////// display multiple entries ?>

<? if ($numrows!=1 && isset($_SESSION['username'])) { ?>
  <?
    $sql="";

    if ($mode==='search' || !PM("/^\s*$/",$KEY)) {
      if (PM("/^\s*\d+\s*$/",$KEY)) {
        $sql.=" AND (catid=$KEY OR typeid=$KEY OR f0 LIKE '%$KEY%')";
      } else if (preg_match("/^\s*([A-Z])\s*(\d+)\s*$/",SU($KEY),$M)) {
        $sql.=" AND (catid=".catidfromid($M[1].$M[2])." OR f0 LIKE '%$KEY%')";
      } else {
        $sql.=" AND (f0 LIKE '%$KEY%' OR f1 LIKE '%$KEY%' OR f2 LIKE '%$KEY%' OR f3 LIKE '%$KEY%' OR ".
              "f4 LIKE '%$KEY%')";
      }
    }    
    
    if (!PM("/^ALL$/",$TYPE)) $sql.=" AND UPPER(type)='$TYPE'";

    if (!PM("/^ALL$/",$PROJECT)) $sql.=" AND project=".$PROJECTNAMES[$PROJECT];
    
    $sql=PR("/\s*WHERE\s*$/","",$sql);
    $sql=PR("/\s*AND\s*$/","",$sql);
    $sql.=' ORDER BY catid DESC';
    
    $res=mydo($DB,"SELECT count(*) AS c FROM cat WHERE $ACCESSQL $sql",1);
    $row = mysqli_fetch_assoc($res);
    $numrows=$row['c'];

    if ($_SESSION['currentoffset']<0) $_SESSION['currentoffset']=0;
    if ($_SESSION['currentoffset']>$numrows) $_SESSION['currentoffset']=0;

    $res=mydo($DB,"SELECT * FROM cat WHERE $ACCESSQL $sql LIMIT ".$_SESSION['currentoffset'].",100",1);
  
    $pof=$_SESSION['currentoffset']/100+1;
    $nop=0; if ($numrows>0) $nop=intval($numrows/100)+1;
    subheader("$numrows Results,&nbsp;&nbsp;&nbsp;Page $pof of $nop");
  
  ?>
  
  <DIV id=head>
  <FORM action="/" method="post"> 
  <TABLE WIDTH=100%>
  <TR><TD VALIGN=top><H2>Type:</H2></TD><TD><? if ($TYPE==='ALL') $tmp="button"; else $tmp="ubutton";   ?>
  <input type="submit" name="type" value="ALL" class="<?=$tmp?>">
  <? foreach($TYPES as $t) { ?>
    <? if (SU($t)===SU($TYPE)) $tmp="button"; else $tmp="ubutton"; ?>
    <input type="submit" name="type" value="<?=SU($t)?>" class="<?=$tmp?>">
  <? } ?></TD>
  
  <TD style='text-align:right;' nowrap valign=top>
    <input type="submit" name="page" value="PREV" class="button">
    <input type="submit" name="page" value="NEXT" class="button">
      <INPUT type='hidden' name='offset' value='<?= $offset ?>'>
      <INPUT type='hidden' name='key' value='<?= $KEY ?>'>
      <INPUT type='hidden' name='postform' value='1'>
  </FORM></TD>
  </TR>
  
  <TR><TD VALIGN=top><H2>Project:</H2></TD><TD><? if ($PROJECT==='ALL') $tmp="button"; else $tmp="ubutton";   ?>
  <input type="submit" name="project" value="ALL" class="<?=$tmp?>">
  <?  foreach($USERPROJECTS as $pid => $pname) { ?>
    <? if (SU($pname)===SU($PROJECT)) $tmp="button"; else $tmp="ubutton"; ?>
    <input type="submit" name="project" value="<?=$pname?>" class="<?=$tmp?>">
  <? } ?></TD></TR>

  </TABLE></DIV><BR>
  
  <TABLE WIDTH=100%>
  <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH><TH WIDTH=5  style='text-align:center;'>Action</TH></TR>
    <? while($row = mysqli_fetch_assoc($res)) distabentry($row) ?>
  </TABLE>
<? } ?>

<? include_once('footer.php'); ?>
