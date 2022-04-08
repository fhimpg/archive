<?
  $NOCLEAN=true;require('./init.php'); 

  $catid=getpar('catid');
  $docid=getpar('docid');
  $file=getpar('file');
    
  if (chkreadaccess($catid)) {

    $sql="SELECT * FROM docs WHERE docid=$docid";  
    $res=mydo($DB,$sql,1);
    $numrows =mysqli_num_rows($res);
  
    if ($numrows==1) {
    
      $row = mysqli_fetch_assoc($res);

      alog(idfromcatid($catid),$docid,'preview');

      $file="$DATA/$catid/$docid";
      if (!is_null($row['fcatid']) && !is_null($row['fdocid'])) {
        $file="$DATA/".$row['fcatid']."/".$row['fdocid'];
      }

      if (file_exists($file)) {
        
        $mime=$row['mime'];
        $size=$row['size']; 
        $filename=$row['filename'];
        $md5=$row['md5'];
        
        $prevdone=0;
        
        if ($row['mime']==='image/jpeg' || $row['mime']==='image/png' || 
            $row['mime']==='image/tiff' || $row['mime']==='image/x-ms-bmp') {
          if ($size>1000000) {
            $nfile="$CACHE/$md5.jpg";
            if (!file_exists($nfile)) exec("/usr/bin/convert $file  -resize 1000x1000 jpg:$nfile");
            $file=$nfile;
            $mime="image/jpeg";          
            $size=filesize($file);
            $filename="$filename.preview.jpg";
            $prevdone=1;
          }
        }

        if ($prevdone==0 && $row['mime']==='image/tiff') {
          $nfile="$CACHE/$md5.jpg";
          if (!file_exists($nfile)) exec("/usr/bin/convert $file jpg:$nfile");
          $file=$nfile;
          $mime="image/jpeg";          
          $size=filesize($file);
          $filename="$filename.preview.jpg";
        }

        phplog("PREVIEW: $catid/$docid, $file $size $mime (".$filename.")");

        header('Content-Type: ' . $mime);
  
        //Use Content-Disposition: attachment to specify the filename
        header('Content-Disposition: attachment; filename='. $filename);
  
        //No cache
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
  
        //Define file size
        header('Content-Length: ' . $size);
  
        ob_clean();ob_end_flush();flush();
        readfile($file);
        exit;
         
      }
    }
  
  }
?>     
