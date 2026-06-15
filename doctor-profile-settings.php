<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$query = mysqli_query($conn, "SELECT * FROM doctors WHERE email='$email'");
$doctor = mysqli_fetch_assoc($query);

$message = "";
if(isset($_POST['update_profile'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
    $experience_years = intval($_POST['experience_years']);
    $consultation_fee = floatval($_POST['consultation_fee']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    
    $update = mysqli_query($conn, "UPDATE doctors SET 
        name='$name', phone='$phone', specialty='$specialty', 
        experience_years='$experience_years', consultation_fee='$consultation_fee', bio='$bio' 
        WHERE email='$email'");
    
    if($update){
        $message = "<div class='success'>Profile updated successfully!</div>";
        $_SESSION['user_name'] = $name;
        $doctor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM doctors WHERE email='$email'"));
    } else {
        $message = "<div class='error'>Update failed!</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Settings - Doctor</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:800px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .card{background:white;border-radius:15px;padding:30px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;font-weight:bold;color:#333;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;}
        .btn{background:#2E8BFF;color:white;padding:12px 30px;border:none;border-radius:8px;cursor:pointer;font-size:16px;}
        .success{background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:20px;}
        .error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:20px;}
        .row-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-cog"></i> Profile Settings</h1>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="card">
        <?php echo $message; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name'] ?? ''); ?>" required>
            </div>
            
            <div class="row-2">
                <div class="form-group">
                    <label>Email (Cannot change)</label>
                    <input type="email" value="<?php echo htmlspecialchars($doctor['email'] ?? ''); ?>" disabled style="background:#f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row-2">
                <div class="form-group">
                    <label>Specialty</label>
                    <select name="specialty">
                        <option value="General Pediatrics" <?php echo ($doctor['specialty'] ?? '') == 'General Pediatrics' ? 'selected' : ''; ?>>General Pediatrics</option>
                        <option value="Neonatology" <?php echo ($doctor['specialty'] ?? '') == 'Neonatology' ? 'selected' : ''; ?>>Neonatology</option>
                        <option value="Pediatric Cardiology" <?php echo ($doctor['specialty'] ?? '') == 'Pediatric Cardiology' ? 'selected' : ''; ?>>Pediatric Cardiology</option>
                        <option value="Pediatric Neurology" <?php echo ($doctor['specialty'] ?? '') == 'Pediatric Neurology' ? 'selected' : ''; ?>>Pediatric Neurology</option>
                        <option value="Pediatric Emergency" <?php echo ($doctor['specialty'] ?? '') == 'Pediatric Emergency' ? 'selected' : ''; ?>>Pediatric Emergency</option>
                        <option value="Child Psychology" <?php echo ($doctor['specialty'] ?? '') == 'Child Psychology' ? 'selected' : ''; ?>>Child Psychology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Years Experience</label>
                    <input type="number" name="experience_years" value="<?php echo $doctor['experience_years'] ?? '0'; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Consultation Fee (SLL)</label>
                <input type="number" name="consultation_fee" value="<?php echo $doctor['consultation_fee'] ?? '150000'; ?>">
            </div>
            
            <div class="form-group">
                <label>Bio / About</label>
                <textarea name="bio" rows="5" placeholder="Tell patients about your experience, education, and approach..."><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" name="update_profile" class="btn"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>