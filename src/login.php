<? 
  $NOLOGIN=1;include_once('init.php');include_once('header.php'); 
  
  $mode=getpar('mode');
  $passwd=getpar('password');
  $name=getpar('name');
  $goto=getpar('goto');

  if ($mode=='login') {                                                                         // perform login
    if (login($name,$passwd)) {
      if (PM("/[A-Z]\d+/",$goto)) header("Location: /$goto");
      else header("Location: /home");
      exit;
    }
  }
  
  session_unset();session_destroy();
?>

<?php if(!isset($_SESSION['USER'])) { ?>
  <BR><CENTER>
  <? if (!PM("/^\s*$/",$LMSG)) { ?>
    <H2><SPAN style='color:#BB0000;'><?= $LMSG ?></SPAN></H2><P><BR>
  <? } ?>
  <form action="/login" method="post">
  <H2> Enter name and password:</H2><P>
  <H3>Name:</H3> <input size=12 type="text" name="name" style='text-align:center;' autofocus>
  <H3>Password:</H3> <input size=12 type="password" name="password" style='text-align:center;'>
  <input type="submit" value="LOGIN" class="button">
  <input type="hidden" name="mode" value="login">  
  <? if (PM("/[A-Z]\d+/",$goto)) { ?><input type="hidden" name="goto" value="<?=$goto?>"><? } ?>
  </form>
  </CENTER>
<?php } ?>

<? include_once('footer.php'); ?>
