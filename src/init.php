<? 
  include_once('version.php'); 

  if ($NOLOGIN!=1) $NOLOGIN=0;
  include_once('archive.conf');
  include_once('access.php');
  include_once('tools.php');
  
  function callback($buffer) {                               // cleanup html output (disable with $NOCLEAN=true)
    return PR("/^\s+/m","",$buffer);
  }
  if (!$NOCLEAN) ob_start("callback");

  global $DB;                                                                             // connect to database
  $DB=@mysqli_connect($MYHOST,$MYUSER,$MYPW,$MYDB);
  
  if (mysqli_connect_errno()) {
    echo '<BR><font color=#700099 style=\'font-size:12pt;\'><tt><B>MySQL connect error: </B>' .
         mysqli_connect_error() . '</tt></font>';
    exit;
  }
  
  session_start();

  $id=getpar('id');                                                                     // check for open access
  if (PM("/[A-Z,a-z]\d+/",$id) && !isset($_SESSION['username'])) {
    $catid=catidfromid($id);
    if (PM("/^\d+$/",$catid)) {
      global $DB,$ACCESSQL,$TCHR;
      $sql="SELECT * FROM cat WHERE catid=$catid AND oa=1";  
      $res=mydo($DB,$sql,1);
      $numrows=mysqli_num_rows($res);
      if ($numrows==1) {
        $_SESSION['username']='openaccess';
        $_SESSION['usertype']='oa';
      } 
    }
  }

  if (!$NOLOGIN) {                                                                          // redirect to login
    if (!isset($_SESSION['username'])) {
      phplog("REDIRECT TO LOGIN: ".$_SERVER['REQUEST_URI'],'error');
      session_unset();session_destroy();
      $id=getpar('id');
      if (PM("/[A-Z]\d+/",$id))   header("Location: /login/$id");
      else header("Location: /login");
      exit;
    }
  }

  global $USER,$ROLE,$ROLENAMES,$USERPROJECTS,$PROJECTS,$PROJECTNAMES,$ACCESSQL,$TCHR,$VERSION;
  global $TITLE,$DEBUG,$STDEF,$STYPES,$LMSG,$PROJECTPRIV,$ALLFIELDS,$DEFSEARCHFIELD,$IDPAT;

  $USER='';$USERTYPE='';$ROLE='';$ACCESSQL='';$ROLENAMES=array();$USERPROJECTS=array();
  
  $res = mysqli_query($DB,"SELECT * FROM projects");
  while($row = mysqli_fetch_assoc($res)){
    $PROJECTS[$row['pid']]=$row['pname'];
    $PROJECTPRIV[$row['pid']]=$row['private'];
    $PROJECTNAMES[$row['pname']]=$row['pid'];
  }
  
  setaccess($_SESSION['username'],$_SESSION['usertype']);
  
  phplog('','info');  
  
?>

