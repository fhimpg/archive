<?
  require('./init.php');

  $upid=getpar('upid');  
  $catid=getpar('catid'); if (PM("/^\d+$/",$catid)) $xid=idfromcatid($catid);
  
  $cn=0;
  
  if ($handle = opendir("$TMP")) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {    
        if (preg_match("/^($upid)\-(\d+)\-(.*$)/",$entry,$M)) {
          $CHUNKS[$M[3]][$M[2]]=$entry;
          $cn++;
        }
      }
    }
  }

  $nf=0;
  
  if ($cn==0) phplog('no chunks found');

  foreach (array_keys($CHUNKS) as $fn) {
    $CHUNK=$CHUNKS[$fn];
    $fid=id62(8);

    $filename=htmlentities($fn);
    
    sort($CHUNK,SORT_REGULAR);
    phplog("FILE: $fn $filename",'chunk');
    
    foreach (array_keys($CHUNK) as $cn) {
      $c=$CHUNK[$cn];
      phplog("CHUNK: $c",'chunk');
      
      system("cat '$TMP/$c' >> '$DATA/$fid'");
      system("rm '$TMP/$c'");
    }

    $mime=mime_content_type("$DATA/$fid");  
    $size=filesize("$DATA/$fid");
    $md5=md5_file ("$DATA/$fid");

    $realfn=myrow($DB,"SELECT * FROM tmp WHERE id='$fn' LIMIT 1");

    $mysqlts=date('Y-m-d H:i:s');

    $sql="INSERT INTO docs SET docid=0,catid=$catid,ts='$mysqlts',rm=0,md5='$md5',filename='".
      mysqli_escape_string($DB,$realfn['value']) ."',mime='".mysqli_escape_string($DB,$mime)."',size=$size";

    $res= mydo($DB,$sql);
    $docid=mysqli_insert_id($DB);

    mydo($DB,"UPDATE cat SET tcha='$mysqlts' WHERE catid=$catid;");

    if (!is_dir("$DATA/$catid")) mkdir("$DATA/$catid"); 
    rename("$DATA/$fid","$DATA/$catid/$docid");

    alog($catid,$docid,'upload');
  }
  
  header("Location: /$xid/edit");exit; 
?>
