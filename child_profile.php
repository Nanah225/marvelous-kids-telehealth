<?php
session_start();
include 'db.php';

if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$message = "";
$error = "";

// Fetch existing child profile data
$query = mysqli_query($conn, "SELECT * FROM child_profiles WHERE parent_id = '$parent_id'");
$existing = mysqli_fetch_assoc($query);

// Handle form submission
if(isset($_POST['save'])){
    
    $child_name = mysqli_real_escape_string($conn, trim($_POST['child_name']));
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $allergies = mysqli_real_escape_string($conn, trim($_POST['allergies']));
    $medical_conditions = mysqli_real_escape_string($conn, trim($_POST['medical_conditions']));
    $medications = mysqli_real_escape_string($conn, trim($_POST['medications']));
    $emergency_contact = mysqli_real_escape_string($conn, trim($_POST['emergency_contact']));
    $pediatrician_name = mysqli_real_escape_string($conn, trim($_POST['pediatrician_name']));
    $pediatrician_phone = mysqli_real_escape_string($conn, trim($_POST['pediatrician_phone']));
    $last_checkup = mysqli_real_escape_string($conn, $_POST['last_checkup']);
    $next_appointment = mysqli_real_escape_string($conn, $_POST['next_appointment']);
    
    // Validation
    if(empty($child_name)) {
        $error = "Child name is required!";
    } elseif(!empty($dob) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) {
        $error = "Invalid date format!";
    } else {
        
        // Calculate age from DOB
        $age_years = null;
        $age_months = null;
        if(!empty($dob)) {
            $birth = new DateTime($dob);
            $today = new DateTime();
            $age_interval = $birth->diff($today);
            $age_years = $age_interval->y;
            $age_months = $age_interval->m;
        }
        
        // Check if profile exists
        $check = mysqli_query($conn, "SELECT * FROM child_profiles WHERE parent_id='$parent_id'");
        
        if(mysqli_num_rows($check) > 0){
            $update = "UPDATE child_profiles SET 
                child_name='$child_name',
                gender='$gender',
                dob='$dob',
                age_years='$age_years',
                age_months='$age_months',
                weight='$weight',
                height='$height',
                blood_group='$blood_group',
                allergies='$allergies',
                medical_conditions='$medical_conditions',
                medications='$medications',
                emergency_contact='$emergency_contact',
                pediatrician_name='$pediatrician_name',
                pediatrician_phone='$pediatrician_phone',
                last_checkup='$last_checkup',
                next_appointment='$next_appointment'
                WHERE parent_id='$parent_id'";
            
            if(mysqli_query($conn, $update)){
                $message = "Child profile updated successfully!";
                // Refresh data
                $query = mysqli_query($conn, "SELECT * FROM child_profiles WHERE parent_id='$parent_id'");
                $existing = mysqli_fetch_assoc($query);
            } else {
                $error = "Failed to update profile: " . mysqli_error($conn);
            }
        } else {
            $insert = "INSERT INTO child_profiles(
                parent_id, child_name, gender, dob, age_years, age_months,
                weight, height, blood_group, allergies, medical_conditions,
                medications, emergency_contact, pediatrician_name,
                pediatrician_phone, last_checkup, next_appointment
            ) VALUES(
                '$parent_id', '$child_name', '$gender', '$dob', '$age_years', '$age_months',
                '$weight', '$height', '$blood_group', '$allergies', '$medical_conditions',
                '$medications', '$emergency_contact', '$pediatrician_name',
                '$pediatrician_phone', '$last_checkup', '$next_appointment'
            )";
            
            if(mysqli_query($conn, $insert)){
                $message = "Child profile created successfully!";
                $query = mysqli_query($conn, "SELECT * FROM child_profiles WHERE parent_id='$parent_id'");
                $existing = mysqli_fetch_assoc($query);
            } else {
                $error = "Failed to create profile: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Child Health Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Phone Frame */
        .phone {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25), 0 0 0 6px #f8faff, 0 0 0 12px #8bb5d1;
        }

        /* Header */
        .profile-header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 24px 20px;
            color: white;
            text-align: center;
        }

        .profile-header h1 {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .profile-header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 6px;
        }

        /* Content Area */
        .content {
            padding: 24px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .content::-webkit-scrollbar {
            width: 4px;
        }
        .content::-webkit-scrollbar-track {
            background: #e2ecf5;
            border-radius: 10px;
        }
        .content::-webkit-scrollbar-thumb {
            background: #9ab3cf;
            border-radius: 10px;
        }

        /* Alert Messages */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 14px;
            border-radius: 20px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 20px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Sections */
        .form-section {
            background: #f9fbfe;
            border-radius: 28px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e7f0fa;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f3a5f;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2ecf5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b6b8f;
            margin-bottom: 6px;
        }

        .required::after {
            content: " *";
            color: #ef4444;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #1f6eeb;
            box-shadow: 0 0 0 3px rgba(31, 110, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Age Display */
        .age-badge {
            background: linear-gradient(135deg, #1f6eeb10, #16b3a310);
            padding: 12px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .age-badge span {
            font-size: 24px;
            font-weight: 800;
            color: #1f6eeb;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
            margin-top: 10px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 12px;
            margin-bottom: 12px;
            border-left: 4px solid #1f6eeb;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .phone {
                border-radius: 32px;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="phone">
    <!-- Header -->
    <div class="profile-header">
        <div class="header-nav">
            <a href="dashboard.php" class="back-btn">
                ← Back
            </a>
        </div>
        <h1>
            👶 Child Health Profile
        </h1>
        <p>Complete medical record for your little one</p>
    </div>

    <!-- Content -->
    <div class="content">
        <?php if($message): ?>
            <div class="alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Age Display (if DOB exists) -->
        <?php if(!empty($existing['dob'])): 
            $birth = new DateTime($existing['dob']);
            $today = new DateTime();
            $age = $birth->diff($today);
        ?>
        <div class="age-badge">
            📅 Age: <span><?php echo $age->y; ?> years <?php echo $age->m; ?> months</span>
        </div>
        <?php endif; ?>

        <form method="POST" id="childProfileForm">
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-title">
                    📋 Basic Information
                </div>
                
                <div class="form-group">
                    <label class="required">Child's Full Name</label>
                    <input type="text" name="child_name" value="<?php echo htmlspecialchars($existing['child_name'] ?? ''); ?>" placeholder="Enter child's full name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male" <?php echo (($existing['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($existing['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($existing['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($existing['dob'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($existing['weight'] ?? ''); ?>" placeholder="e.g., 15.5">
                    </div>
                    
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" step="0.1" name="height" value="<?php echo htmlspecialchars($existing['height'] ?? ''); ?>" placeholder="e.g., 105">
                    </div>
                </div>

                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group">
                        <option value="">Select Blood Group</option>
                        <?php 
                        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach($blood_groups as $bg): 
                        ?>
                            <option value="<?php echo $bg; ?>" <?php echo (($existing['blood_group'] ?? '') == $bg) ? 'selected' : ''; ?>>
                                <?php echo $bg; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Medical Information Section -->
            <div class="form-section">
                <div class="section-title">
                    🏥 Medical Information
                </div>

                <div class="form-group">
                    <label>Allergies</label>
                    <textarea name="allergies" rows="3" placeholder="List any allergies (medications, foods, environmental, etc.)"><?php echo htmlspecialchars($existing['allergies'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Chronic Conditions / Medical History</label>
                    <textarea name="medical_conditions" rows="3" placeholder="Asthma, diabetes, heart conditions, etc."><?php echo htmlspecialchars($existing['medical_conditions'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Current Medications</label>
                    <textarea name="medications" rows="2" placeholder="List any medications with dosages"><?php echo htmlspecialchars($existing['medications'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Pediatrician & Emergency Section -->
            <div class="form-section">
                <div class="section-title">
                    👨‍⚕️ Pediatrician & Emergency Contacts
                </div>

                <div class="form-group">
                    <label>Pediatrician Name</label>
                    <input type="text" name="pediatrician_name" value="<?php echo htmlspecialchars($existing['pediatrician_name'] ?? ''); ?>" placeholder="Dr. ...">
                </div>

                <div class="form-group">
                    <label>Pediatrician Phone</label>
                    <input type="tel" name="pediatrician_phone" value="<?php echo htmlspecialchars($existing['pediatrician_phone'] ?? ''); ?>" placeholder="+1234567890">
                </div>

                <div class="form-group">
                    <label>Emergency Contact Name & Number</label>
                    <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($existing['emergency_contact'] ?? ''); ?>" placeholder="Name: Phone number">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Last Checkup Date</label>
                        <input type="date" name="last_checkup" value="<?php echo htmlspecialchars($existing['last_checkup'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Appointment</label>
                        <input type="date" name="next_appointment" value="<?php echo htmlspecialchars($existing['next_appointment'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Growth Chart Info -->
            <div class="form-section">
                <div class="section-title">
                    📊 Growth & Development Notes
                </div>
                <div class="info-card">
                    💡 <strong>Health Tips:</strong> Regular checkups help track developmental milestones. Keep this profile updated for better care.
                </div>
            </div>

            <button type="submit" name="save" class="submit-btn">
                💾 Save Child Profile
            </button>
        </form>
    </div>
</div>

<script>
    // Auto-calculate age preview (client-side)
    const dobInput = document.querySelector('input[name="dob"]');
    const ageDisplay = document.querySelector('.age-badge');
    
    if(dobInput && ageDisplay) {
        dobInput.addEventListener('change', function() {
            if(this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let years = today.getFullYear() - birthDate.getFullYear();
                let months = today.getMonth() - birthDate.getMonth();
                if (months < 0) {
                    years--;
                    months += 12;
                }
                if(years >= 0) {
                    ageDisplay.innerHTML = `📅 Age: <span>${years} years ${months} months</span>`;
                }
            }
        });
    }
    
    // Form validation before submit
    document.getElementById('childProfileForm')?.addEventListener('submit', function(e) {
        const childName = document.querySelector('input[name="child_name"]').value.trim();
        if(!childName) {
            e.preventDefault();
            alert('Please enter child name');
            return false;
        }
    });
    
    // Auto-hide alerts after 4 seconds
    setTimeout(() => {
        const success = document.querySelector('.alert-success');
        const error = document.querySelector('.alert-error');
        if(success) success.style.opacity = '0';
        if(error) error.style.opacity = '0';
        setTimeout(() => {
            if(success) success.style.display = 'none';
            if(error) error.style.display = 'none';
        }, 500);
    }, 4000);
</script>

</body>
</html>

