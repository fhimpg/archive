<?
  $NOCLEAN=true;require('./init.php');
  
  [$catid,$docid]=iddecode(getpar('cdid'));

  if (chkreadaccess($catid)) {
  
    $sql="SELECT * FROM docs WHERE docid=$docid";  
    $res=mydo($DB,$sql,1);
    $numrows =mysqli_num_rows($res);
  
    if ($numrows==1) {

      $row = mysqli_fetch_assoc($res);
      
      $file="$DATA/$catid/$docid";
      if (!is_null($row['fcatid']) && !is_null($row['fdocid'])) {
        $file="$DATA/".$row['fcatid']."/".$row['fdocid'];
      }
  
      if (file_exists($file)) {
        alog($catid,$docid,'send',$row['filename']);
  
        header('Content-Type: ' . $row['mime']);
 
        if ($row['mime']==='application/pdf') {
          header('Content-Disposition: inline; filename="'.htmlentities($row['filename']).'"'); 
          header('Content-Transfer-Encoding: binary');
          header('Accept-Ranges: bytes');
          ob_clean();ob_end_flush();flush();
          readfile($file);
          exit;
        } else {
          header('Content-Disposition: attachment; filename="'.htmlentities($row['filename']).'"');
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . $row['size']);
          ob_clean();ob_end_flush();flush();
          readfile($file);
          exit;
        }
      }
    }
  }
?>     

