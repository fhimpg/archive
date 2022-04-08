<!DOCTYPE html> 
<HTML>
<HEAD>
  <TITLE>Archiv</TITLE>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <LINK rel='stylesheet' href='/style.css' type='text/css'>
  <LINK rel="stylesheet" href="/dropzone.css">
</HEAD>
<BODY>

<DIV id=head>
<TABLE WIDTH=100%>

<TR>

<TD>
  <H1><A HREF="/home" style='color: inherit;text-decoration: inherit;'><IMG SRC='/img/logo.png' HEIGHT=16x> 
  <?= $TITLE ?>
  <SPAN style='margin-top:-5px;font-size:50%;color:#C0C0C0'><?= "v$VERSION" ?></SPAN></A></H1>
  
  </TD>

  <?
    if (getpar('mode')==='reset' || (getpar('mode')==='search' && PM("/^\s*$/",getpar('key')))) {
      $_SESSION['currentsearch']=getpar('key');
      $KEY='';
    } else {
      if (!isset($_SESSION['currentsearch'])) $_SESSION['currentsearch']='';
      if (!PM("/^\s*$/",getpar('key'))) $_SESSION['currentsearch']=getpar('key');
      $KEY=$_SESSION['currentsearch'];
    }
  ?>

  <script type="text/javascript">
    function onglobalsearch(input) {
      if(input.value == "") document.getElementById("globalsearch").submit();
    }
  </script>

  <TD style='text-align:right;' VALIGN=center>
  <? if(isset($_SESSION['username']) && $_SESSION['usertype']!=='oa') { ?>
    <form id="globalsearch" action="/home" method="post" style='display:inline;padding:0px;margin:0px;'>
      <I><FONT COLOR=#888 SIZE=-1>Search:</FONT></I>
      <input SIZE=12 type="search" name="key" value="<?=$_SESSION['currentsearch']?>"  
        style='text-align:center;'   onsearch="onglobalsearch(this)">
      <input type="hidden" name="mode" value="search">
      <INPUT type='hidden' name='postform' value='1'>
    </form>
  <? } ?>
  </TD>

  <TD style='text-align:right;' VALIGN=center>
    <? if(isset($_SESSION['username'])) { ?>
      <? if($_SESSION['usertype']!=='oa') { ?>
        <A HREF="/new" class="button">NEW</A>
        <A HREF="/search" class="button">SEARCH</A>
        <A HREF="/more" class="button">MORE</A>
        <A HREF="/tags" class="button">TAGS</A>
        <A HREF="/logout" class="button">LOGOUT</A>
        <A HREF="/home" class="button">HOME</A>
      <? } else { ?>
        <A HREF="/login" class="button">LOGIN</A>
      <? } ?>
    <? } ?>
  </TD>

</TR>

<? if ($ROLE<2 && isset($_SESSION['username'])) { ?>

  <TR>
  <TD ALIGN=left VALIGN=bottom STYLE='padding-top:-100px;'></TD>
  <TD ALIGN=right COLSPAN=2>
    <A HREF="/admin/stats" class="abutton">STATS</A>
    <A HREF="/admin/projects" class="abutton">PROJECTS</A>
    <A HREF="/admin/invite" class="abutton">INVITE</A>
    <A HREF="/admin/user" class="abutton">USER</A>
  </TD><TR>

<? } ?>

</TABLE></DIV>
<HR>

<? function subheader($txt="") { ?>
  <DIV id=head style='margin-top:-5px;font-size:80%;color:#7981ff'><TABLE WIDTH=100%><TR>
  <TD ALIGN=left VALIGN=top HEIGHT=24><SPAN style='color:#c0c0c0'><? userinfo() ?></SPAN></TD> 
  <TD ALIGN=right VALIGN=top HEIGHT=24>
  <?=$txt?>
  <? if (isset($_SESSION['copylist'])) { if (count($_SESSION['copylist'])>0) { ?>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <? foreach ($_SESSION['copylist'] as $id) echo idfromcatid($id)." "; ?>
    <A HREF="/clrcopy" class="minibutton">CLR</A>
  <? }} ?>
  </TD></TR></TABLE></DIV>
<? } ?>

