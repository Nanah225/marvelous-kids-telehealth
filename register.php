<?php
include 'db.php';

$message = "";

if(isset($_POST['register'])){

    $fullname = mysqli_real_escape_string($conn,$_POST['fullname']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $child_name = mysqli_real_escape_string($conn,$_POST['child_name']);
    $child_age = mysqli_real_escape_string($conn,$_POST['child_age']);

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password != $confirm_password){

        $message = "<div class='error'>Passwords do not match!</div>";

    }else{

        $check = mysqli_query($conn,
        "SELECT * FROM parents WHERE email='$email'");

        if(mysqli_num_rows($check) > 0){

            $message = "<div class='error'>Email already exists!</div>";

        }else{

            $hashed_password =
            password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_query($conn,

            "INSERT INTO parents(
            fullname,
            email,
            phone,
            child_name,
            child_age,
            password
            )

            VALUES(
            '$fullname',
            '$email',
            '$phone',
            '$child_name',
            '$child_age',
            '$hashed_password'
            )"

            );

            if($insert){

                header("Location: login.php");
                exit();

            }else{

                $message =
                "<div class='error'>Registration failed!</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Marvelous Kids Registration</title>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:#eef7ff;
height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:20px;
}

.phone{
width:390px;
height:820px;
background:white;
border-radius:40px;
overflow-y:auto;
box-shadow:0 0 40px rgba(0,0,0,.15);
padding:30px;
}

.logo{
text-align:center;
margin-bottom:20px;
}

.logo-circle{
width:90px;
height:90px;
border-radius:50%;
background:linear-gradient(
135deg,
#2E8BFF,
#00C896
);
margin:auto;
display:flex;
justify-content:center;
align-items:center;
font-size:40px;
color:white;
}

.logo h1{
color:#2E8BFF;
margin-top:10px;
font-size:28px;
}

.logo p{
color:#666;
font-size:14px;
}

.section-title{
margin-top:20px;
margin-bottom:10px;
font-size:18px;
font-weight:bold;
color:#2E8BFF;
}

.input-group{
margin-bottom:15px;
}

.input-group label{
display:block;
margin-bottom:5px;
font-weight:600;
color:#444;
}

.input-group input{
width:100%;
padding:12px;
border:1px solid #ccc;
border-radius:12px;
outline:none;
font-size:15px;
}

.input-group input:focus{
border-color:#2E8BFF;
}

.btn{
width:100%;
padding:14px;
border:none;
border-radius:15px;
background:linear-gradient(
135deg,
#2E8BFF,
#00C896
);
color:white;
font-size:16px;
font-weight:bold;
cursor:pointer;
margin-top:10px;
}

.btn:hover{
opacity:.9;
}

.login-link{
text-align:center;
margin-top:20px;
}

.login-link a{
text-decoration:none;
color:#2E8BFF;
font-weight:bold;
}

.error{
background:#ffe5e5;
color:red;
padding:10px;
border-radius:10px;
margin-bottom:15px;
text-align:center;
}

</style>

</head>

<body>

<div class="phone">

<div class="logo">

<div class="logo-circle">
  <img src="marvelous.png" alt="marvelous kids" style="width:400px;height:150px;border-radius:100%;">
</div>


<h1>Marvelous Kids</h1>

<p>Caring For Little Heroes Everywhere</p>

</div>

<?php echo $message; ?>

<form method="POST">

<div class="section-title">
Parent Information
</div>

<div class="input-group">
<label>Full Name</label>
<input type="text" name="fullname" required>
</div>

<div class="input-group">
<label>Email Address</label>
<input type="email" name="email" required>
</div>

<div class="input-group">
<label>Phone Number</label>
<input type="text" name="phone" required>
</div>

<div class="section-title">
Child Information
</div>

<div class="input-group">
<label>Child Name</label>
<input type="text" name="child_name" required>
</div>

<div class="input-group">
<label>Child Age</label>
<input type="number" name="child_age" min="0" max="5" required>
</div>

<div class="section-title">
Security
</div>

<div class="input-group">
<label>Password</label>
<input type="password" name="password" required>
</div>

<div class="input-group">
<label>Confirm Password</label>
<input type="password" name="confirm_password" required>
</div>

<button
type="submit"
name="register"
class="btn">
Create Account
</button>

<div class="login-link">
Already have an account?
<a href="login.php">Login</a>
</div>

</form>

</div>

</body>
</html>