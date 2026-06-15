<?php
include 'db.php';

$message = "";
$message_type = "";
$selected_role = isset($_POST['role']) ? $_POST['role'] : 'parent';

if(isset($_POST['register'])){

    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password != $confirm_password){
        $message = "<div class='error'>Passwords do not match!</div>";
        $message_type = "error";
    } elseif(strlen($password) < 8) {
        $message = "<div class='error'>Password must be at least 8 characters!</div>";
        $message_type = "error";
    } else {
        
        // For Parent: Insert into parents table
        if($role == 'parent'){
            $check = mysqli_query($conn, "SELECT * FROM parents WHERE email='$email'");
            
            if(mysqli_num_rows($check) > 0){
                $message = "<div class='error'>Email already exists! Please login instead.</div>";
                $message_type = "error";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $child_name = mysqli_real_escape_string($conn, $_POST['child_name']);
                $child_age = mysqli_real_escape_string($conn, $_POST['child_age']);
                $child_dob = !empty($_POST['child_dob']) ? mysqli_real_escape_string($conn, $_POST['child_dob']) : NULL;
                $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
                
                $insert = mysqli_query($conn, "INSERT INTO parents(
                    fullname, email, phone, child_name, child_age, child_dob, address, password, created_at
                ) VALUES(
                    '$fullname', '$email', '$phone', '$child_name', '$child_age', 
                    " . ($child_dob ? "'$child_dob'" : "NULL") . ", '$address', '$hashed_password', NOW()
                )");
                
                if($insert){
                    // Also insert into users table for unified login
                    mysqli_query($conn, "INSERT INTO users(
                        email, password_hash, role, full_name, phone_number, location, is_verified, is_active, created_at
                    ) VALUES(
                        '$email', '$hashed_password', 'parent', '$fullname', '$phone', '$location', 1, 1, NOW()
                    )");
                    
                    $message = "<div class='success'>✅ Registration successful! Redirecting to login...</div>";
                    $message_type = "success";
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $message = "<div class='error'>Registration failed: " . mysqli_error($conn) . "</div>";
                    $message_type = "error";
                }
            }
        }
        
        // For Doctor: Insert into doctors table
        elseif($role == 'doctor'){
            $check = mysqli_query($conn, "SELECT * FROM doctors WHERE email='$email'");
            
            if(mysqli_num_rows($check) > 0){
                $message = "<div class='error'>Email already exists! Please login instead.</div>";
                $message_type = "error";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
                $experience_years = intval($_POST['experience_years']);
                $consultation_fee = floatval($_POST['consultation_fee']);
                $bio = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
                
                $insert = mysqli_query($conn, "INSERT INTO doctors(
                    name, specialty, email, phone, experience_years, consultation_fee, bio, 
                    verification_status, is_active, created_at
                ) VALUES(
                    '$fullname', '$specialty', '$email', '$phone', '$experience_years', 
                    '$consultation_fee', '$bio', 'pending', 1, NOW()
                )");
                
                if($insert){
                    // Also insert into users table
                    mysqli_query($conn, "INSERT INTO users(
                        email, password_hash, role, full_name, phone_number, location, is_verified, is_active, created_at
                    ) VALUES(
                        '$email', '$hashed_password', 'doctor', '$fullname', '$phone', '$location', 0, 1, NOW()
                    )");
                    
                    $message = "<div class='success'>✅ Doctor registration submitted! Redirecting to login...</div>";
                    $message_type = "success";
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $message = "<div class='error'>Registration failed: " . mysqli_error($conn) . "</div>";
                    $message_type = "error";
                }
            }
        }
        
        // For Admin: Insert into users table only (NO CODE REQUIRED NOW)
        elseif($role == 'admin'){
            // Check if email exists
            $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
            
            if(mysqli_num_rows($check) > 0){
                $message = "<div class='error'>Email already exists!</div>";
                $message_type = "error";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert into users table
                $insert = mysqli_query($conn, "INSERT INTO users(
                    email, password_hash, role, full_name, phone_number, location, is_verified, is_active, created_at
                ) VALUES(
                    '$email', '$hashed_password', 'admin', '$fullname', '$phone', '$location', 1, 1, NOW()
                )");
                
                if($insert){
                    $user_id = mysqli_insert_id($conn);
                    
                    // Insert into admins table
                    $role_level = mysqli_real_escape_string($conn, $_POST['role_level'] ?? 'admin');
                    $department = mysqli_real_escape_string($conn, $_POST['department'] ?? 'Administration');
                    
                    mysqli_query($conn, "INSERT INTO admins(
                        user_id, role_level, department, is_active, created_at
                    ) VALUES(
                        '$user_id', '$role_level', '$department', 1, NOW()
                    )");
                    
                    $message = "<div class='success'>✅ Admin account created successfully! Redirecting to login...</div>";
                    $message_type = "success";
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $message = "<div class='error'>Registration failed: " . mysqli_error($conn) . "</div>";
                    $message_type = "error";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Marvelous Kids Registration - Parent, Doctor, Admin</title>
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
width:500px;
max-width:100%;
background:white;
border-radius:40px;
overflow-y:auto;
box-shadow:0 20px 60px rgba(0,0,0,0.3);
padding:30px;
animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.logo{
text-align:center;
margin-bottom:20px;
}

.logo-circle{
width:90px;
height:90px;
border-radius:50%;
background:linear-gradient(135deg,#2E8BFF,#00C896);
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

.role-selector {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    background: #f0f0f0;
    padding: 5px;
    border-radius: 50px;
}

.role-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.role-btn.active {
    background: linear-gradient(135deg, #2E8BFF, #00C896);
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.section-title{
margin-top:20px;
margin-bottom:10px;
font-size:18px;
font-weight:bold;
color:#2E8BFF;
border-bottom: 2px solid #f0f0f0;
padding-bottom: 5px;
}

.form-section {
    display: none;
    animation: fadeIn 0.3s ease;
}

.form-section.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.input-group{
margin-bottom:15px;
}

.input-group label{
display:block;
margin-bottom:5px;
font-weight:600;
color:#444;
font-size:13px;
}

.input-group label i {
    margin-right: 8px;
    color: #2E8BFF;
}

.input-group input, 
.input-group select,
.input-group textarea{
width:100%;
padding:12px;
border:1px solid #ddd;
border-radius:12px;
outline:none;
font-size:14px;
transition: all 0.3s ease;
}

.input-group input:focus,
.input-group select:focus,
.input-group textarea:focus{
border-color:#2E8BFF;
box-shadow: 0 0 0 3px rgba(46,139,255,0.1);
}

.row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn{
width:100%;
padding:14px;
border:none;
border-radius:15px;
background:linear-gradient(135deg,#2E8BFF,#00C896);
color:white;
font-size:16px;
font-weight:bold;
cursor:pointer;
margin-top:10px;
transition: transform 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.login-link{
text-align:center;
margin-top:20px;
padding-top: 15px;
border-top: 1px solid #eee;
}

.login-link a{
text-decoration:none;
color:#2E8BFF;
font-weight:bold;
}

.error{
background:#f8d7da;
color:#721c24;
padding:12px;
border-radius:12px;
margin-bottom:15px;
text-align:center;
font-size:14px;
}

.success{
background:#d4edda;
color:#155724;
padding:12px;
border-radius:12px;
margin-bottom:15px;
text-align:center;
font-size:14px;
}

.hint {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
}

.info-box {
    background: #e3f2fd;
    padding: 12px;
    border-radius: 10px;
    margin-top: 10px;
    font-size: 12px;
    color: #0c5460;
}

.required:after {
    content: " *";
    color: red;
}

@media (max-width: 480px) {
    .phone {
        border-radius: 20px;
        padding: 20px;
    }
    .row-2 {
        grid-template-columns: 1fr;
        gap: 0;
    }
    .role-btn span {
        display: none;
    }
    .role-btn i {
        font-size: 20px;
    }
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
<p>Caring For Little Heroes Everywhere</p>
</div>

<?php echo $message; ?>

<!-- Role Selector -->
<div class="role-selector">
    <button type="button" class="role-btn <?php echo $selected_role == 'parent' ? 'active' : ''; ?>" data-role="parent">
        <i class="fas fa-users"></i> <span>Parent</span>
    </button>
    <button type="button" class="role-btn <?php echo $selected_role == 'doctor' ? 'active' : ''; ?>" data-role="doctor">
        <i class="fas fa-stethoscope"></i> <span>Doctor</span>
    </button>
    <button type="button" class="role-btn <?php echo $selected_role == 'admin' ? 'active' : ''; ?>" data-role="admin">
        <i class="fas fa-user-shield"></i> <span>Admin</span>
    </button>
</div>

<form method="POST">
<input type="hidden" name="role" id="roleInput" value="<?php echo $selected_role; ?>">

<!-- COMMON FIELDS (All Roles) -->
<div class="section-title">
    <i class="fas fa-user-plus"></i> Account Information
</div>

<div class="input-group">
<label><i class="fas fa-user"></i> Full Name <span class="required">required</span></label>
<input type="text" name="fullname" required placeholder="Enter your full name">
</div>

<div class="row-2">
    <div class="input-group">
        <label><i class="fas fa-envelope"></i> Email Address <span class="required">required</span></label>
        <input type="email" name="email" required placeholder="your@email.com">
    </div>
    <div class="input-group">
        <label><i class="fas fa-phone"></i> Phone Number <span class="required">required</span></label>
        <input type="tel" name="phone" required placeholder="+232 XX XXX XXX">
    </div>
</div>

<div class="row-2">
    <div class="input-group">
        <label><i class="fas fa-lock"></i> Password <span class="required">required</span></label>
        <input type="password" name="password" required placeholder="Min. 8 characters">
        <div class="hint">At least 8 characters with 1 uppercase, 1 number</div>
    </div>
    <div class="input-group">
        <label><i class="fas fa-check-circle"></i> Confirm Password <span class="required">required</span></label>
        <input type="password" name="confirm_password" required placeholder="Re-enter password">
    </div>
</div>

<div class="input-group">
    <label><i class="fas fa-map-marker-alt"></i> Location (City/District)</label>
    <input type="text" name="location" placeholder="e.g., Freetown, Western Area">
</div>

<!-- PARENT SPECIFIC FIELDS -->
<div id="parentFields" class="form-section <?php echo $selected_role == 'parent' ? 'active' : ''; ?>">
    <div class="section-title">
        <i class="fas fa-baby"></i> Child Information
    </div>
    
    <div class="row-2">
        <div class="input-group">
            <label><i class="fas fa-child"></i> Child's Name <span class="required">required</span></label>
            <input type="text" name="child_name" id="child_name" placeholder="Child's full name">
        </div>
        <div class="input-group">
            <label><i class="fas fa-calendar-alt"></i> Child's Age <span class="required">required</span></label>
            <input type="number" name="child_age" id="child_age" min="0" max="5" placeholder="0-5 years">
        </div>
    </div>
    
    <div class="row-2">
        <div class="input-group">
            <label><i class="fas fa-birthday-cake"></i> Date of Birth</label>
            <input type="date" name="child_dob" id="child_dob">
        </div>
        <div class="input-group">
            <label><i class="fas fa-phone-alt"></i> Emergency Contact</label>
            <input type="tel" name="emergency_contact" id="emergency_contact" placeholder="Alternative phone number">
        </div>
    </div>
    
    <div class="input-group">
        <label><i class="fas fa-home"></i> Address</label>
        <textarea name="address" id="address" rows="2" placeholder="Home address"></textarea>
    </div>
</div>

<!-- DOCTOR SPECIFIC FIELDS -->
<div id="doctorFields" class="form-section <?php echo $selected_role == 'doctor' ? 'active' : ''; ?>">
    <div class="section-title">
        <i class="fas fa-stethoscope"></i> Professional Information
    </div>
    
    <div class="row-2">
        <div class="input-group">
            <label><i class="fas fa-heartbeat"></i> Specialty <span class="required">required</span></label>
            <select name="specialty" id="specialty">
                <option value="">Select Specialty</option>
                <option value="General Pediatrics">General Pediatrics</option>
                <option value="Neonatology">Neonatology</option>
                <option value="Pediatric Cardiology">Pediatric Cardiology</option>
                <option value="Pediatric Neurology">Pediatric Neurology</option>
                <option value="Pediatric Emergency">Pediatric Emergency</option>
                <option value="Pediatric Nutrition">Pediatric Nutrition</option>
                <option value="Child Psychology">Child Psychology</option>
            </select>
        </div>
        <div class="input-group">
            <label><i class="fas fa-calendar-alt"></i> Years Experience</label>
            <input type="number" name="experience_years" id="experience_years" min="0" max="50" placeholder="Years">
        </div>
    </div>
    
    <div class="row-2">
        <div class="input-group">
            <label><i class="fas fa-money-bill-wave"></i> Consultation Fee (SLL)</label>
            <input type="number" name="consultation_fee" id="consultation_fee" placeholder="150000">
        </div>
    </div>
    
    <div class="input-group">
        <label><i class="fas fa-info-circle"></i> Bio / About</label>
        <textarea name="bio" id="bio" rows="3" placeholder="Tell patients about your experience and approach..."></textarea>
    </div>
    
    <div class="info-box">
        <i class="fas fa-info-circle"></i> 
        <strong>Note:</strong> Your account will be verified by admin before you can start consulting.
    </div>
</div>

<!-- ADMIN SPECIFIC FIELDS (No code required) -->
<div id="adminFields" class="form-section <?php echo $selected_role == 'admin' ? 'active' : ''; ?>">
    <div class="section-title">
        <i class="fas fa-user-shield"></i> Administrative Details
    </div>
    
    <div class="row-2">
        <div class="input-group">
            <label><i class="fas fa-badge"></i> Role Level</label>
            <select name="role_level" id="role_level">
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
                <option value="support">Support Staff</option>
                <option value="super_admin">Super Admin</option>
            </select>
        </div>
        <div class="input-group">
            <label><i class="fas fa-building"></i> Department</label>
            <input type="text" name="department" id="department" placeholder="e.g., Medical Review, Support">
        </div>
    </div>
    
    <div class="info-box">
        <i class="fas fa-info-circle"></i> 
        <strong>Note:</strong> Admin accounts have full access to the system dashboard.
    </div>
</div>

<button type="submit" name="register" class="btn">
    <i class="fas fa-user-plus"></i> Create Account
</button>

<div class="login-link">
Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
</div>

</form>

</div>

<script>
// Role switching functionality
const roleBtns = document.querySelectorAll('.role-btn');
const roleInput = document.getElementById('roleInput');
const parentFields = document.getElementById('parentFields');
const doctorFields = document.getElementById('doctorFields');
const adminFields = document.getElementById('adminFields');

// Required fields for each role
const parentRequired = ['child_name', 'child_age'];
const doctorRequired = ['specialty'];

function updateRequiredFields(role) {
    // Remove required from all role-specific fields
    parentRequired.forEach(field => {
        const el = document.getElementById(field);
        if(el) el.removeAttribute('required');
    });
    doctorRequired.forEach(field => {
        const el = document.getElementById(field);
        if(el) el.removeAttribute('required');
    });
    
    // Add required based on selected role
    if(role === 'parent') {
        parentRequired.forEach(field => {
            const el = document.getElementById(field);
            if(el) el.setAttribute('required', 'required');
        });
    } else if(role === 'doctor') {
        doctorRequired.forEach(field => {
            const el = document.getElementById(field);
            if(el) el.setAttribute('required', 'required');
        });
    }
}

roleBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const role = this.getAttribute('data-role');
        
        // Update active state
        roleBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Update hidden input
        roleInput.value = role;
        
        // Show/hide relevant fields
        parentFields.classList.remove('active');
        doctorFields.classList.remove('active');
        adminFields.classList.remove('active');
        
        if(role === 'parent') parentFields.classList.add('active');
        if(role === 'doctor') doctorFields.classList.add('active');
        if(role === 'admin') adminFields.classList.add('active');
        
        // Update required fields
        updateRequiredFields(role);
    });
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirm = document.querySelector('input[name="confirm_password"]').value;
    
    if(password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if(password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
        return false;
    }
    
    // Check password strength
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    if(!hasUpper || !hasLower || !hasNumber) {
        e.preventDefault();
        alert('Password must contain at least one uppercase letter, one lowercase letter, and one number!');
        return false;
    }
    
    // Role-specific validation
    const role = roleInput.value;
    
    if(role === 'parent') {
        const childName = document.getElementById('child_name').value;
        const childAge = document.getElementById('child_age').value;
        
        if(!childName || !childAge) {
            e.preventDefault();
            alert('Please fill in all child information fields!');
            return false;
        }
        
        if(childAge < 0 || childAge > 5) {
            e.preventDefault();
            alert('Child age must be between 0 and 5 years!');
            return false;
        }
    }
    
    if(role === 'doctor') {
        const specialty = document.getElementById('specialty').value;
        
        if(!specialty) {
            e.preventDefault();
            alert('Please select your specialty!');
            return false;
        }
    }
    
    return true;
});

// Initialize required fields
updateRequiredFields('<?php echo $selected_role; ?>');

// Phone number formatting
const phoneInput = document.querySelector('input[name="phone"]');
if(phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if(value.length > 0 && value.length <= 3) {
            value = '+' + value;
        } else if(value.length > 3 && value.length <= 6) {
            value = '+' + value.slice(0,3) + ' ' + value.slice(3);
        } else if(value.length > 6) {
            value = '+' + value.slice(0,3) + ' ' + value.slice(3,6) + ' ' + value.slice(6,10);
        }
        e.target.value = value;
    });
}
</script>

</body>
</html>