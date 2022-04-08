<?
$NOLOGIN=1;

require('./init.php');

function jfailed($err='') {
  header('Content-Type: application/json');
  if (strlen($err)>0) echo json_encode(array('success' => 0, 'error' => $err))."\n";
  else echo json_encode(array('success' => 0))."\n";
  exit;
}

function jsuccess($J) {
  header('Content-Type: application/json');echo json_encode($J)."\n";exit;
}

$cmd=getpar('cmd');
$cmds=explode('/',$cmd);

if ($cmd==='login') {                                                                              // rest login
  $J = json_decode(file_get_contents('php://input'), true);

  if (!preg_match("/^\s*$/",$J['password']) && !preg_match("/^\s*$/",$J['user'])) {
    if (login($J['user'],$J['password'])) {
      jsuccess(array('success' => 1, 'token' => $_SESSION['token']));
    } 
  }
  jfailed('login failed');
}

mydo($DB,"DELETE FROM tokens WHERE unix_timestamp(now())-unix_timestamp(ts)>86400");

$J = json_decode(file_get_contents('php://input'), true);                           // load user info from token

if ($J==null) jfailed("invalid json");

$res=mydo($DB,"SELECT * FROM tokens WHERE token='".$J['token']."'",1);          
$numrows =mysqli_num_rows($res);

if ($numrows==1) {
  $row = mysqli_fetch_assoc($res);    
  
  if ($_SERVER['REMOTE_ADDR']===$row['ip']) {
    mydo($DB,"UPDATE tokens SET ts=now() WHERE token='".$J['token']."'");          

    $name=$row['name'];
    $type=$row['type'];
  } else jfailed("invalid token");
} else jfailed("invalid token");

if (!setaccess($name,$type)) jfailed("login failed"); 

////////////////////////////////////////////////////////////////////////////////////////////////// rest commands

if ($cmds[0]==='download') { ///////////////////////////////////////////////////////////////////// download file
  
  $catid=catidfromid($cmds[1]); if ($catid==-1) jfailed("id not found");
  $docid=$cmds[2];

  $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
  $res=mydo($DB,$sql,1);
    
  $numrows =mysqli_num_rows($res);
  if ($numrows==1) {

    $sql="SELECT * FROM docs WHERE catid=$catid AND docid=$docid"; 
    $res=mydo($DB,$sql,1);

    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {

      $row=mysqli_fetch_assoc($res);
      
      $file="$DATA/$catid/$docid";
      if (!is_null($row['fcatid']) && !is_null($row['fdocid'])) {
        $file="$DATA/".$row['fcatid']."/".$row['fdocid'];
      }

      if (file_exists($file)) {
        header('Content-Type: ' . $row['mime']);
        if ($row['mime']==='application/pdf') {
          header('Content-Disposition: inline; filename="' . $row['filename'] . '"'); 
          header('Content-Transfer-Encoding: binary');
          header('Accept-Ranges: bytes');
          ob_clean();ob_end_flush();flush();
          readfile($file);
          exit;
        } else {
          header('Content-Disposition: attachment; filename='. $row['filename']);
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . $row['size']);
          ob_clean();ob_end_flush();flush();
          readfile($file);
          exit;
        }
      }

    } else {
      jfailed("doc not found");
    }
    
  } else {
    jfailed("download failed");
  }
}

if ($cmds[0]==='directupload' || $cmds[0]==='urlupload') { //////////////// upload data (urlupload/directupload)

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  if (chkeditaccess($catid)) { 
    $J=json_decode(file_get_contents('php://input'), true);

    $fn=$J['filename'];
    $fid=id62(12);

    if ($cmds[0]==='directupload') {
      $content=base64_decode($J['content']);
      file_put_contents( "$DATA/tmp/$fid",$content);
    }
    if ($cmds[0]==='urlupload') {
      $url=$J['url'];
      shell_exec("curl -s -o '$DATA/tmp/$fid' '$url'");
    }

    $mime=mime_content_type("$DATA/tmp/$fid");  
    $size=filesize("$DATA/tmp/$fid");
    $md5=md5_file ("$DATA/tmp/$fid");
    $mysqlts=date('Y-m-d H:i:s');

    $sql="INSERT INTO docs SET docid=0,catid=$catid,ts='$mysqlts',rm=0,md5='$md5',filename='".
         mysqli_escape_string($DB,$fn) ."',mime='".mysqli_escape_string($DB,$mime)."',size=$size";
    $res= mydo($DB,$sql);
    $docid=mysqli_insert_id($DB);

    if (!is_dir("$DATA/$catid")) mkdir("$DATA/$catid"); 
    rename("$DATA/tmp/$fid","$DATA/$catid/$docid");
    
    mydo($DB,"UPDATE cat SET tcha='$mysqlts' WHERE catid=$catid;");

    alog($catid,$docid,'upload');

    jsuccess(array('success' => 1, 'docid' => $docid));
  } else {
    jfailed('no access');
  }
}

if ($cmd==='list') { /////////////////////////////////////////////////////////////////// list accessible entries
  $I=array();
  
  $sql="SELECT * FROM cat WHERE $ACCESSQL"; 
  $res=mydo($DB,$sql,1);
    
  $numrows =mysqli_num_rows($res);
  if ($numrows>0) {
  
    while($row = mysqli_fetch_assoc($res)) { 
      array_push($I,$row['catid']);
    }
    
    jsuccess(array('success' => 1, 'ids' => $I));
  } 
}

if ($cmd==='fields') { ///////////////////////////////////////////////////////////////// list accessible entries
  $I=array();
  $J=json_decode(file_get_contents('php://input'), true);

  $type=$J['type'];
  for ($i=0;$i<$FMAX;$i++) {
    $f="f$i";
    $n=["name"=>$FMAP[$type][$i][0],"mandatory"=>$FMAP[$type][$i][1]];
    if (!PM("/^\s*$/",$n['name'])) $I[$f]=$n;
  }
  jsuccess(array('success' => 1, 'fields' => $I));
  
}

if ($cmd==='types') { /////////////////////////////////////////////////////////////////////////////// list types
  $I=array();
  $J=json_decode(file_get_contents('php://input'), true);

  foreach ($TYPES as $t) array_push($I,$t);
  jsuccess(array('success' => 1, 'types' => $I));
  
}

if ($cmd==='projects') { ///////////////////////////////////////////////////////////////////////// list projects
  $I=array();
  $J=json_decode(file_get_contents('php://input'), true);

  foreach ($USERPROJECTS as $p) array_push($I,$p);
  jsuccess(array('success' => 1, 'projects' => $I));
}

if ($cmds[0]==='get' || $cmds[0]==='getraw') { ////////////////////////////////////////////////// get entry data
  
  $catid=catidfromid($cmds[1]); if ($catid==-1) jfailed("id not found");

  $gen=array();
  $sql="SELECT * FROM gen WHERE catid=$catid AND rm=0 ORDER BY gen";
  $res=mydo($DB,$sql,1);
  while($r = mysqli_fetch_assoc($res)) array_push($gen,idfromcatid($r['parent']));
  
  $par=array();
  $sql="SELECT * FROM gen WHERE parent=$catid AND rm=0 ORDER BY gen";
  $res=mydo($DB,$sql,1);
  while($r = mysqli_fetch_assoc($res)) array_push($par,idfromcatid($r['catid']));
  
  $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
  $res=mydo($DB,$sql,1);
    
  $numrows =mysqli_num_rows($res);
  if ($numrows==1) {
    $row = mysqli_fetch_assoc($res);    
    
    if ($cmds[0]==='get') $project=$PROJECTS[$row['project']];
    else $project=$row['project'];

    $J = array('success' => 1,
               'id'=>idfromcatid($row['catid']),
               'tent'=>$row['tent'],
               'tcha'=>$row['tcha'],
               'project'=>$project,
               'user'=>$row['user'],
               'type'=>$row['type']);

    if (count($gen)>0) $J['ancestry'] = $gen;
    if (count($par)>0) $J['descendants'] = $par;

    for ($i=0;$i<$FMAX;$i++) {
      
      if ($cmds[0]==='get') {
        $key=$FMAP[$row['type']][$i][0];
        if (!PM("/^\s*$/",$row["f$i"]))   $J[$key]=$row["f$i"];            
      } else {
        if (!PM("/^\s*$/",$row["f$i"]))   $J["f$i"]=$row["f$i"];           
      }
    }            
        
    $J["jasondata"]=  json_decode($row['jsondata']);  
         
    $I=array(); 
    $sql="SELECT * FROM docs WHERE catid=$catid AND rm=0";  
    $res=mydo($DB,$sql,1);
    $fnumrows =mysqli_num_rows($res);
    if ($fnumrows>0) { 
      while($row = mysqli_fetch_assoc($res)) {
      
       $comment =$row['comment'];if (PM("/^\s*$/",$row['comment'])) $comment=""; 
      
        array_push($I,array('id'       => $row['docid'],
                            'filename' => $row['filename'],
                            'comment'  => $comment,
                            'size'     => $row['size'],
                            'mimetype' => $row['mime']));
      }
      $J["files"]=$I;
    }
    
    $I=array(); 
    $sql="select * from cat,links where links.catid=$catid and cat.catid=links.link and $ACCESSQL"; 

    $res=mydo($DB,$sql,1);
    $fnumrows =mysqli_num_rows($res);
    if ($fnumrows>0) { 
      while($lrow = mysqli_fetch_assoc($res)) {
        array_push($I,array('lid'  => $lrow['lid'],
                            'link' => $lrow['id']));
      }
      $J["links"]=$I;
    }
        
    jsuccess($J);
  } 
}

if ($cmds[0]==='new') { ////////////////////////////////////////////////////////////////////////////// new entry
  $J=json_decode(file_get_contents('php://input'), true);

  $type=$J['type'];
  $project=$J['project'];
  $parent=$J['parent'];

  $m=0;
  foreach($TYPES as $typename) { 
    if (SL($type)===SL($typename)) {
      $m++;
      break;
    }
  }
  if ($m!=1) jfailed("invalid type");

  $m=0;
  foreach ($USERPROJECTS as $pid => $pname) {
    if (SU($pname)===SU($project)) {
      $m++;
      break;
    }
  }
  if ($m!=1) jfailed("invalid project");

  mysqli_query($DB,"BEGIN");

  $sql="SELECT max(typeid)+1 as nexttid FROM cat WHERE type='$type'"; 
  $res=mydo($DB,$sql,1);
  $row = mysqli_fetch_assoc($res);
  $nexttid=$row['nexttid'];
  
  if (strlen($nexttid)==0) $nexttid=0;
      
  $sql="INSERT INTO cat SET catid=0,typeid=$nexttid,tent=now(),";
  for ($i=0;$i<$FMAX;$i++) {
    $f="f$i";
    $c=mysqli_escape_string($DB,$J[$f]);
    if (!PM("/^\s*$/",$c)) {
      $sql.="$f='$c',";
    }
  }

  $xid=$TCHR[$type].$nexttid;

  $sql.="id='$xid',type='$type',rm=0,ts=now(),tcha=now(),user='$USER',project=$pid";

  $res=mydo($DB,$sql);
  $catid=mysqli_insert_id($DB);

  mysqli_query($DB,"COMMIT");
  $myres=mysqli_errno($DB);

  if ($myres==0) {

    if (PM("/[A-Z]\d+/i",$parent)) $parent=catidfromid($parent);

    if (PM("/^\d+$/i",$parent)) {
      $sql="INSERT INTO gen VALUES ";
      $res=mydo($DB,"SELECT * FROM gen WHERE catid=$parent AND rm=0 ORDER BY gen",1);
      $gen="";
      while($row = mysqli_fetch_assoc($res)) {
        $gen=$row['gen'];
        $sql.="($catid,".$row['parent'].",$gen,0,now(),'$USER'),";
      }
      $gen++;$sql.="($catid,$parent,$gen,0,now(),'$USER')";
      $res= mydo($DB,$sql);
    }

    $sql="SELECT * FROM cat WHERE id='$xid' AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
      $trow = mysqli_fetch_assoc($res);    
      mydo($DB,"DELETE FROM tags WHERE id='$xid'");
      for ($i=0;$i<$FMAX;$i++) {
        if ($FMAP[$otype][$i][2]==0 || $FMAP[$otype][$i][2]==1) {
          $tmp=$trow["f$i"];
          if (!PM("/^\s*$/",$tmp)) {
            if (preg_match_all("/\#[A-Za-z]+\w+\b/",$tmp,$M)) {
              if (is_array($M[0])) {
                foreach ($M[0] as $tag)  {
                  $tag=SL(PR("/^\s*#\s*/","",$tag));
                  mydo($DB,"INSERT INTO tags SET id='$xid',tag='$tag',user='$USER'",0);
                }
              }
            }
            
          }
        }    
      }
    }
    alog($xid,0,'savenew');
    jsuccess(array('success' => 1, 'id' => idfromcatid($catid)));
  }

  jfailed();
}

if ($cmds[0]==='edit') { //////////////////////////////////////////////////////////////////////////// edit entry
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  if (chkeditaccess($catid)) { 
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
   
      $sql="UPDATE cat SET ";
      $m=0;
      for ($i=0;$i<$FMAX;$i++) {
        $f="f$i";
        $c=mysqli_escape_string($DB,$J[$f]);
        if (!PM("/^\s*$/",$c)) {
          $sql.="$f='$c',";
          $m++;
        }
      }
      $sql.="tcha=now() WHERE catid=$catid";
      if ($m>0) {
        $res=mydo($DB,$sql);
        
        alog($cmds[1],0,'saveedit');
   
        if (mysqli_errno($DB)==0) jsuccess(array('success' => 1, 'id' => idfromcatid($catid)));
      }
    }
  } else {
    jfailed('no access');
  }

  jfailed();
}

if ($cmds[0]==='delete') { //////////////////////////////////////////////////////////////////////// delete entry
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  if (chkeditaccess($catid)) { 

    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
      mydo($DB,"UPDATE cat SET rm=1 WHERE catid=$catid");
      mydo($DB,"UPDATE docs SET rm=1 WHERE catid=$catid");
      mydo($DB,"UPDATE gen SET rm=1 WHERE catid=$catid");
      alog($cmds[1],0,'delete');
      jsuccess(array('success' => 1, 'id' => $cmds[1]));
    }
  } else {
    jfailed('no access');
  }
  
  jfailed();
}

if ($cmds[0]==='deletedoc') { ////////////////////////////////////////////////////////////////// delete document
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");
  $docid=$cmds[2];

  if (chkeditaccess($catid)) { 
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
      
      $sql="SELECT * FROM docs WHERE catid=$catid AND docid=$docid"; 
      $res=mydo($DB,$sql,1);
  
      $numrows =mysqli_num_rows($res);
      if ($numrows==1) {
    
        mydo($DB,"UPDATE docs SET rm=1 WHERE docid=$docid");
        alog($cmds[1],$docid,'deldoc');
  
        jsuccess(array('success' => 1, 'id' => $cmds[1], 'docid' => $cmds[2]));
      }
    }
  } else {
    jfailed('no access');
  }
 
  jfailed();
}

if ($cmds[0]==='addlinks') { ///////////////////////////////////////////////////////////////////////// add links
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  if (chkeditaccess($catid)) { 

    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
  
      $I=array();
      $hcom="";
      
      foreach($J['links'] as $link) {
        $id=catidfromid($link);
        if ($id!=-1) {
          mydo($DB,"INSERT INTO links SET lid=0,ts=now(),catid=$catid,link=$id");
          $hcom.="$link, ";
          array_push($I,$link);
        }
      }
      $hcom=PR("/,\s*$/","",$hcom);
    
      if (count($I)>0) {
        alog($cmds[1],0,'link',$hcom);
        jsuccess(array('success' => 1, 'id' => $cmds[1], 'links' => $I));
      } else {
        jfailed('link ids not found');  
      }
    }
  } else {
    jfailed('no access');
  }
 
  jfailed();
}

if ($cmds[0]==='deletelinks') { /////////////////////////////////////////////////////////////////// delete links
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");
  
  if (chkeditaccess($catid)) { 
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
  
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
      $I=$J['lids'];
      foreach($I as $i) {
        mydo($DB,"DELETE FROM links WHERE lid=$i AND catid=$catid");
        alog($cmds[1],0,'dellink',"$i");
      }
      
      jsuccess(array('success' => 1, 'id' => $cmds[1]));
    }
  } else {
    jfailed('no access');
  }
  
  jfailed();
}

if ($cmds[0]==='search') { ////////////////////////////////////////////////////////////////////////////// search
  $J=json_decode(file_get_contents('php://input'), true);
  $S=$J['search'];

  $sql="SELECT * FROM cat WHERE (";

  foreach ($S as $s) {
    foreach ($s['fields'] as $f) {
      $sql.="(";  
      foreach ($s['keys'] as $k) {
  
        $sql.="f$f LIKE '%$k%' OR ";
      }
      $sql=PR("/\s*OR\s*$/",") OR ",$sql);
    }
    $sql=PR("/\s*OR\s*$/","",$sql);
    $sql.=" AND ";
  }
  $sql=PR("/\s*AND\s*$/","",$sql);
  $sql.=") AND $ACCESSQL";
  
  $res=mydo($DB,$sql,1);

  $I=array();

  $numrows =mysqli_num_rows($res);
  if ($numrows>0) {
    while($row = mysqli_fetch_assoc($res)) array_push($I,idfromcatid($row['catid']));
  }
  
  jsuccess(array('success' => 1, 'result' => $I));
}

if ($cmds[0]==='jsonadd') { ////////////////////////////////////////////////////////////////////// add json data
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  if (chkeditaccess($catid)) { 
    $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
    $res=mydo($DB,$sql,1);
      
    $numrows =mysqli_num_rows($res);
    if ($numrows==1) {
  
      mydo($DB,"UPDATE cat SET jsondata='".json_encode($J['jsondata'])."' WHERE catid=$catid");
  
      alog($cmds[1],0,'jsonadd',json_encode($J['jsondata']));
  
      jsuccess(array('success' => 1, 'id' => $cmds[1]));
    }
  } else {
    jfailed('no access');
  }
  
  jfailed();
}

if ($cmds[0]==='jsonget') { ////////////////////////////////////////////////////////////////////// add json data
  $J=json_decode(file_get_contents('php://input'), true);

  $catid=catidfromid($cmds[1]);if ($catid==-1) jfailed("id not found");

  $sql="SELECT * FROM cat WHERE catid=$catid AND $ACCESSQL"; 
  $res=mydo($DB,$sql,1);

  $I=array();
    
  $numrows =mysqli_num_rows($res);
  if ($numrows==1) {
    $row = mysqli_fetch_assoc($res);
    $J=json_decode($row['jsondata']);
    jsuccess(array('success' => 1, 'jasondata' => $J));
  }
  
  jfailed();
}

if ($cmds[0]==='jsonsearch') { ///////////////////////////////////////////////////////////////////// json search
  $J=json_decode(file_get_contents('php://input'), true);
  $S=$J['jsonsearch'];
  $R=$J['range'];
  $M=$J['match'];

  $sql="SELECT * FROM cat WHERE $ACCESSQL AND json_extract(jsondata,'\$.$S') IS NOT NULL ";
       
  if (is_array($R)) {
    $sql.="AND json_extract(jsondata,'\$.$S') >= $R[0] ";
    $sql.="AND json_extract(jsondata,'\$.$S') <= $R[1] ";
  }
   
  if (is_string($M)) {
    $sql.="AND lower(json_extract(jsondata,'\$.$S')) like lower('%$M%') ";
  } 

  $sql.="ORDER BY catid DESC";

  $res=mydo($DB,$sql,0);

  $I=array();

  $numrows =mysqli_num_rows($res);
  if ($numrows>0) {
    while($row = mysqli_fetch_assoc($res)) {
      $J=json_decode($row['jsondata']);
      $I[idfromcatid($row['catid'])]=$J;
    }
  }
  
  jsuccess(array('success' => 1, 'result' => $I));
}

?>
