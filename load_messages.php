<?php
session_start();
include 'db.php';

$parent_id=$_SESSION['parent_id'];

$result=mysqli_query($conn,
"SELECT * FROM messages WHERE parent_id='$parent_id' ORDER BY id ASC");

while($row=mysqli_fetch_assoc($result)){

$class=$row['sender']=="parent"?"parent":"doctor";

echo "<div class='msg $class'>{$row['message']}</div>";
}
?>