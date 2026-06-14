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

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($doctor_id == 0){
    header("Location: dashboard.php");
    exit();
}

// Get doctor details
$doctor_query = mysqli_query($conn, "SELECT * FROM doctors WHERE id = '$doctor_id' AND is_active = 1");
$doctor = mysqli_fetch_assoc($doctor_query);

if(!$doctor){
    header("Location: dashboard.php");
    exit();
}

// Get doctor reviews
$reviews_query = mysqli_query($conn, "SELECT r.*, p.fullname FROM doctor_reviews r 
    JOIN parents p ON r.parent_id = p.id 
    WHERE r.doctor_id = '$doctor_id' 
    ORDER BY r.created_at DESC");

$reviews = [];
while($review = mysqli_fetch_assoc($reviews_query)){
    $reviews[] = $review;
}

// Check if current user has already reviewed
$user_reviewed = false;
$check_user_review = mysqli_query($conn, "SELECT id FROM doctor_reviews WHERE doctor_id='$doctor_id' AND parent_id='$parent_id'");
if(mysqli_num_rows($check_user_review) > 0){
    $user_reviewed = true;
}

// Handle review submission
if(isset($_POST['submit_review'])){
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review']);
    
    if($rating >= 1 && $rating <= 5 && !empty($review_text)){
        $insert = "INSERT INTO doctor_reviews (doctor_id, parent_id, rating, review) VALUES ('$doctor_id', '$parent_id', '$rating', '$review_text')";
        if(mysqli_query($conn, $insert)){
            // Update doctor's average rating
            $avg_query = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM doctor_reviews WHERE doctor_id='$doctor_id'");
            $avg_data = mysqli_fetch_assoc($avg_query);
            $new_rating = round($avg_data['avg_rating'], 1);
            $total_reviews = $avg_data['total'];
            mysqli_query($conn, "UPDATE doctors SET rating='$new_rating', total_reviews='$total_reviews' WHERE id='$doctor_id'");
            
            $_SESSION['success'] = "Thank you for your review!";
            header("Location: doctor_profile.php?id=$doctor_id");
            exit();
        } else {
            $_SESSION['error'] = "Failed to submit review. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Please provide a rating and review.";
    }
}

// Get upcoming appointments with this doctor
$appointments_query = mysqli_query($conn, "SELECT * FROM appointments WHERE parent_id='$parent_id' AND doctor_id='$doctor_id' AND status='confirmed' AND appointment_date >= CURDATE() ORDER BY appointment_date ASC LIMIT 1");
$next_appointment = mysqli_fetch_assoc($appointments_query);

// Calculate average rating display
$rating_display = number_format($doctor['rating'], 1);
$full_stars = floor($doctor['rating']);
$half_star = ($doctor['rating'] - $full_stars) >= 0.5 ? 1 : 0;
$empty_stars = 5 - $full_stars - $half_star;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Dr. <?php echo htmlspecialchars($doctor['name']); ?> | Marvelous Kids</title>
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
        .header h1 { font-size: 18px; font-weight: 700; }

        /* Content Area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Doctor Profile Card */
        .doctor-card {
            background: white;
            border-radius: 28px;
            padding: 24px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #eef2f8;
        }
        .doctor-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 16px;
        }
        .doctor-name {
            font-size: 22px;
            font-weight: 700;
            color: #1f3a5f;
            margin-bottom: 4px;
        }
        .doctor-specialty {
            font-size: 14px;
            color: #7f8c9a;
            margin-bottom: 12px;
        }
        .rating-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .stars {
            display: flex;
            gap: 4px;
        }
        .star-filled { color: #f59e0b; font-size: 18px; }
        .star-half { color: #f59e0b; font-size: 18px; position: relative; }
        .star-empty { color: #e2e8f0; font-size: 18px; }
        .rating-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f3a5f;
        }
        .experience-badge {
            display: inline-block;
            background: #e8f3fe;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            color: #1f6eeb;
            margin: 8px 0;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-primary {
            flex: 1;
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary {
            flex: 1;
            background: #eef3fc;
            color: #1f6eeb;
            border: none;
            padding: 12px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Info Section */
        .info-section {
            background: #f9fbfe;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #eef2f8;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f3a5f;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-text {
            font-size: 14px;
            color: #4b6b8f;
            line-height: 1.5;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e2ecf5;
        }
        .info-label {
            width: 100px;
            font-weight: 600;
            color: #1f3a5f;
            font-size: 13px;
        }
        .info-value {
            flex: 1;
            color: #4b6b8f;
            font-size: 13px;
        }

        /* Review Section */
        .review-card {
            background: white;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #eef2f8;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .reviewer-name {
            font-weight: 600;
            color: #1f3a5f;
            font-size: 14px;
        }
        .review-date {
            font-size: 10px;
            color: #9ab3cf;
        }
        .review-stars {
            margin-bottom: 8px;
        }
        .review-text {
            font-size: 13px;
            color: #4b6b8f;
            line-height: 1.4;
        }

        /* Write Review Form */
        .write-review {
            background: #f9fbfe;
            border-radius: 24px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #eef2f8;
        }
        .rating-select {
            display: flex;
            gap: 12px;
            margin: 12px 0;
        }
        .rating-star {
            font-size: 28px;
            cursor: pointer;
            color: #e2e8f0;
            transition: 0.2s;
        }
        .rating-star.selected {
            color: #f59e0b;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            margin: 12px 0;
        }
        .submit-btn {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
        }

        /* Alert Messages */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        /* Upcoming Appointment Card */
        .appointment-card {
            background: linear-gradient(135deg, #e8f3fe, #d9efff);
            border-radius: 20px;
            padding: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #1f6eeb;
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
            <div>Doctor Profile</div>
        </div>
        <h1>👨‍⚕️ Pediatric Specialist</h1>
    </div>

    <div class="content">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-success">✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Doctor Profile Card -->
        <div class="doctor-card">
            <div class="doctor-avatar">👨‍⚕️</div>
            <h2 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>
            <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></div>
            <div class="rating-container">
                <div class="stars">
                    <?php for($i = 0; $i < $full_stars; $i++): ?>
                        <span class="star-filled">★</span>
                    <?php endfor; ?>
                    <?php if($half_star): ?>
                        <span class="star-half">½</span>
                    <?php endif; ?>
                    <?php for($i = 0; $i < $empty_stars; $i++): ?>
                        <span class="star-empty">★</span>
                    <?php endfor; ?>
                </div>
                <span class="rating-value"><?php echo $rating_display; ?> (<?php echo $doctor['total_reviews']; ?> reviews)</span>
            </div>
            <div class="experience-badge">⭐ <?php echo $doctor['experience_years']; ?>+ years experience</div>
            
            <div class="action-buttons">
                <button class="btn-primary" onclick="bookAppointment(<?php echo $doctor['id']; ?>)">📅 Book Appointment</button>
                <button class="btn-secondary" onclick="chatWithDoctor(<?php echo $doctor['id']; ?>)">💬 Chat Now</button>
            </div>
        </div>

        <!-- Upcoming Appointment Reminder -->
        <?php if($next_appointment): ?>
        <div class="appointment-card">
            <div style="font-weight:600; margin-bottom:4px;">📌 Upcoming Appointment</div>
            <div style="font-size:13px;">📅 <?php echo date('F j, Y', strtotime($next_appointment['appointment_date'])); ?></div>
            <div style="font-size:13px;">⏰ <?php echo date('g:i A', strtotime($next_appointment['appointment_time'])); ?></div>
        </div>
        <?php endif; ?>

        <!-- About Doctor -->
        <div class="info-section">
            <div class="section-title">
                <span>📋 About Dr. <?php echo htmlspecialchars($doctor['name']); ?></span>
            </div>
            <div class="info-text">
                <?php echo !empty($doctor['bio']) ? htmlspecialchars($doctor['bio']) : 'Dr. ' . htmlspecialchars($doctor['name']) . ' is a highly respected ' . htmlspecialchars($doctor['specialty']) . ' with over ' . $doctor['experience_years'] . ' years of experience in pediatric care. Dedicated to providing compassionate, evidence-based medical care for children of all ages.'; ?>
            </div>
        </div>

        <!-- Doctor Details -->
        <div class="info-section">
            <div class="section-title">
                <span>ℹ️ Professional Information</span>
            </div>
            <div class="info-row">
                <div class="info-label">Specialty</div>
                <div class="info-value"><?php echo htmlspecialchars($doctor['specialty']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Experience</div>
                <div class="info-value"><?php echo $doctor['experience_years']; ?>+ years</div>
            </div>
            <div class="info-row">
                <div class="info-label">Consultation Fee</div>
                <div class="info-value">$<?php echo number_format($doctor['consultation_fee'], 2); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Languages</div>
                <div class="info-value">English, Spanish</div>
            </div>
        </div>

        <!-- Patient Reviews -->
        <div class="info-section">
            <div class="section-title">
                <span>⭐ Patient Reviews (<?php echo count($reviews); ?>)</span>
            </div>
            
            <?php if(count($reviews) > 0): ?>
                <?php foreach($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="reviewer-name"><?php echo htmlspecialchars($review['fullname']); ?></span>
                        <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                    <div class="review-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <span style="color: <?php echo $i <= $review['rating'] ? '#f59e0b' : '#e2e8f0'; ?>; font-size: 14px;">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="review-text"><?php echo htmlspecialchars($review['review']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="info-text" style="text-align: center; padding: 20px;">No reviews yet. Be the first to review!</div>
            <?php endif; ?>
        </div>

        <!-- Write a Review -->
        <?php if(!$user_reviewed): ?>
        <div class="write-review">
            <div class="section-title" style="margin-bottom: 8px;">
                <span>✍️ Write a Review</span>
            </div>
            <form method="POST" id="reviewForm">
                <div class="rating-select" id="ratingSelect">
                    <span class="rating-star" data-rating="1">★</span>
                    <span class="rating-star" data-rating="2">★</span>
                    <span class="rating-star" data-rating="3">★</span>
                    <span class="rating-star" data-rating="4">★</span>
                    <span class="rating-star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="selectedRating" required>
                <textarea name="review" rows="3" placeholder="Share your experience with Dr. <?php echo htmlspecialchars($doctor['name']); ?>..." required></textarea>
                <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
            </form>
        </div>
        <?php else: ?>
        <div class="info-section" style="text-align: center;">
            <div class="info-text">✅ Thank you for reviewing Dr. <?php echo htmlspecialchars($doctor['name']); ?>!</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="toastMsg" class="toast-msg"></div>

<script>
    // Rating star selection
    let selectedRating = 0;
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('selectedRating');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            selectedRating = rating;
            ratingInput.value = rating;
            
            stars.forEach((s, index) => {
                if(index < rating) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
        });
    });
    
    function showToast(msg) {
        const toast = document.getElementById('toastMsg');
        toast.innerText = msg;
        toast.style.opacity = '1';
        setTimeout(() => toast.style.opacity = '0', 2500);
    }
    
    function bookAppointment(doctorId) {
        window.location.href = `appointment_booking.php?doctor=${doctorId}`;
    }
    
    function chatWithDoctor(doctorId) {
        window.location.href = `chat.php?doctor_id=${doctorId}`;
    }
    
    // Validate review form
    document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
        if(selectedRating === 0) {
            e.preventDefault();
            showToast('Please select a rating');
            return false;
        }
        const reviewText = document.querySelector('textarea[name="review"]').value.trim();
        if(reviewText === '') {
            e.preventDefault();
            showToast('Please write your review');
            return false;
        }
    });
    
    setTimeout(() => {
        showToast(`💬 Learn more about Dr. <?php echo htmlspecialchars($doctor['name']); ?>`);
    }, 1000);
</script>
</body>
</html>