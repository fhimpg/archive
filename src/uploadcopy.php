<?
  include_once('init.php');

  $upid=$_POST['upid'];

  if (!empty($_FILES)) {

    $fnid=md5($_FILES['file']['name']);
    mydo($DB,"INSERT INTO tmp SET id='$fnid',value='".
      mysqli_escape_string($DB,$_FILES['file']['name'])."',ts=now()");
  
    $n=sprintf("%04d",$_POST['dzchunkindex']);
    $file = $_FILES['file']['tmp_name'];
    move_uploaded_file($file,"$TMP/$upid-$n-$fnid"); 

  }
?>     
