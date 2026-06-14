<?php
session_start();
include 'db.php';

// Check if parent is logged in
if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$fullname = $_SESSION['fullname'];

// Get current subscription
$query = mysqli_query($conn, "SELECT subscription_plan, subscription_expiry FROM parents WHERE id = '$parent_id'");
$current = mysqli_fetch_assoc($query);
$current_plan = $current['subscription_plan'] ?? 'Basic';
$expiry_date = $current['subscription_expiry'] ?? null;

// Handle subscription payment
if(isset($_POST['process_payment'])) {
    $selected_plan = mysqli_real_escape_string($conn, $_POST['plan']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_name = isset($_POST['bank_name']) ? mysqli_real_escape_string($conn, $_POST['bank_name']) : '';
    
    // In real implementation, integrate with payment gateway here
    // For demo, we'll simulate successful payment
    
    $success = true; // Simulate successful payment
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    if($success) {
        $new_expiry = date('Y-m-d', strtotime('+30 days'));
        $update_query = "UPDATE parents SET subscription_plan = '$selected_plan', subscription_expiry = '$new_expiry' WHERE id = '$parent_id'";
        
        if(mysqli_query($conn, $update_query)) {
            // Log transaction
            $log_query = "INSERT INTO payment_transactions (parent_id, plan, amount, payment_method, bank_name, transaction_id, status, created_at) 
                          VALUES ('$parent_id', '$selected_plan', 
                          '".($selected_plan == 'Premium' ? '9.99' : '14.99')."', 
                          '$payment_method', '$bank_name', '$transaction_id', 'completed', NOW())";
            mysqli_query($conn, $log_query);
            
            $_SESSION['success'] = "🎉 Subscription successful! Your $selected_plan plan is now active. Transaction ID: $transaction_id";
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update subscription. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Payment failed. Please try again.";
    }
}

// Plans data
$plans = [
    'Premium' => [
        'price' => 9.99,
        'features' => [
            '24/7 Chat Support',
            'Video Consultations',
            'Unlimited Appointments',
            'Growth Tracking',
            'Vaccine Reminders',
            'Priority Support'
        ],
        'popular' => true
    ],
    'Family' => [
        'price' => 14.99,
        'features' => [
            'All Premium Features',
            'Multiple Children Support',
            'Emergency Hotline',
            'Home Visit Option',
            'Family Health Records',
            '24/7 Priority Support'
        ],
        'popular' => false
    ]
];

// Payment methods
$payment_methods = [
    'Orange Money' => ['icon' => '🟠', 'fields' => ['phone_number']],
    'Afri Money' => ['icon' => '🌍', 'fields' => ['phone_number']],
    'Q Money' => ['icon' => '💳', 'fields' => ['phone_number']],
    'Visa Card' => ['icon' => '💳', 'fields' => ['card_number', 'expiry_date', 'cvv']],
    'Bank Transfer' => ['icon' => '🏦', 'fields' => ['bank_name', 'account_number']]
];

// Banks list for bank transfer
$banks = [
    'Access Bank', 'Zenith Bank', 'GTBank', 'First Bank', 'UBA', 
    'Ecobank', 'Fidelity Bank', 'Stanbic IBTC', 'Union Bank', 'Sterling Bank'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Subscription Plans | Marvelous Kids</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            color: #1f3a5f;
            margin-bottom: 10px;
        }

        .header p {
            color: #2c3e66;
            font-size: 16px;
        }

        .current-plan {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .current-plan-badge {
            display: inline-block;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .plan-card {
            background: white;
            border-radius: 30px;
            padding: 30px;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .plan-card.selected {
            border-color: #1f6eeb;
            background: #f0f7ff;
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .plan-name {
            font-size: 24px;
            font-weight: 800;
            color: #1f3a5f;
            margin-bottom: 15px;
        }

        .plan-price {
            font-size: 36px;
            font-weight: 800;
            color: #1f6eeb;
            margin-bottom: 20px;
        }

        .plan-price small {
            font-size: 14px;
            font-weight: normal;
            color: #7f8c9a;
        }

        .features-list {
            list-style: none;
            margin: 20px 0;
        }

        .features-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #4a5568;
        }

        .features-list li:before {
            content: "✓";
            color: #1f6eeb;
            font-weight: bold;
            font-size: 18px;
        }

        .select-plan-btn {
            width: 100%;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .select-plan-btn:hover {
            opacity: 0.9;
        }

        .payment-section {
            background: white;
            border-radius: 30px;
            padding: 30px;
            margin-top: 20px;
            display: none;
        }

        .payment-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .payment-method {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #1f6eeb;
            background: #f0f7ff;
        }

        .payment-method.selected {
            border-color: #1f6eeb;
            background: #e6f0ff;
        }

        .payment-method-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .payment-method-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f3a5f;
        }

        .payment-form {
            margin-top: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #1f3a5f;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #1f6eeb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .pay-now-btn {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }

        .pay-now-btn:hover {
            transform: scale(1.02);
        }

        .back-btn {
            display: inline-block;
            background: #e2e8f0;
            color: #1f3a5f;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 30px;
            margin-top: 20px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #cbd5e1;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🌟 Choose Your Plan</h1>
        <p>Get access to premium healthcare features for your child</p>
    </div>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="current-plan">
        <div class="current-plan-badge">Current Plan: <?php echo $current_plan; ?></div>
        <?php if($expiry_date && $expiry_date > date('Y-m-d')): ?>
            <p style="margin-top: 10px;">✅ Active until: <?php echo date('F d, Y', strtotime($expiry_date)); ?></p>
        <?php else: ?>
            <p style="margin-top: 10px; color: #ef4444;">⚠️ Your plan has expired. Please renew to continue enjoying benefits.</p>
        <?php endif; ?>
    </div>

    <div class="plans-grid">
        <?php foreach($plans as $plan_name => $plan): ?>
        <div class="plan-card" data-plan="<?php echo $plan_name; ?>">
            <?php if($plan['popular']): ?>
                <div class="popular-badge">🔥 Most Popular</div>
            <?php endif; ?>
            <div class="plan-name"><?php echo $plan_name; ?></div>
            <div class="plan-price">$<?php echo $plan['price']; ?><small>/month</small></div>
            <ul class="features-list">
                <?php foreach($plan['features'] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                <?php endforeach; ?>
            </ul>
            <button class="select-plan-btn" onclick="selectPlan('<?php echo $plan_name; ?>')">
                <?php echo $current_plan == $plan_name ? 'Current Plan' : 'Select Plan'; ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Section -->
    <div id="paymentSection" class="payment-section">
        <h2 style="margin-bottom: 20px;">💳 Complete Payment</h2>
        <p style="margin-bottom: 20px;">Selected Plan: <strong id="selectedPlanName"></strong> - <strong id="selectedPlanPrice"></strong></p>
        
        <h3>Select Payment Method</h3>
        <div class="payment-methods" id="paymentMethods">
            <?php foreach($payment_methods as $method => $details): ?>
                <div class="payment-method" data-method="<?php echo $method; ?>">
                    <div class="payment-method-icon"><?php echo $details['icon']; ?></div>
                    <div class="payment-method-name"><?php echo $method; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" id="paymentForm">
            <input type="hidden" name="plan" id="selectedPlanInput">
            <input type="hidden" name="payment_method" id="selectedMethodInput">
            <div id="dynamicFields" class="payment-form"></div>
            <button type="submit" name="process_payment" class="pay-now-btn">💳 Pay Now</button>
        </form>
    </div>

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>

<script>
    let selectedPlan = null;
    let selectedMethod = null;

    function selectPlan(plan) {
        selectedPlan = plan;
        const price = plan === 'Premium' ? '9.99' : '14.99';
        document.getElementById('selectedPlanName').innerText = plan;
        document.getElementById('selectedPlanPrice').innerText = '$' + price + '/month';
        document.getElementById('selectedPlanInput').value = plan;
        
        // Highlight selected plan card
        document.querySelectorAll('.plan-card').forEach(card => {
            card.classList.remove('selected');
            if(card.getAttribute('data-plan') === plan) {
                card.classList.add('selected');
            }
        });
        
        // Show payment section
        document.getElementById('paymentSection').classList.add('active');
        document.getElementById('paymentSection').scrollIntoView({ behavior: 'smooth' });
    }

    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            selectedMethod = this.getAttribute('data-method');
            document.getElementById('selectedMethodInput').value = selectedMethod;
            loadDynamicFields(selectedMethod);
        });
    });

    function loadDynamicFields(method) {
        const fieldsContainer = document.getElementById('dynamicFields');
        let html = '';
        
        switch(method) {
            case 'Orange Money':
            case 'Afri Money':
            case 'Q Money':
                html = `
                    <div class="form-group">
                        <label>📱 Phone Number</label>
                        <input type="tel" name="phone_number" placeholder="Enter your mobile money number" required>
                    </div>
                    <div class="form-group">
                        <label>💰 Amount</label>
                        <input type="text" value="$${document.getElementById('selectedPlanPrice').innerText}" disabled style="background:#e2e8f0;">
                    </div>
                    <p style="font-size:12px; color:#666; margin-top:10px;">You will receive a prompt on your phone to complete payment.</p>
                `;
                break;
                
            case 'Visa Card':
                html = `
                    <div class="form-group">
                        <label>💳 Card Number</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>📅 Expiry Date</label>
                            <input type="text" name="expiry_date" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label>🔐 CVV</label>
                            <input type="password" name="cvv" placeholder="123" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>👤 Cardholder Name</label>
                        <input type="text" name="cardholder_name" placeholder="Name on card" required>
                    </div>
                `;
                break;
                
            case 'Bank Transfer':
                const banks = <?php echo json_encode($banks); ?>;
                html = `
                    <div class="form-group">
                        <label>🏦 Select Bank</label>
                        <select name="bank_name" required>
                            <option value="">Choose your bank</option>
                            ${banks.map(bank => `<option value="${bank}">${bank}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🔢 Account Number</label>
                        <input type="text" name="account_number" placeholder="Your account number" required>
                    </div>
                    <div class="form-group">
                        <label>👤 Account Name</label>
                        <input type="text" name="account_name" placeholder="Account holder name" required>
                    </div>
                    <p style="font-size:12px; color:#666; margin-top:10px;">You will be redirected to your bank's secure payment page.</p>
                `;
                break;
        }
        
        fieldsContainer.innerHTML = html;
    }

    // Form validation before submit
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        if(!selectedPlan) {
            e.preventDefault();
            alert('Please select a plan first');
            return false;
        }
        if(!selectedMethod) {
            e.preventDefault();
            alert('Please select a payment method');
            return false;
        }
        
        // Show processing message
        const submitBtn = this.querySelector('.pay-now-btn');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = '⏳ Processing Payment...';
        submitBtn.disabled = true;
        
        // Allow form to submit normally
        return true;
    });
</script>
</body>
</html>