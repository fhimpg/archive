<?
  include_once('init.php');include_once('header.php');subheader(); 

  if (isset($_POST['tag']) || isset($_GET['tag'])) $_SESSION['tag']=getpar('tag');
  $tag=$_SESSION['tag'];

  $page=getpar('page');
  if (PM("/^\s*$/",$_SESSION['currentoffset'])) $_SESSION['currentoffset']=0;
  if ($page==='NEXT') $_SESSION['currentoffset']+=100;
  else if ($page==='PREV') $_SESSION['currentoffset']-=100;
  if (getpar('postform')==1) {
    header("Location: /tags");exit;
  }

  $sql="SELECT tag,count(*) FROM tags GROUP BY tag ORDER BY tag";
  $res=mydo($DB,$sql,1);
  while($row = mysqli_fetch_assoc($res)) {
    $tmp=$row['tag'];
    if (SL($tmp)===SL($tag)) $TL.="<A HREF=\"/tag/$tmp\"><B>$tmp</B></A> ";
    else $TL.="<A id=tags HREF=\"/tag/$tmp\">$tmp</A> ";
  }

  $sql="SELECT *,SUM(31-(UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(ts))/86400) AS dt ".
       "FROM tags GROUP BY tag ORDER BY dt DESC LIMIT 10;";
  $res=mydo($DB,$sql,1);
  while($row = mysqli_fetch_assoc($res)) {
    $tmp=$row['tag'];
    if (SL($tmp)===SL($tag)) $TRL.="<A HREF=\"/tag/$tmp\"><B>$tmp</B></A> ";
    else $TRL.="<A id=tags HREF=\"/tag/$tmp\">$tmp</A> ";
  }

  if (PM("/^\w+$/",$tag)) {
     $ip=$_SERVER['REMOTE_ADDR'];
    mydo($DB,"INSERT INTO taglog set id=0,ts=now(),ip='$ip',tag='$tag'",1);
  }
  
  $sql="SELECT count(*) AS c FROM tags,cat WHERE $ACCESSQL  AND cat.id=tags.id AND tag='$tag' ";

    $res=mydo($DB,$sql,1);
    $row = mysqli_fetch_assoc($res);
    $numrows=$row['c'];

    if ($_SESSION['currentoffset']<0) $_SESSION['currentoffset']=0;
    if ($_SESSION['currentoffset']>$numrows) $_SESSION['currentoffset']=0;

    $sql="SELECT * FROM tags,cat WHERE $ACCESSQL  AND cat.id=tags.id AND tag='$tag' group by cat.id";

    $res=mydo($DB,$sql,1);
  ?>

  <DIV id=head>
  <FORM action="/tag" method="post"> 
  <TABLE WIDTH=100%>
  <TR><TD VALIGN=top><H2>Trending:</H2>
    <SPAN style='font-size:120%;'><SPAN id=tags><?= $TRL ?></SPAN></SPAN></TD></TR>
  <TR><TD HEIGHT=8></TD></TR>
  <TR><TD VALIGN=top><H2>All Tags:</H2>
    <SPAN style='font-size:120%;'><SPAN id=tags><?= $TL ?></SPAN></SPAN></TD></TR>
  </TABLE></FORM></DIV>
  
  <P>

  <H2>Results:</H2>
  <TABLE WIDTH=100%>
  
  <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH>
    <TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
    <? while($row = mysqli_fetch_assoc($res)) tagstabentry($row) ?>
  </TABLE>
  
  <P>
  
<? // ----------------------------------------------------------------------------------------- edit table entry
  function tagstabentry($row,$mode='default') { 
    global $PROJECTS;
?>
    
  <TR>
  <TD WIDTH=5><A HREF="/<?= $row['id'] ?>"><?= $row['id'] ?></A></TD>
  <TD WIDTH=5 style='font-size:80%'><?= $PROJECTS[$row['project']] ?></TD>
  
  <? if (PM("/filename/",$mode)) { ?>
    <TD ALIGN=center><?= $row['filename'] ?></TD>

  <? } ?>
  <? if (PM("/s_json/",$mode)) { ?>
    <TD ALIGN=center><?= $row['s_json'] ?></TD>

  <? } ?>
  <TD><?= AL($row['f0']) ?></TD>
  <TD><?= AL($row['f1']) ?></TD>
  
  <TD ALIGN=center style="white-space:nowrap">
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
  </TR>

<? } ?>

<? include_once('footer.php'); ?>
