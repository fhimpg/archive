<? 
  session_start();session_unset();session_destroy();

  $NOLOGIN=1;include_once('init.php');include_once('header.php'); 
  
  $key=getpar('key');
  $mode=getpar('mode');
  $p1=getpar('p1');
  $p2=getpar('p2');

  phplog('ACCOUNT ACTIVATION STARTED','activate');

  $res=mydo($DB,"SELECT * FROM activate WHERE activationkey='$key' AND state=0",1);
  $numrows =mysqli_num_rows($res);
  
  if ($numrows==1) {
    $row = mysqli_fetch_assoc($res);    
    $email=$row['email'];
    $ures=mydo($DB,"SELECT * FROM user WHERE name='$email'",1);
    $unumrows =mysqli_num_rows($ures);

    phplog("$email/$key",'activate');

    if ($unumrows==1) {
      if ($mode==='setpw') {
        if ($p1===$p2) {
          if (strlen($p1)>=8) {
          
            $cpw=crypt($p1,'$5$ujeeyaequoox');
  
            mydo($DB,"UPDATE activate SET state=1 WHERE activationkey='$key'");
            mydo($DB,"UPDATE user SET passwd='$cpw' WHERE name='$email'");
  
            ?>
            <BR><CENTER><H2><SPAN style='color:#00BB00;'>Account activation finished</SPAN></H2>
            <P>
            
            <? phplog("DONE FOR: $email",'activate'); ?>

            <A HREF="/login">You can now login</A>
            
            </CENTER>
  
            <?
            include_once('footer.php');exit;
          
          } else $err='Password to short, enter at least 8 characters';
        } else $err='Passwords does not match';
      }
    } else $xerr="User not found $email";

  } else $xerr='Activation key not found';

  if(!PM("/^\s*$/",$xerr)) phplog("ERROR: $xerr ($key)",'activate');
  if(!PM("/^\s*$/",$err)) phplog("ERROR: $err ($key)",'activate');
?>

<?php if(!PM("/^\s*$/",$xerr)) { ?>
  <BR><CENTER><H2><SPAN style='color:#BB0000;'><?= $xerr ?></SPAN></H2></CENTER>
<? } else { ?>
  <?php if(!isset($_SESSION['USER'])) { ?>
    <BR><CENTER>
    <form action="/activate/<?=$key?>" method="post">
    <H1> Archive account activation</H1><P>
    <DIV id=head><TABLE>
    <TR><TD><H3>Email:</H3></TD>
        <TD ALIGN=right><?= $row['email']?></TD></TR>
     <TR><TD HEIGHT=36></TD></TR>
    <TR><TD ALIGN=center COLSPAN=2>Enter a password for your account:</TD></TR>
     <TR><TD HEIGHT=12></TD></TR>
    <TR><TD><H3>Password:</H3></TD>
        <TD ALIGN=right><input size=12 type="password" name="p1" style='text-align:center;'></TD></TR>
    <TR><TD><H3>Retype Password:&nbsp;</H3></TD>
        <TD ALIGN=right><input size=12 type="password" name="p2" style='text-align:center;'></TD></TR>
     <TR><TD HEIGHT=12></TD></TR>
     </TABLE></DIV><P>
     <? if(!PM("/^\s*$/",$err)) { ?>
        <SPAN style='color:#BB0000;'><?= $err ?></SPAN><P>
      <? } ?>
  
     <input type="submit" value="SET PASSWORD" class="button">
    <input type="hidden" name="mode" value="setpw">  
    </form>
    </CENTER>
  <? } ?>
<? } ?>

<? include_once('footer.php'); ?>
