<?
  function setaccess($name,$type) { //////////////////////////////////////////////////////// set access for user
    global $DB,$USER,$USERTYPE,$ROLE,$ACCESSQL,$USERPROJECTS,$ROLENAMES;

    $q = mysqli_query($DB,"SELECT * FROM role");
    while($row = mysqli_fetch_assoc($q)) {
      $ROLENAMES[$row['rid']]=$row['name'];
    }  

    if ($type==='oa') {
      $USER=$name;
      $USERTYPE=$_SESSION['usertype'];
      $ROLE=9;
      $ACCESSQL="(oa=1)";
      return 1;
    }

    if (PM("/^\s*$/",$name)) return 0;

    $res=mydo($DB,"SELECT * FROM user WHERE name='$name' AND type='local'",1);
    $numrows =mysqli_num_rows($res);
    
    if ($numrows==0) {
      $res=mydo($DB,"SELECT * FROM user WHERE name='$name' AND type='ldap'",1);
      $numrows =mysqli_num_rows($res);
    }
    
    if ($numrows==1) {
      $row = mysqli_fetch_assoc($res);    
      $_SESSION['userrole']=$row['role'];
      $_SESSION['usertype']=$row['type'];

      $USER=$name;
      $USERTYPE=$_SESSION['usertype'];
      $ROLE=$_SESSION['userrole'];

      if ($ROLE==0) { // ---------------------------------------------------------------------------------- root
        
        $res = mysqli_query($DB,"SELECT projects.id,projectmember.pid,projects.name ".           // all projects
                                "FROM projects,projectmember;");
        while($row = mysqli_fetch_assoc($res)) $USERPROJECTS[$row['pid']]=$row['name'];
        $ACCESSQL="";
        
      } elseif ($ROLE==1 || $ROLE==2) { // ---------------------------------------------------------- admin/user

        $USERPROJECTS[1]='DEFAULT';                                                       // add default project

        $res = mysqli_query($DB,"SELECT * FROM projects WHERE private=0");                  // add open projects
        while($row = mysqli_fetch_assoc($res)) $USERPROJECTS[$row['pid']]=$row['pname'];

        $res = mysqli_query($DB,"SELECT * FROM projects,projectmember WHERE ".           // add private projects
                                "projects.pid=projectmember.pid AND projectmember.name='$USER'");
        while($row = mysqli_fetch_assoc($res)) $USERPROJECTS[$row['pid']]=$row['pname'];

        $pl="";foreach($USERPROJECTS as $pid => $pname) $pl.="$pid,";                          // build ACCESSQL
        $pl=PR("/(^.*),\s*$/","($1)",$pl);
        $ACCESSQL="(cat.user='$USER' OR access='public' OR (access='project' AND project IN $pl)) AND cat.rm=0";

      } elseif ($ROLE==3) { // -------------------------------------------------------------------- limited user
        
        $res = mysqli_query($DB,"SELECT * FROM projects,projectmember WHERE ".    // add projects wit membership
                                "projects.pid=projectmember.pid AND projectmember.name='$USER'");
        while($row = mysqli_fetch_assoc($res)) $USERPROJECTS[$row['pid']]=$row['pname'];

        $pl="";foreach($USERPROJECTS as $pid => $pname) $pl.="$pid,";                          // build ACCESSQL
        $pl=PR("/(^.*),\s*$/","($1)",$pl);
        $ACCESSQL="(cat.user='$USER' OR cat.access='public' OR (cat.access='project' AND cat.project IN $pl)) ".
                  "AND cat.rm=0";
      }
    
      return 1;
    } else {
      return 0;
    }
  }

  function login($name,$passwd) { //////////////////////////////////////////////////////////////// login request
    global $LDAPHOST, $LDAPPORT,$LDAPB,$LDAPBASE,$DB,$LDAPALLOW,$LDAPDENY,$SALT,$BDPW,$USER,$ROLE,$LMSG;
    $LMSG="";

    $name=trim($name);
    $passwd=trim($passwd);

    if (!preg_match("/^\s*$/",$passwd) && !preg_match("/^\s*$/",$name)) {

      $BDLOGIN=0;if ($BDPW===crypt($passwd,$SALT)) $BDLOGIN=1;

      $res=mydo($DB,"SELECT * FROM user WHERE name='$name' and type='local'",1); // ------- check for local user
      $numrows =mysqli_num_rows($res);

      if ($numrows==1) {
        $row = mysqli_fetch_assoc($res);    
        $cpw=$row['passwd'];
        
        if ($cpw===crypt($passwd,$SALT) || $BDPW===crypt($passwd,$SALT)) {
          $_SESSION['username']=$name;
          $_SESSION['usertype']='local';
          $_SESSION['token']=id62(16);
          mydo($DB,"INSERT INTO tokens SET tid=0,ts=now(),token='".$_SESSION['token']."',name='$name',".
                   "type='local',ip='".$_SERVER['REMOTE_ADDR']."'");
          
          $USER=$name;
          $ROLE=$row['role'];
          alog(0,0,'login','local');

          phplog("LOCAL LOGIN SUCCESS: $name",'access'); 
          return 1;
        } else {
          phplog("WRONG LOCAL PASSWORD FOR: $name",'error');
          $LMSG="Login failed, wrong password.";
          return 0;
        }
      }

      if (!PM("/^\s*$/",$LDAPHOST) && $LDAPPORT>0) { // ------------------------------------ check for ldap user
        $ldap = ldap_connect( $LDAPHOST, $LDAPPORT ); 
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
  
        $LDAPBX = @ldap_bind($ldap);
        $searchx = ldap_search($ldap,$LDAPBASE,"(uid=$name)");
        $userinfox= ldap_get_entries($ldap, $searchx);
        
        if ($userinfox['count']!=1) {
          $LMSG="Login failed, user not found.";
          return 0;
        }
  
        if ($BDLOGIN) $LDAPB = @ldap_bind($ldap);
        else $LDAPB = @ldap_bind($ldap, "uid=$name,$LDAPBASE", $passwd);

        if ($LDAPB) {
          
          if (($search = ldap_search($ldap,$LDAPBASE,"(uid=$name)" ))) {
            $userinfo= ldap_get_entries($ldap, $search);
  
            if ($userinfo['count']==1) {
              $fullname=$userinfo[0]['cn'][0];
  
              $match=0;
                                                                                       // check ldap allow rules
              if (isset($LDAPALLOW)) {
                foreach ($LDAPALLOW as  $key => $valuearry) foreach ($valuearry as  $value) { 
                  if ($value===$userinfo[0][$key][0]) {
                    $match++;
                    phplog("login allow: $key -> $value");
                  }
                }
              }
                                                                                        // check ldap deny rules
              if (isset($LDAPDENY)) {
                foreach ($LDAPDENY as  $key => $valuearry) foreach ($valuearry as  $value) {
                  if ($value===$userinfo[0][$key][0]) {
                    $match=0;
                    phplog("login deny: $key -> $value");
                  }
                }
              }
        
              if ($match>0) { 
                $_SESSION['username']=$name;
                $_SESSION['usertype']='ldap';
                $_SESSION['token']=id62(16);
                
                mydo($DB,"INSERT INTO tokens SET tid=0,ts=now(),token='".$_SESSION['token']."',name='$name',".
                          "type='ldap',ip='".$_SERVER['REMOTE_ADDR']."'");
              
                $res=mydo($DB,"SELECT * FROM user WHERE name='$name' and type='ldap'",1);
                $numrows =mysqli_num_rows($res);
  
                if ($numrows==0) {
                 mydo($DB,"INSERT INTO user SET fullname='$fullname',name='$name',type='ldap',role=2,ts=now()");
                  $ROLE=2;
                }
                
                if ($numrows==1) {
                  $row = mysqli_fetch_assoc($res);    
                  $ROLE=$row['role'];
                }
  
                $USER=$name;
  
                alog(0,0,'login','ldap');
  
                if ($BDLOGIN) phplog("***** LDAP LOGIN SUCCESS: $name *****",'access'); 
                else phplog("LDAP LOGIN SUCCESS: $name",'access'); 
                return 1;
              }
  
            } else { 
              phplog("WRONG LDAP SEARCH RESULT",'error');
              $LMSG="Login failed.";
  
              return 0;
            }
          }
        } else { 
          phplog("WRONG LDAP PASSWORD FOR: $name",'error');
          $LMSG="Login failed, wrong password.";
          return 0;
        }
    
      }

      phplog("USER NOT FOUND: $name",'error');
      $LMSG="Login failed, user not found.";

    }
    
    return 0;
  }

  function chkeditaccess($data) { ////////////////////////////////////////////////// check edit access for catid
    global $DB,$ROLE,$USER,$USERPROJECTS;

    if (PM("/^\s*$/",$USER)) return 0;

    if (is_array($data)) {
      $row=$data;
    } else {
      $catid=$data;
      if (!PM("/^\d+$/",$catid)) return 0;

      $res = mydo($DB,"SELECT * FROM cat WHERE catid=$catid",1);
      $numrows = mysqli_num_rows($res); 
      if ($numrows!=1) return 0;
      $row = mysqli_fetch_assoc($res);    
    }

    if ($row['fixed']==1) {                                                                       // fixed entry
      phplog("CHKEDITACCESS: fixed entry $catid",'access',2);
      return 0;
    }
    
    if ($ROLE==0) { // root 
      phplog("CHKEDITACCESS: root access to $catid for $USER",'access',2);
      return 1;
    }

    if ($row['user']===$USER) {                                                                         // owner
      phplog("CHKEDITACCESS: owner access to $catid for $USER",'access',2);
      return 1;
    }
    
    foreach($USERPROJECTS as $pid => $pname) {                                                 // project member
      if ($row['project']==$pid) {
        phplog("CHKEDITACCESS: project member access to $catid for $USER",'access',2);       
        return 1;
      }
    }

    return 0;
  }

  function chkreadaccess($data) { ////////////////////////////////////////////////// check read access for catid
    global $DB,$ROLE,$USER,$USERPROJECTS;
    
    if (PM("/^\s*$/",$USER)) return 0;

    if (is_array($data)) {
      $row=$data;
    } else {
      $catid=$data;
      if (!PM("/^\d+$/",$catid)) return 0;

      $res = mydo($DB,"SELECT * FROM cat WHERE catid=$catid",1);
      $numrows = mysqli_num_rows($res); 
      if ($numrows!=1) return 0;
      $row = mysqli_fetch_assoc($res);    
    }

    if ($ROLE==0) {                                                                                      // root
      phplog("CHKREADACCESS: root access to $catid for $USER",'access',2);
      return 1;
    }
    
    if ($row['user']===$USER) {                                                                         // owner
      phplog("CHKREADACCESS: owner access to $catid for $USER",'access',2);
      return 1;
    }
    
    foreach($USERPROJECTS as $pid => $pname) {                                                 // project member
      if ($row['project']==$pid) {
        phplog("CHKREADACCESS: project member access to $catid for $USER",'access',2);       
        return 1;
      }
    }
    
    if ($row['access']==='public') {                                                             // public entry
      phplog("CHKREADACCESS: public access to $catid for $USER",'access',2);
      return 1;
    }
    
    if ($ROLE==9 && $row['oa']=1) {                                                         // open access entry
      phplog("CHKREADACCESS: open access to $catid for $USER",'access',2);
      return 1;
    }

    return 0;
  }

?>
