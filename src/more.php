<? ///////////////////////////////////////////////////////////////////////////// archive, more.php (mwx'2022) ?>
<? include_once('init.php');include_once('header.php');subheader(); ?>

<CENTER><H1>My Latest Entries</H1></CENTER>
<? $res=mydo($DB,"SELECT * FROM cat WHERE $ACCESSQL AND user='$USER' ORDER BY tcha DESC LIMIT 16",1); ?>
  <TABLE WIDTH=100%>
  <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH><TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
  <? while($row = mysqli_fetch_assoc($res)) distabentry($row) ?>
  </TABLE>

<P>

<CENTER><H1>Saved Searches</H1></CENTER>
<? $res=mydo($DB,"SELECT * FROM prefs WHERE user='$USER' AND type='search' ORDER BY name",1);?>
  <TABLE WIDTH=100%>
  <TR><TH WIDTH=5>Id</TH><TH ALIGN=left>Name</TH><TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
  <? while($row = mysqli_fetch_assoc($res)) { ?>
    <TR>
    <TD VALIGN=top ALIGN=left><?= $row['pid'] ?></TD>
    <TD VALIGN=top ALIGN=left><A HREF="/search/<?= $row['pid'] ?>"><?= $row['name'] ?></A></TD>
    
    <TD VALIGN=center ALIGN=center style="white-space:nowrap">  
       	<A HREF="/delsearch/<?= $row['pid'] ?>" CLASS='icon'>
        <IMG HEIGHT=14 TITLE='Delete' SRC="/img/trash.png"></A>
    </TD></TR>
  <? } ?>
  </TABLE>

<P>

<CENTER><H1>Bookmarks</H1></CENTER>
<? $res=mydo($DB,"SELECT * FROM cat,bookmarks WHERE  cat.catid=bookmarks.catid and bookmarks.user='$USER'",1);?>
  <TABLE WIDTH=100%>
  <TR><TH>Id</TH><TH>Project</TH><TH></TH><TH></TH><TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
  <? while($row = mysqli_fetch_assoc($res)) distabentry($row,'bookmark') ?>
  </TABLE>

<? include_once('footer.php'); ////////////////////////////////////////////////////////////////////////// END ?>
