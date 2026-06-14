<?php
session_start();
include 'db.php';

$message = "";

if(isset($_POST['login'])){

    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
    "SELECT * FROM parents WHERE email='$email'");

    if(mysqli_num_rows($query) > 0){

        $user = mysqli_fetch_assoc($query);

        if(password_verify($password,$user['password'])){

            $_SESSION['parent_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['child_name'] = $user['child_name'];

            header("Location: dashboard.php");
            exit();

        }else{

            $message =
            "<div class='error'>Incorrect Password</div>";
        }

    }else{

        $message =
        "<div class='error'>Account Not Found</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Marvelous Kids Login</title>

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
box-shadow:0 0 40px rgba(0,0,0,.15);
padding:30px;
overflow:hidden;
}

.logo{
text-align:center;
margin-top:40px;
margin-bottom:30px;
}

.logo-circle{
width:100px;
height:100px;
margin:auto;
border-radius:50%;
background:linear-gradient(
135deg,
#2E8BFF,
#00C896
);

display:flex;
justify-content:center;
align-items:center;
font-size:45px;
color:white;
}

.logo h1{
margin-top:15px;
color:#2E8BFF;
}

.logo p{
margin-top:5px;
color:#666;
}

.input-group{
margin-bottom:20px;
}

.input-group label{
display:block;
margin-bottom:8px;
font-weight:600;
color:#444;
}

.input-group input{
width:100%;
padding:14px;
border:1px solid #ccc;
border-radius:15px;
font-size:15px;
outline:none;
}

.input-group input:focus{
border-color:#2E8BFF;
}

.password-box{
position:relative;
}

.password-box span{
position:absolute;
right:15px;
top:15px;
cursor:pointer;
}

.remember{
display:flex;
align-items:center;
gap:8px;
margin-bottom:20px;
}

.btn{
width:100%;
padding:15px;
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
}

.btn:hover{
opacity:.9;
}

.register-link{
text-align:center;
margin-top:25px;
}

.register-link a{
color:#2E8BFF;
font-weight:bold;
text-decoration:none;
}

.error{
background:#ffe5e5;
color:red;
padding:12px;
border-radius:10px;
text-align:center;
margin-bottom:15px;
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

<p>Welcome Back Parent</p>

</div>

<?php echo $message; ?>

<form method="POST">

<div class="input-group">
<label>Email Address</label>
<input
type="email"
name="email"
required>
</div>

<div class="input-group">
<label>Password</label>

<div class="password-box">

<input
type="password"
name="password"
id="password"
required>

<span onclick="togglePassword()">
👁
</span>

</div>

</div>

<div class="remember">
<input type="checkbox">
<label>Remember Me</label>
</div>

<button
type="submit"
name="login"
class="btn">
Login
</button>

<div class="register-link">
Don't have an account?
<a href="register.php">
Register
</a>
</div>

</form>

</div>

<script>

function togglePassword(){

let password =
document.getElementById("password");

if(password.type === "password"){

password.type = "text";

}else{

password.type = "password";

}

}

</script>

</body>
</html>