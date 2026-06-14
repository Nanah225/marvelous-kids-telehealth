<?php
session_start();
include 'db.php';

if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$fullname = $_SESSION['fullname'];
$child_name = $_SESSION['child_name'];

// Create tables if they don't exist (fallback)

// Insert default milestones if empty

// Get child's data for growth tracking
$child_dob = null;
$child_gender = 'female';
$child_height = null;
$child_weight = null;
$child_bmi = null;

// Check if child_profiles has height/weight columns
$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM child_profiles LIKE 'height'");
if(mysqli_num_rows($columns_check) == 0){
    mysqli_query($conn, "ALTER TABLE child_profiles ADD COLUMN height DECIMAL(5,2) DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE child_profiles ADD COLUMN weight DECIMAL(5,2) DEFAULT NULL");
}

$query = mysqli_query($conn, "SELECT * FROM child_profiles WHERE parent_id='$parent_id'");
$child_data = mysqli_fetch_assoc($query);
if($child_data){
    $child_name = $child_data['child_name'] ?? $child_name;
    $child_dob = $child_data['dob'] ?? null;
    $child_gender = $child_data['gender'] ?? 'female';
    $child_height = $child_data['height'] ?? null;
    $child_weight = $child_data['weight'] ?? null;
    
    if($child_height && $child_weight && $child_height > 0){
        $child_bmi = round($child_weight / (($child_height/100) * ($child_height/100)), 1);
    }
}

// Calculate age
$child_age_years = 0;
$child_age_months = 0;
if($child_dob){
    $birth = new DateTime($child_dob);
    $today = new DateTime();
    $age_diff = $birth->diff($today);
    $child_age_years = $age_diff->y;
    $child_age_months = ($age_diff->y * 12) + $age_diff->m;
}

// Handle growth data submission
if(isset($_POST['save_growth'])){
    $new_height = floatval($_POST['height']);
    $new_weight = floatval($_POST['weight']);
    $record_date = mysqli_real_escape_string($conn, $_POST['record_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    $insert = "INSERT INTO growth_records (parent_id, height, weight, record_date, notes) 
               VALUES ('$parent_id', '$new_height', '$new_weight', '$record_date', '$notes')";
    if(mysqli_query($conn, $insert)){
        // Update child profile with latest measurements
        mysqli_query($conn, "UPDATE child_profiles SET height='$new_height', weight='$new_weight' WHERE parent_id='$parent_id'");
        $_SESSION['success'] = "Growth data saved successfully!";
        header("Location: growth.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to save growth data: " . mysqli_error($conn);
        header("Location: growth.php");
        exit();
    }
}

// Get growth history
$growth_history = [];
$history_query = mysqli_query($conn, "SELECT * FROM growth_records WHERE parent_id='$parent_id' ORDER BY record_date DESC LIMIT 10");
if($history_query){
    while($row = mysqli_fetch_assoc($history_query)){
        $growth_history[] = $row;
    }
}

// Get milestones for current age
$milestones = [];
$milestone_query = mysqli_query($conn, "SELECT * FROM growth_milestones WHERE age_months <= $child_age_months ORDER BY age_months ASC");
if($milestone_query){
    while($row = mysqli_fetch_assoc($milestone_query)){
        $milestones[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Growth Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }

        .phone {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            height: 780px;
        }

        /* Header */
        .header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 20px;
            color: white;
            border-radius: 48px 48px 28px 28px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .back-btn {
            background: rgba(255,255,255,0.2);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            font-size: 20px;
        }
        .header h1 { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .child-info { font-size: 11px; opacity: 0.9; margin-top: 6px; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 20px;
            background: white;
        }
        .stat-card {
            background: linear-gradient(135deg, #f0f7ff, #e8f3fe);
            padding: 14px;
            border-radius: 20px;
            text-align: center;
        }
        .stat-value { font-size: 28px; font-weight: 800; color: #1f6eeb; }
        .stat-label { font-size: 11px; color: #7f8c9a; margin-top: 4px; }

        /* Content Area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 0 16px 20px;
        }

        /* Section Title */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f3a5f;
            margin: 20px 0 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .view-all { font-size: 11px; color: #1f6eeb; text-decoration: none; cursor: pointer; }

        /* Growth Input Card */
        .input-card {
            background: linear-gradient(135deg, #f9fbfe, #ffffff);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #eef2f8;
        }
        .input-group {
            margin-bottom: 16px;
        }
        .input-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4b6b8f;
            display: block;
            margin-bottom: 6px;
        }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            font-size: 14px;
            font-family: inherit;
        }
        .save-btn {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
        }

        /* Growth Chart */
        .chart-container {
            background: #f9fbfe;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #eef2f8;
        }
        .chart-bars {
            margin-top: 16px;
        }
        .bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .bar-label {
            width: 60px;
            font-size: 12px;
            font-weight: 600;
            color: #1f3a5f;
        }
        .bar-fill {
            flex: 1;
            height: 30px;
            background: #e2ecf5;
            border-radius: 15px;
            overflow: hidden;
        }
        .bar-progress {
            height: 100%;
            background: linear-gradient(90deg, #1f6eeb, #16b3a3);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            color: white;
            font-size: 11px;
            font-weight: 600;
        }

        /* Milestone Cards */
        .milestone-card {
            background: white;
            border-radius: 20px;
            padding: 14px;
            margin-bottom: 12px;
            border-left: 4px solid #1f6eeb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .milestone-age {
            font-size: 10px;
            color: #1f6eeb;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .milestone-text {
            font-size: 14px;
            color: #1f3a5f;
        }

        /* History Table */
        .history-table {
            background: #f9fbfe;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .history-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f8;
            font-size: 13px;
        }
        .history-header {
            background: #e8f3fe;
            font-weight: 700;
            color: #1f3a5f;
        }

        /* Alert */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 16px;
            margin: 16px;
            font-size: 13px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 16px;
            margin: 16px;
            font-size: 13px;
        }

        .toast-msg {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 12px;
            opacity: 0;
            transition: 0.2s;
            z-index: 1000;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="phone">
    <div class="header">
        <div class="header-top">
            <a href="dashboard.php" class="back-btn">←</a>
            <div>📈 Growth Tracker</div>
        </div>
        <h1>📊 Child Growth</h1>
        <div class="child-info">👶 <?php echo htmlspecialchars($child_name); ?> • <?php echo $child_age_years; ?> years <?php echo $child_age_months%12; ?> months</div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-success">✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $child_height ? $child_height . ' cm' : '--'; ?></div>
            <div class="stat-label">Height</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $child_weight ? $child_weight . ' kg' : '--'; ?></div>
            <div class="stat-label">Weight</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $child_bmi ? $child_bmi : '--'; ?></div>
            <div class="stat-label">BMI</div>
        </div>
    </div>

    <div class="content">
        <!-- Growth Input Form -->
        <div class="section-title">📝 Record Measurements</div>
        <form method="POST" class="input-card">
            <div class="input-group">
                <label>📏 Height (cm)</label>
                <input type="number" step="0.1" name="height" placeholder="Enter height in cm" value="<?php echo $child_height; ?>" required>
            </div>
            <div class="input-group">
                <label>⚖️ Weight (kg)</label>
                <input type="number" step="0.1" name="weight" placeholder="Enter weight in kg" value="<?php echo $child_weight; ?>" required>
            </div>
            <div class="input-group">
                <label>📅 Date of Measurement</label>
                <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="input-group">
                <label>📝 Notes (optional)</label>
                <textarea name="notes" rows="2" placeholder="Any additional notes about growth..."></textarea>
            </div>
            <button type="submit" name="save_growth" class="save-btn">💾 Save Growth Data</button>
        </form>

        <!-- Growth Chart Visualization -->
        <div class="section-title">📈 Growth Chart</div>
        <div class="chart-container">
            <div class="chart-bars">
                <div class="bar-item">
                    <div class="bar-label">Height</div>
                    <div class="bar-fill"><div class="bar-progress" style="width: <?php echo $child_height ? min(100, ($child_height/120)*100) : 0; ?>%"><?php echo $child_height ? $child_height . ' cm' : 'Not recorded'; ?></div></div>
                </div>
                <div class="bar-item">
                    <div class="bar-label">Weight</div>
                    <div class="bar-fill"><div class="bar-progress" style="width: <?php echo $child_weight ? min(100, ($child_weight/30)*100) : 0; ?>%"><?php echo $child_weight ? $child_weight . ' kg' : 'Not recorded'; ?></div></div>
                </div>
            </div>
            <div style="font-size: 11px; color: #7f8c9a; margin-top: 12px; text-align: center;">
                💡 Compare with WHO growth standards. Consult pediatrician for concerns.
            </div>
        </div>

        <!-- Growth History -->
        <?php if(count($growth_history) > 0): ?>
        <div class="section-title">📜 Growth History</div>
        <div class="history-table">
            <div class="history-row history-header">
                <span>Date</span>
                <span>Height</span>
                <span>Weight</span>
            </div>
            <?php foreach($growth_history as $record): ?>
            <div class="history-row">
                <span><?php echo date('M d, Y', strtotime($record['record_date'])); ?></span>
                <span><?php echo $record['height'] ? $record['height'] . ' cm' : '--'; ?></span>
                <span><?php echo $record['weight'] ? $record['weight'] . ' kg' : '--'; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Development Milestones -->
        <div class="section-title">
            <span>🎯 Development Milestones</span>
            <span class="view-all"><?php echo count($milestones); ?> achieved</span>
        </div>
        <div>
            <?php if(count($milestones) > 0): ?>
                <?php foreach($milestones as $milestone): ?>
                <div class="milestone-card">
                    <div class="milestone-age">✓ Age: <?php echo floor($milestone['age_months']/12); ?>y <?php echo $milestone['age_months']%12; ?>m</div>
                    <div class="milestone-text"><?php echo htmlspecialchars($milestone['milestone']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="milestone-card"><div class="milestone-text">✨ Start tracking your child's growth by adding measurements above!</div></div>
            <?php endif; ?>
        </div>

        <!-- Growth Tips -->
        <div class="section-title">💡 Growth Tips</div>
        <div class="milestone-card">
            <div class="milestone-text">🥗 <strong>Proper Nutrition:</strong> Ensure balanced diet with proteins, calcium, vitamin D, and iron for optimal growth.</div>
        </div>
        <div class="milestone-card">
            <div class="milestone-text">😴 <strong>Adequate Sleep:</strong> Children need 10-14 hours of sleep depending on age for growth hormone release.</div>
        </div>
        <div class="milestone-card">
            <div class="milestone-text">🏃‍♂️ <strong>Physical Activity:</strong> 60 minutes of active play daily supports healthy bone and muscle development.</div>
        </div>
        <div class="milestone-card">
            <div class="milestone-text">🩺 <strong>Regular Checkups:</strong> Schedule pediatric visits to track growth percentiles and development.</div>
        </div>
    </div>
</div>

<div id="toastMsg" class="toast-msg"></div>

<script>
    function showToast(msg) {
        const toast = document.getElementById('toastMsg');
        toast.innerText = msg;
        toast.style.opacity = '1';
        setTimeout(() => toast.style.opacity = '0', 2500);
    }

    // BMI Interpretation
    <?php if($child_bmi): ?>
    setTimeout(() => {
        let bmi = <?php echo $child_bmi; ?>;
        let message = '';
        let age = <?php echo $child_age_years; ?>;
        
        if(age < 2){
            message = 'Growth is measured using WHO weight-for-length charts';
        } else if(bmi < 14) {
            message = 'Underweight - Consider consulting pediatrician';
        } else if(bmi > 18.5) {
            message = 'Overweight - Focus on balanced nutrition and activity';
        } else {
            message = 'Healthy weight range - Great job! Keep it up!';
        }
        showToast(`BMI: ${bmi} - ${message}`);
    }, 1500);
    <?php endif; ?>
</script>
</body>
</html>