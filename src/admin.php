<? include_once('init.php');include_once('header.php');subheader(); ?>

<? 
  $mode=getpar('mode');
  if ($ROLE>=2) {
    header("Location: /home");exit;
  }
?>

<? if ($mode==='deluser') { ?>
  <? 
    $uid=getpar('uid');
    $urow=myrow($DB,"SELECT * FROM user WHERE uid=$uid");

    $sql="DELETE FROM user WHERE uid=$uid AND type='local'";
    $res=mydo($DB,$sql);

    $sql="DELETE FROM projectmember WHERE name='".mysqli_escape_string($DB,$urow['name'])."'";
    $res=mydo($DB,$sql);

    alog(0,0,'admin',"deluser,$uid,".$urow['name']);

    header("Location: /admin/user");exit;
  ?>    
<? } ?>

<? if ($mode==='inviteagain') { ?>
  <? 
    $uid=getpar('uid');
    $res=mydo($DB,"SELECT * from user WHERE uid=$uid",1);
    $numrows =mysqli_num_rows($res);

    if ($numrows==1) {
      $row = mysqli_fetch_assoc($res);
      $fullname=$row['fullname'];
      $email=$row['name'];
      $passwd=$row['passwd'];
      $usertype=$row['type'];
      $aid=id62(32);

      $res=mydo($DB,"DELETE FROM activate WHERE email='$email'");
      $res=mydo($DB,"INSERT INTO activate SET activationkey='$aid',email='$email',state=0");

      $msg="Your FHI Archive account has been created, but you must activate it by ".
           "clicking on the link below:\n\n".
           "$BASEURL/activate/$aid\n";

      $headers = 'From: archive@fhi.mpg.de';
    
      mail($email,"Activate your archive account", $msg, $headers);
    }

    header("Location: /admin/user");exit;
  ?>
<? } ?>

<? if ($mode==='user') { ?>
  <CENTER>
  <H1>User</H1><BR><BR>
  <? 
    $res=mydo($DB,"SELECT * FROM projects,projectmember WHERE projects.pid=projectmember.pid",0); 
    while($row = mysqli_fetch_assoc($res)) $PM[$row['name']].=$row['pname'].", ";
    if (isset($PM)) {
      foreach($PM as $k => $v) $PM[$k]=PR("/,\s*$/","",$PM[$k]);
    }
  ?>
  <? $res=mydo($DB,"SELECT * FROM user WHERE  role>=$ROLE ORDER BY ts desc ;",1); ?>
  <TABLE>
  <TR><TH>Account&nbsp;Name</TH><TH>Fullname</TH><TH>Type</TH><TH>Role</TH><TH>Id</TH>
      <TH>Projects</TH><TH>Activation</TH>
  <TH WIDTH=5 style='text-align:center;'>Action</TH></TR>
  
  <? while($row = mysqli_fetch_assoc($res)) { ?>
    <TR>
    <TD><?= $row['name'] ?></TD>
    <TD><?= $row['fullname'] ?></TD>
    <TD ALIGN=center><?= $row['type'] ?></TD>
    <TD ALIGN=center><?= $ROLENAMES[$row['role']] ?></TD>
    <TD><?= $row['uid'] ?></TD>
    <TD ALIGN=center><?= $PM[$row['name']]?></TD>
    
    <TD ALIGN=center><? 
      if ($row['type'] ==='ldap') {
        echo '-';       
      } else {
        if (PM("/^\s*$/",$row['passwd'])) {
          echo "<SPAN style='color:#BB0000;'>open</SPAN>";
        } else {
          echo "<SPAN style='color:#00BB00;'>done</SPAN>";
        }
      }
    ?></TD>
    
    <TD ALIGN=left style="white-space:nowrap">
    <? if ($row['type']==='local') { ?>

    <A HREF="/admin/deluser/<?=$row['uid']?>"
      onclick="return confirm('Delete user \'<?=$row['name']?>\' ?')" CLASS='icon'>
      <IMG HEIGHT=16 TITLE='Delete' SRC="/img/trash.png"></A>
    <? if (PM("/^\s*$/",$row['passwd'])) { ?>
    <A HREF="/admin/inviteagain/<?=$row['uid']?>" 
      onclick="return confirm('Invite user \'<?=$row['name']?>\' again?')" CLASS='icon'>
      <IMG HEIGHT=16 TITLE='Invite Again' SRC="/img/mail.png"></A>
    <? } ?>
    </TD>
    <? } ?>
    </TR>
  <? } ?>
  </TABLE></CENTER>
<? } ?>

<? if ($mode==='projects') { ?>
  <CENTER>
  
  <H1>Normal Projects</H1><BR><BR>
  <? $res=mydo($DB,"SELECT * FROM projects WHERE private=0 ORDER BY pname;",1); ?>
  <TABLE>
  <TR><TH ALIGN=left>Project</TH><TH ALIGN=left>ID</TH><TH ALIGN=left>Extra Member</TH></TR>
  
  <? while($row = mysqli_fetch_assoc($res)) { ?>

    <?
      $member="";
      $pres=mydo($DB,"SELECT * FROM projectmember WHERE pid=".$row['pid'].";",1); 
      while($prow = mysqli_fetch_assoc($pres)) $member.=$prow['name'].", ";
      $member=PR("/\s*,\s*$/","",$member);
    ?>

    <TR>
    <TD><?= $row['pname'] ?></TD>
    <TD><?= $row['pid'] ?></TD>
    <TD><?= $member ?></TD>

    </TR>
  <? } ?>
  </TABLE>
  
  <P><BR>
  <H1>Private Projects</H1><BR><BR>
  
  <? $res=mydo($DB,"SELECT * FROM projects WHERE private=1 ORDER BY pname;",1); ?>
  <TABLE WIDTH=100%>
  <TR><TH ALIGN=left>Project</TH><TH ALIGN=left>ID</TH><TH ALIGN=left>Exclusive Member</TH></TR>
  
  <? while($row = mysqli_fetch_assoc($res)) { ?>

    <?
    $member="";
    $pres=mydo($DB,"SELECT * FROM projectmember WHERE pid=".$row['pid'].";",1); 

    while($prow = mysqli_fetch_assoc($pres)) { 
      $member.=$prow['name'].", ";
    }
    $member=PR("/\s*,\s*$/","",$member);
    ?>

    <TR>
    <TD><?= $row['pname'] ?></TD>
    <TD><?= $row['pid'] ?></TD>
    <TD><?= $member ?></TD>
    </TR>
  <? } ?>
  </TABLE></CENTER>
<? } ?>

<? if ($mode==='invitesave') { ?>

  <? 
    $fullname=getpar('fullname');
    $email=getpar('email');
    $passwd=getpar('passwd');
    $usertype=getpar('usertype');
    $aid=id62(32);

    $res=mydo($DB,"SELECT * from user WHERE name='$email'",1);
    $numrows =mysqli_num_rows($res);

    if ($numrows==0) {
      if (!PM("/^\s*$/",$passwd)) {
        
        $cpw=crypt($passwd,'$5$ujeeyaequoox');
        phplog(">>> $passwd $cpw");

        $res=mydo($DB,"INSERT INTO user SET passwd='$cpw',fullname='$fullname',name='$email',".
                      "type='local',role=$usertype");

      } else {

        $res=mydo($DB,"INSERT INTO user SET fullname='$fullname',".
                      "name='$email',type='local',role=$usertype");
        $res=mydo($DB,"INSERT INTO activate SET activationkey='$aid',email='$email',state=0");
  
        $msg="Your FHI Archive account has been created, but you must activate it by ".
             "clicking on the link below:\n\n".
             "$BASEURL/activate/$aid\n";
    
        $headers = 'From: archive@fhi.mpg.de';
    
        mail($email,"Activate your archive account", $msg, $headers);
      
      }
      
      foreach($PROJECTS as $pid => $pname) {
        if (isset($_POST[$pid])) {
          phplog("PID> $pid");
          $res=mydo($DB,"INSERT INTO projectmember SET pid=$pid,name='$email'");
        }
      }

      header("Location: /admin/user");exit;
    
    } else {
      ?> <BR><CENTER><H2><SPAN style='color:#BB0000;'>User already exists</SPAN></H2><BR></CENTER> <?
    }
  ?>
<? } ?>

<? if ($mode==='invite') { ?>
  <H1>Invite User</H1><BR><BR>

  <DIV id=head>
  <FORM action="/admin/invitesave" method="post"> 
  <TABLE WIDTH=100%>
    <TR><TD><H2>Email/Username: </H2></TD>
    <TD>&nbsp;<input type="search" name="email" value="<?=$s_usr?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:400px;'></TD></TR>
    <TR><TD><H2>Fullname: </H2></TD>
    <TD>&nbsp;<input type="search" name="fullname" value="<?=$s_usr?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:400px;'></TD></TR>
    <TR><TD><H2>Password: </H2></TD>
    <TD>&nbsp;<input type="search" name="passwd" value="<?=$s_usr?>" onsearch="onsearchsearch(this)" 
               style='text-align:left;width:400px;'></TD></TR>
    
    <TR><TD HEIGHT=24></TD></TR>
          
    <TR><TD VALIGN=top><H2>User Type: </H2></TD>
    <TD>
      <input type="radio" name=usertype value=2>Regular User
      <input type="radio" name=usertype value=3 CHECKED>Limited User
    </TD></TR>
    
    <TR><TD HEIGHT=24></TD></TR>

    <TR><TD VALIGN=top><H2>Projects: </H2></TD>
    <TD><? foreach($PROJECTS as $pid => $pname) { ?>
      <? if ($PROJECTPRIV[$pid]==0) { ?>
        <SPAN style="white-space: nowrap;"><input type="checkbox" name=<?=$pid?> value=1> <?= $pname ?></SPAN>
    
      <? } ?>
    <? } ?></TD></TR>
    
    <TR><TD HEIGHT=12></TD></TR>

    <TR><TD VALIGN=top><H2>Private Projects: </H2></TD>
    <TD><? foreach($PROJECTS as $pid => $pname) { ?>
      <? if ($PROJECTPRIV[$pid]==1) { ?>
        <SPAN style="white-space: nowrap;"><input type="checkbox" name=<?=$pid?> value=1> <?= $pname ?></SPAN>
      <? } ?>
    <? } ?></TD></TR>
               
    <TR><TD HEIGHT=24></TD></TR>

    <TR><TD></TD><TD colspan=2><input type="submit" name="sctype" value="INVITE USER" class="button" ></TD></TR>
    </TABLE></DIV>
  </FORM>

<? } ?>

<? include_once('footer.php'); ?>

