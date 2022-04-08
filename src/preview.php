<?
  include_once('init.php');include_once('header.php'); 

  [$catid,$docid]=iddecode(getpar('cdid'));
  
  if (chkreadaccess($catid)) {
    $sql="SELECT * FROM docs WHERE catid=$catid AND docid=$docid";  
    $res=mydo($DB,$sql,1);
    $numrows =mysqli_num_rows($res);
        $row = mysqli_fetch_assoc($res);

    if ($numrows==1) {
      $prevf0=$row['f0'];
      $prevcatid=$row['catid'];
      $prevdocid=$row['docid'];

      $dlink="<A HREF=\"/send/".idencode($catid,$docid)."\">".$row['filename']."</A>";

      $previewtxt="(".$dlink.", $docid, ".formsize($row['size']).", ".$row['mime'].")";
      $prevmime=$row['mime'];
      $prevfilename=$row['filename'];
      
      $prevfilepath="$DATA/".$row['catid']."/".$row['docid'];
      if (!is_null($row['fcatid']) && !is_null($row['fdocid'])) {
        $prevfilepath="$DATA/".$row['fcatid']."/".$row['fdocid'];
      }

      $sres=mydo($DB,"select docid,filename from docs where catid=$prevcatid and rm=0 ".
                     "and docid>$prevdocid order by docid limit 1",1);
      $nextlink="";
      $snumrows =mysqli_num_rows($sres);
      if ($snumrows==1) {
        $srow = mysqli_fetch_assoc($sres);
        $nextlink="<A HREF=\"/preview/".idencode($prevcatid,$srow['docid'])."\" class=\"button\">NEXT</A>";
      }
      
      $sres=mydo($DB,"select docid,filename from docs where catid=$prevcatid and rm=0 ".
                     "and docid<$prevdocid order by docid desc limit 1",1);
      $prevlink="";
      $snumrows =mysqli_num_rows($sres);
      if ($snumrows==1) {
        $srow = mysqli_fetch_assoc($sres);
        $prevlink="<A HREF=\"/preview/".idencode($prevcatid,$srow['docid'])."\" class=\"button\">PREV</A>";
      }
      
      echo "<DIV id=head><TABLE WIDTH=100%><TR><TD>";
      echo "<H3>Preview</H3> <SPAN STYLE='font-size:90%;color:#999;'>$previewtxt</SPAN>";
      echo "</TD><TD style='text-align:right;'>";

      echo "<A HREF=\"/".idfromcatid($catid)."\" class=\"button\">BACK</A>&nbsp;&nbsp;&nbsp";

      if (!PM("/^\s*$/",$prevlink)) echo "&nbsp;$prevlink";
      if (!PM("/^\s*$/",$nextlink)) echo "&nbsp;$nextlink";
      
      echo "</TD></TR></TABLE></DIV>";
      
    }
  }
?>

<? if (PM("/^text\/plain$/",$prevmime) || PM("/\.txt$/",$row['filename'])) { ?>
  <PRE>
  <?  readfile($prevfilepath); ?>
  </PRE>
<? } else if (PM("/^image\//",$prevmime)) { ?>
  <P><IMG SRC='/p/<?=$catid?>/<?=$docid?>/<?=$prevfilename?>' WIDTH=100%>
<? } else if (PM("/^application\/pdf$/",$prevmime)) { ?>
  <? header("Location: /send/".idencode($catid,$row['docid']));exit; ?>
<? } else { ?>
  <P><BR><BR><CENTER><B><SPAN style='font-size:16px;color:#999999;'>no preview avalible</SPAN></B></CENTER><P>
  
<? }?>

<? include_once('footer.php'); ?>
