<?php
session_start();
include 'db.php';

if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];

$query = mysqli_query($conn,
"SELECT * FROM child_profiles WHERE parent_id='$parent_id'");

$data = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Child Profile</title>

<style>

body{
background:#eef7ff;
font-family:Arial;
display:flex;
justify-content:center;
padding:20px;
}

.phone{
width:390px;
background:white;
border-radius:30px;
padding:20px;
box-shadow:0 0 20px rgba(0,0,0,0.1);
}

h2{
text-align:center;
color:#2E8BFF;
}

.card{
background:#f7fbff;
padding:15px;
border-radius:15px;
margin-bottom:10px;
}

.label{
font-weight:bold;
color:#333;
}

.value{
color:#555;
margin-top:3px;
}

.back{
display:block;
text-align:center;
margin-top:15px;
padding:12px;
background:#2E8BFF;
color:white;
text-decoration:none;
border-radius:10px;
}

.back:hover{
background:#1b6edc;
}

</style>

</head>

<body>

<div class="phone">

<h2>👶 Child Profile</h2>

<?php if($data){ ?>

<div class="card">
<div class="label">Child Name</div>
<div class="value"><?php echo $data['child_name']; ?></div>
</div>

<div class="card">
<div class="label">Gender</div>
<div class="value"><?php echo $data['gender']; ?></div>
</div>

<div class="card">
<div class="label">Date of Birth</div>
<div class="value"><?php echo $data['dob']; ?></div>
</div>

<div class="card">
<div class="label">Weight</div>
<div class="value"><?php echo $data['weight']; ?></div>
</div>

<div class="card">
<div class="label">Blood Group</div>
<div class="value"><?php echo $data['blood_group']; ?></div>
</div>

<div class="card">
<div class="label">Allergies</div>
<div class="value"><?php echo $data['allergies']; ?></div>
</div>

<?php } else { ?>

<p>No child profile found.</p>

<?php } ?>

<a class="back" href="dashboard.php">
⬅ Back to Dashboard
</a>

</div>

</body>
</html>