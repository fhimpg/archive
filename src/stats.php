<? include_once('init.php');include_once('header.php');subheader(); ?>

<? 
  if ($ROLE>=2) {
    header("Location: /home");exit;
  }
?>

<CENTER>
<H1>Recent Stats</H1>
<? 
  $U="ts between (CURDATE() - INTERVAL 1 MONTH ) and (CURDATE() + INTERVAL 1 DAY)";

  $res=mydo($DB,"SELECT user,date(ts) AS d FROM log WHERE $U GROUP BY user,d ORDER BY d DESC;",1); 
  while($row = mysqli_fetch_assoc($res)) {
    $DU[$row['d']]++;
    $DATES[$row['d']]++;
  }
  $res=mydo($DB,"SELECT user FROM log  GROUP BY user;",1); 
  while($row = mysqli_fetch_assoc($res)) $TDU++;
  
  $res=mydo($DB,"SELECT *,date(ts) AS d FROM log WHERE action='savenew' AND $U",1); 
  while($row = mysqli_fetch_assoc($res)) {
    $DN[$row['d']]++;
    $DATES[$row['d']]++;
  }
  $res=mydo($DB,"SELECT * FROM log WHERE action='savenew'",1); 
  while($row = mysqli_fetch_assoc($res)) $TDN++;

  $res=mydo($DB,"select *,date(ts) as d from log where action='upload' AND $U group by docid,d order by d",1); 
  while($row = mysqli_fetch_assoc($res)) {
    $UL[$row['d']]++;
    $DATES[$row['d']]++;
  }
  $res=mydo($DB,"select * from log where action='upload'",1); 
  while($row = mysqli_fetch_assoc($res)) $TUL++;

  $res=mydo($DB,"select *,date(ts) as d from log where action='send' AND $U ORDER BY d",1); 
  while($row = mysqli_fetch_assoc($res)) {
    $DL[$row['d']]++;
    $DATES[$row['d']]++;
  }
  $res=mydo($DB,"select * from log where action='send'",1); 
  while($row = mysqli_fetch_assoc($res)) $TDL++;
?>

<TABLE>
<TR><TH>Date</TH><TH>User</TH><TH>New</TH><TH>Uploads</TH><TH>Downloads</TH></TR>
<? foreach($DATES as $d => $u) { ?>
  <?
    $t = date("d.m.Y", strtotime($d));
    $dw = date('w', strtotime($d));
    $col="#000000";if ($dw==0 || $dw==6) $col="#AA0000";
  ?>

  <TR>
  <TD ALIGN=center WIDTH=100><SPAN style=color:<?=$col?>;'><?= $t ?></TD>
  <TD ALIGN=center WIDTH=100><?= $DU[$d] ?></TD>
  <TD ALIGN=center WIDTH=100><?= $DN[$d] ?></TD>
  <TD ALIGN=center WIDTH=100><?= $UL[$d] ?></TD>
  <TD ALIGN=center WIDTH=100><?= $DL[$d] ?></TD>
  </TR>
<? } ?>

</TABLE>

<P>
<H1>Total</H1>

<TABLE>
<TR><TH>Date</TH><TH>User</TH><TH>New</TH><TH>Uploads</TH><TH>Downloads</TH></TR>

<TR>
<TD ALIGN=center WIDTH=100>TOTAL</TD>
<TD ALIGN=center WIDTH=100><?= $TDU ?></TD>
<TD ALIGN=center WIDTH=100><?= $TDN ?></TD>
<TD ALIGN=center WIDTH=100><?= $TUL ?></TD>
<TD ALIGN=center WIDTH=100><?= $TDL ?></TD>
</TR>
</TABLE></CENTER>

<? include_once('footer.php'); ?>

