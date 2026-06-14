<?php
session_start();
include 'db.php';

$parent_id=$_SESSION['parent_id'];
$msg=$_POST['message'];

mysqli_query($conn,
"INSERT INTO messages(parent_id,doctor_id,sender,message)
VALUES('$parent_id',1,'parent','$msg')");
?>