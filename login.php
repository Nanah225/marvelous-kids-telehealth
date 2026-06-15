<?php
session_start();
include 'db.php';

$message = "";

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // First check in users table (unified login)
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND is_active=1");
    
    if(mysqli_num_rows($query) > 0){
        $user = mysqli_fetch_assoc($query);
        
        if(password_verify($password, $user['password_hash'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Update last login
            mysqli_query($conn, "UPDATE users SET last_login=NOW() WHERE id='{$user['id']}'");
            
            // Redirect based on role
            if($user['role'] == 'parent'){
                // Get parent data
                $parent_query = mysqli_query($conn, "SELECT * FROM parents WHERE email='$email'");
                if($parent_data = mysqli_fetch_assoc($parent_query)){
                    $_SESSION['parent_id'] = $parent_data['id'];
                    $_SESSION['fullname'] = $parent_data['fullname'];
                    $_SESSION['child_name'] = $parent_data['child_name'];
                }
                header("Location: dashboard.php");
                
            } elseif($user['role'] == 'doctor'){
                // Check doctor verification status
                $doc_query = mysqli_query($conn, "SELECT verification_status, id FROM doctors WHERE email='$email'");
                if($doc_data = mysqli_fetch_assoc($doc_query)){
                    $_SESSION['doctor_id'] = $doc_data['id'];
                    if($doc_data['verification_status'] == 'approved'){
                        header("Location: doctor-dashboard.php");
                    } else {
                        header("Location: doctor-pending.php");
                    }
                } else {
                    header("Location: doctor-dashboard.php");
                }
                
            } elseif($user['role'] == 'admin'){
                header("Location: admin-dashboard.php");
            }
            exit();
        } else {
            $message = "<div class='error'>Incorrect Password</div>";
        }
    } else {
        // Check in parents table for backward compatibility
        $parent_query = mysqli_query($conn, "SELECT * FROM parents WHERE email='$email'");
        if(mysqli_num_rows($parent_query) > 0){
            $user = mysqli_fetch_assoc($parent_query);
            if(password_verify($password, $user['password'])){
                $_SESSION['parent_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['child_name'] = $user['child_name'];
                $_SESSION['user_role'] = 'parent';
                
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "<div class='error'>Incorrect Password</div>";
            }
        } else {
            $message = "<div class='error'>Account Not Found</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Marvelous Kids Login</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:20px;
}

.phone{
width:390px;
max-width:100%;
background:white;
border-radius:40px;
box-shadow:0 20px 60px rgba(0,0,0,0.3);
padding:30px;
}

.logo{
text-align:center;
margin-bottom:30px;
}

.logo-circle{
width:100px;
height:100px;
margin:auto;
border-radius:50%;
background:linear-gradient(135deg,#2E8BFF,#00C896);
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
border:1px solid #ddd;
border-radius:15px;
font-size:15px;
outline:none;
}

.input-group input:focus{
border-color:#2E8BFF;
box-shadow:0 0 0 3px rgba(46,139,255,0.1);
}

.password-box{
position:relative;
}

.password-box span{
position:absolute;
right:15px;
top:15px;
cursor:pointer;
font-size:18px;
}

.role-badges{
display:flex;
justify-content:center;
gap:20px;
margin-bottom:20px;
}

.role-badge{
text-align:center;
padding:8px 12px;
border-radius:20px;
background:#f0f0f0;
font-size:12px;
}

.role-badge i{
margin-right:5px;
}

.btn{
width:100%;
padding:15px;
border:none;
border-radius:15px;
background:linear-gradient(135deg,#2E8BFF,#00C896);
color:white;
font-size:16px;
font-weight:bold;
cursor:pointer;
transition:transform 0.2s;
}

.btn:hover{
transform:translateY(-2px);
box-shadow:0 5px 15px rgba(0,0,0,0.2);
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
background:#f8d7da;
color:#721c24;
padding:12px;
border-radius:10px;
text-align:center;
margin-bottom:15px;
font-size:14px;
}
</style>
</head>
<body>

<div class="phone">

<div class="logo">
<div class="logo-circle">
  <i class="fas fa-child"></i>
</div>
<h1>Marvelous Kids</h1>
<p>Welcome Back</p>
</div>

<div class="role-badges">
    <div class="role-badge"><i class="fas fa-users"></i> Parent</div>
    <div class="role-badge"><i class="fas fa-stethoscope"></i> Doctor</div>
    <div class="role-badge"><i class="fas fa-user-shield"></i> Admin</div>
</div>

<?php echo $message; ?>

<form method="POST">
<div class="input-group">
<label>Email Address</label>
<input type="email" name="email" required placeholder="your@email.com">
</div>

<div class="input-group">
<label>Password</label>
<div class="password-box">
<input type="password" name="password" id="password" required placeholder="Enter your password">
<span onclick="togglePassword()">👁</span>
</div>
</div>

<button type="submit" name="login" class="btn">
    <i class="fas fa-sign-in-alt"></i> Login
</button>

<div class="register-link">
Don't have an account? <a href="register.php">Register Here</a>
</div>

</form>

</div>

<script>
function togglePassword(){
    let password = document.getElementById("password");
    if(password.type === "password"){
        password.type = "text";
    }else{
        password.type = "password";
    }
}
</script>

</body>
</html>