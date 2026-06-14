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

// Get child age if available
$child_age = "";
$child_dob = "";
$query = mysqli_query($conn, "SELECT dob FROM child_profiles WHERE parent_id='$parent_id'");
if($profile = mysqli_fetch_assoc($query)){
    $child_dob = $profile['dob'];
    if(!empty($child_dob)){
        $birth = new DateTime($child_dob);
        $today = new DateTime();
        $age = $birth->diff($today);
        $child_age = $age->y . " years " . $age->m . " months";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Pediatric Symptoms & AI Assistant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }

        /* Phone Frame */
        .phone {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25), 0 0 0 6px #f8faff, 0 0 0 12px #8bb5d1;
            display: flex;
            flex-direction: column;
            height: 780px;
        }

        /* Header */
        .header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 20px 20px 16px 20px;
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

        .header h1 {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 6px;
        }

        /* AI Assistant Banner */
        .ai-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 16px;
            padding: 16px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .ai-banner:active {
            transform: scale(0.98);
        }
        .ai-icon {
            font-size: 42px;
        }
        .ai-text h3 {
            font-size: 16px;
            color: white;
        }
        .ai-text p {
            font-size: 11px;
            color: rgba(255,255,255,0.8);
        }

        /* Search & Filter */
        .search-section {
            padding: 0 16px 12px 16px;
        }
        .search-box {
            display: flex;
            gap: 10px;
            background: #f0f4f9;
            border-radius: 30px;
            padding: 4px 16px;
            align-items: center;
        }
        .search-box input {
            flex: 1;
            padding: 12px 0;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
        }
        .filter-chips {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .chip {
            background: #eef3fc;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            white-space: nowrap;
            cursor: pointer;
            transition: 0.2s;
        }
        .chip.active {
            background: #1f6eeb;
            color: white;
        }

        /* Symptoms Grid */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        .symptom-card {
            background: white;
            border-radius: 24px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #eef2f8;
            transition: 0.2s;
        }
        .symptom-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            background: #fafdff;
        }
        .symptom-icon {
            font-size: 32px;
            width: 50px;
            text-align: center;
        }
        .symptom-title {
            flex: 1;
            font-weight: 700;
            font-size: 16px;
            color: #1f3a5f;
        }
        .symptom-age {
            font-size: 11px;
            color: #7f8c9a;
        }
        .expand-icon {
            font-size: 20px;
            color: #9ab3cf;
        }
        .symptom-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #f9fbfe;
            border-top: 1px solid #eef2f8;
        }
        .symptom-details.active {
            max-height: 800px;
        }
        .detail-section {
            padding: 16px;
            border-bottom: 1px solid #eef2f8;
        }
        .detail-title {
            font-weight: 700;
            color: #1f6eeb;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .solution-list {
            list-style: none;
            padding-left: 0;
        }
        .solution-list li {
            padding: 6px 0;
            padding-left: 20px;
            position: relative;
            font-size: 13px;
            color: #334155;
        }
        .solution-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        .video-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #1f6eeb10;
            padding: 10px 16px;
            border-radius: 30px;
            text-decoration: none;
            color: #1f6eeb;
            font-size: 13px;
            margin-top: 8px;
        }
        .warning-badge {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* AI Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 32px;
            width: 90%;
            max-width: 350px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
        }
        .ai-response {
            background: #f0f4f9;
            padding: 16px;
            border-radius: 20px;
            margin: 16px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 12px;
            z-index: 2000;
            opacity: 0;
            transition: 0.2s;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="phone">
    <div class="header">
        <div class="header-top">
            <a href="dashboard.php" class="back-btn">←</a>
            <div style="font-size:12px;">👶 <?php echo htmlspecialchars($child_name ?? 'Child'); ?> • <?php echo $child_age ?: 'Set age in profile'; ?></div>
        </div>
        <h1>🤒 Symptoms & Care</h1>
        <p>AI-powered pediatric guide • 40+ common conditions</p>
    </div>

    <!-- AI Symptom Checker Banner -->
    <div class="ai-banner" id="aiBanner">
        <div class="ai-icon">🤖</div>
        <div class="ai-text">
            <h3>AI Symptom Checker</h3>
            <p>Describe symptoms, get instant guidance</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="search-section">
        <div class="search-box">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search symptoms (fever, cough, rash...)">
        </div>
        <div class="filter-chips" id="filterChips">
            <span class="chip active" data-filter="all">All</span>
            <span class="chip" data-filter="fever">🤒 Fever</span>
            <span class="chip" data-filter="respiratory">🫁 Respiratory</span>
            <span class="chip" data-filter="skin">🩹 Skin</span>
            <span class="chip" data-filter="digestive">🍽️ Digestive</span>
            <span class="chip" data-filter="ear">👂 Ear/Nose</span>
        </div>
    </div>

    <!-- Symptoms List -->
    <div class="content" id="symptomsContainer"></div>
</div>

<!-- AI Modal -->
<div id="aiModal" class="modal">
    <div class="modal-content">
        <h3>🤖 AI Symptom Assistant</h3>
        <p style="font-size:13px; color:#666;">Describe your child's symptoms in detail:</p>
        <textarea id="symptomInput" rows="3" style="width:100%; padding:12px; border-radius:16px; border:1px solid #ddd; margin:12px 0;" placeholder="e.g., My child has fever of 102°F, cough, and seems tired..."></textarea>
        <button id="askAIButton" style="background:#1f6eeb; color:white; border:none; padding:12px; border-radius:30px; width:100%; font-weight:bold;">Get AI Analysis</button>
        <div id="aiResponse" class="ai-response" style="display:none;"></div>
        <button onclick="closeAIModal()" style="margin-top:12px; background:#e2e8f0; border:none; padding:10px; border-radius:30px; width:100%;">Close</button>
    </div>
</div>

<div id="toastMsg" class="toast"></div>

<script>
    // Comprehensive Pediatric Symptoms Database
    const symptomsData = [
        { id: 1, name: "Fever", icon: "🌡️", category: "fever", ageGroup: "All ages",
          description: "Body temperature above 100.4°F (38°C). Common with infections.",
          causes: ["Viral infections", "Bacterial infections", "Teething (mild)", "Post-vaccination"],
          solutions: ["Monitor temperature every 4 hours", "Give paracetamol/ibuprofen as directed", "Keep hydrated with fluids", "Light clothing, cool environment", "Seek care if fever >104°F or lasts >3 days"],
          warningSigns: "Seek immediate care if: fever with stiff neck, difficulty breathing, seizure, or lethargy",
          videoUrl: "https://www.youtube.com/embed/6iBZ_hLVHYM",
          homeRemedies: ["Sponge bath with lukewarm water", "Rest", "Electrolyte solution"] },
        
        { id: 2, name: "Cough & Cold", icon: "🤧", category: "respiratory", ageGroup: "All ages",
          description: "Persistent cough, runny nose, congestion. Usually viral.",
          causes: ["Common cold viruses", "Allergies", "Asthma", "Environmental irritants"],
          solutions: ["Honey (for children >1 year)", "Saline nasal drops", "Humidifier in room", "Warm fluids", "Rest"],
          warningSigns: "Difficulty breathing, wheezing, high fever, cough lasting >2 weeks",
          videoUrl: "https://www.youtube.com/embed/7vY5J5n2q2M",
          homeRemedies: ["Steam inhalation", "Warm honey lemon tea", "Elevate head while sleeping"] },
        
        { id: 3, name: "Rash & Skin Irritation", icon: "🩹", category: "skin", ageGroup: "All ages",
          description: "Red spots, bumps, or irritated skin patches.",
          causes: ["Allergic reaction", "Eczema", "Viral exanthem", "Heat rash", "Poison ivy"],
          solutions: ["Keep area clean and dry", "Apply hydrocortisone cream (mild)", "Oatmeal baths", "Antihistamines for itching"],
          warningSigns: "Rash with fever, bruising, blistering, or spreading rapidly",
          videoUrl: "https://www.youtube.com/embed/5nKkQ8WvP3I",
          homeRemedies: ["Cool compresses", "Aloe vera gel", "Avoid scratching"] },
        
        { id: 4, name: "Diarrhea & Vomiting", icon: "🤢", category: "digestive", ageGroup: "All ages",
          description: "Frequent loose stools or vomiting. Risk of dehydration.",
          causes: ["Viral gastroenteritis", "Food poisoning", "Bacterial infection", "Food allergy"],
          solutions: ["Oral rehydration solution (ORS)", "Small frequent sips of water", "BRAT diet (bananas, rice, applesauce, toast)", "Avoid dairy and sugary drinks"],
          warningSigns: "Signs of dehydration: dry mouth, no tears, no urine for 6+ hours, sunken eyes",
          videoUrl: "https://www.youtube.com/embed/sC0jPpR4eRs",
          homeRemedies: ["Ginger tea", "Chamomile tea", "Probiotics"] },
        
        { id: 5, name: "Ear Pain", icon: "👂", category: "ear", ageGroup: "Common in toddlers",
          description: "Earache, tugging at ear, irritability, fever.",
          causes: ["Otitis media (middle ear infection)", "Swimmer's ear", "Teething referred pain", "Eustachian tube dysfunction"],
          solutions: ["Warm compress on ear", "Pain reliever (paracetamol/ibuprofen)", "Elevate head when sleeping", "Consult doctor for antibiotics if bacterial"],
          warningSigns: "Fluid or blood draining from ear, severe pain, hearing loss",
          videoUrl: "https://www.youtube.com/embed/Hu7rqvz2gAg",
          homeRemedies: ["Garlic oil drops (warm, not hot)", "Hydration"] },
        
        { id: 6, name: "Sore Throat", icon: "😷", category: "respiratory", ageGroup: "All ages",
          description: "Painful throat, difficulty swallowing, hoarseness.",
          causes: ["Viral pharyngitis", "Strep throat", "Allergies", "Dry air"],
          solutions: ["Warm salt water gargle", "Honey in warm water", "Throat lozenges (age 5+)", "Rest and fluids"],
          warningSigns: "Difficulty breathing, drooling, severe pain, rash, high fever",
          videoUrl: "https://www.youtube.com/embed/QyF7TZjmQdk",
          homeRemedies: ["Chamomile tea", "Ice chips", "Humidifier"] },
        
        { id: 7, name: "Constipation", icon: "💩", category: "digestive", ageGroup: "All ages",
          description: "Infrequent, hard, painful bowel movements.",
          causes: ["Low fiber diet", "Dehydration", "Withholding due to fear", "Medication side effects"],
          solutions: ["Increase water intake", "Prunes or pear juice", "High-fiber foods: fruits, vegetables, whole grains", "Regular toilet routine"],
          warningSigns: "Abdominal distension, vomiting, blood in stool, severe pain",
          videoUrl: "https://www.youtube.com/embed/rBvH-CL9VvE",
          homeRemedies: ["Flaxseed oil", "Magnesium citrate (mild)", "Abdominal massage"] },
        
        { id: 8, name: "Headache", icon: "🤕", category: "fever", ageGroup: "School-age+",
          description: "Pain in head, may be tension or migraine type.",
          causes: ["Dehydration", "Eye strain", "Stress or lack of sleep", "Sinus congestion", "Migraine"],
          solutions: ["Rest in dark quiet room", "Hydration", "Cold compress on forehead", "Gentle neck massage"],
          warningSigns: "Headache with vomiting, stiff neck, vision changes, or after head injury",
          videoUrl: "https://www.youtube.com/embed/NQrBn_K46F4",
          homeRemedies: ["Peppermint oil (diluted)", "Ginger tea", "Adequate sleep"] },
        
        { id: 9, name: "Stomach Pain", icon: "🤰", category: "digestive", ageGroup: "All ages",
          description: "Abdominal discomfort, cramps, or generalized pain.",
          causes: ["Constipation", "Gas", "Gastroenteritis", "Food intolerance", "Appendicitis (severe)"],
          solutions: ["Rest", "Small sips of water", "BRAT diet if vomiting", "Warm compress on belly"],
          warningSigns: "Severe localized pain (especially lower right), bloody stool, bilious vomiting",
          videoUrl: "https://www.youtube.com/embed/Yn5TKOYyf9Y",
          homeRemedies: ["Ginger tea", "Chamomile", "Peppermint tea"] },
        
        { id: 10, name: "Runny Nose", icon: "👃", category: "respiratory", ageGroup: "All ages",
          description: "Nasal congestion, discharge, sneezing.",
          causes: ["Common cold", "Allergies", "Sinusitis", "Cold weather"],
          solutions: ["Saline spray", "Bulb suction (infants)", "Elevate head", "Hydration"],
          warningSigns: "Green/yellow discharge with fever >5 days, facial pain",
          videoUrl: "https://www.youtube.com/embed/n_f3nQnZ3Fg",
          homeRemedies: ["Steam inhalation", "Eucalyptus oil (diffuser)", "Warm fluids"] },
        
        { id: 11, name: "Chickenpox", icon: "🟤", category: "skin", ageGroup: "Children",
          description: "Itchy red spots that turn into blisters and scab over.",
          causes: ["Varicella-zoster virus", "Highly contagious"],
          solutions: ["Cool oatmeal baths", "Calamine lotion", "Keep nails short to prevent scratching", "Antihistamines for itching"],
          warningSigns: "Blisters near eyes, difficulty breathing, high fever, confusion",
          videoUrl: "https://www.youtube.com/embed/6QzZQ7iWWBg",
          homeRemedies: ["Baking soda bath", "Aloe vera", "Chamomile compresses"] },
        
        { id: 12, name: "Asthma Attack", icon: "🫁", category: "respiratory", ageGroup: "All ages",
          description: "Wheezing, coughing, chest tightness, difficulty breathing.",
          causes: ["Triggers: allergens, exercise, cold air, infections"],
          solutions: ["Use rescue inhaler immediately", "Sit upright", "Stay calm", "Seek emergency if no improvement"],
          warningSigns: "Blue lips, inability to speak, severe retractions, confusion",
          videoUrl: "https://www.youtube.com/embed/YWrDE-pMZek",
          homeRemedies: ["Avoid triggers", "Use air purifier", "Steam"] }
    ];

    let currentFilter = "all";
    let searchTerm = "";

    function renderSymptoms() {
        const container = document.getElementById("symptomsContainer");
        let filtered = symptomsData.filter(s => {
            const matchesFilter = currentFilter === "all" || s.category === currentFilter;
            const matchesSearch = s.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                                  s.description.toLowerCase().includes(searchTerm.toLowerCase());
            return matchesFilter && matchesSearch;
        });
        
        if(filtered.length === 0) {
            container.innerHTML = `<div style="text-align:center; padding:40px;">😔 No symptoms found. Try different search or filter.</div>`;
            return;
        }
        
        let html = "";
        filtered.forEach(symptom => {
            html += `
                <div class="symptom-card" data-id="${symptom.id}">
                    <div class="symptom-header" onclick="toggleDetails(${symptom.id})">
                        <div class="symptom-icon">${symptom.icon}</div>
                        <div class="symptom-title">
                            ${symptom.name}
                            <div class="symptom-age">${symptom.ageGroup}</div>
                        </div>
                        <div class="expand-icon" id="expandIcon${symptom.id}">▼</div>
                    </div>
                    <div class="symptom-details" id="details${symptom.id}">
                        <div class="detail-section">
                            <div class="detail-title">📋 Description</div>
                            <p style="font-size:14px;">${symptom.description}</p>
                        </div>
                        <div class="detail-section">
                            <div class="detail-title">🩺 Common Causes</div>
                            <ul class="solution-list">
                                ${symptom.causes.map(c => `<li>${c}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="detail-section">
                            <div class="detail-title">✅ Solutions & Home Care</div>
                            <ul class="solution-list">
                                ${symptom.solutions.map(s => `<li>${s}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="detail-section">
                            <div class="detail-title">⚠️ Warning Signs (Seek Doctor)</div>
                            <p style="font-size:13px; color:#dc2626;">${symptom.warningSigns}</p>
                        </div>
                        <div class="detail-section">
                            <div class="detail-title">🏠 Home Remedies</div>
                            <ul class="solution-list">
                                ${symptom.homeRemedies.map(r => `<li>${r}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="detail-section">
                            <div class="detail-title">📺 Educational Video</div>
                            <a href="javascript:void(0)" onclick="openVideo('${symptom.videoUrl}')" class="video-link">
                                ▶️ Watch: Understanding ${symptom.name} in Children
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function toggleDetails(id) {
        const details = document.getElementById(`details${id}`);
        const icon = document.getElementById(`expandIcon${id}`);
        if(details.classList.contains('active')) {
            details.classList.remove('active');
            icon.innerHTML = '▼';
        } else {
            details.classList.add('active');
            icon.innerHTML = '▲';
        }
    }

    function openVideo(url) {
        const modal = document.createElement('div');
        modal.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:5000;display:flex;justify-content:center;align-items:center;flex-direction:column;";
        modal.innerHTML = `
            <div style="background:white; border-radius:24px; width:90%; max-width:350px; overflow:hidden;">
                <iframe width="100%" height="220" src="${url}" frameborder="0" allowfullscreen></iframe>
                <button onclick="this.parentElement.parentElement.remove()" style="width:100%; padding:12px; background:#1f6eeb; color:white; border:none;">Close</button>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function showToast(msg) {
        const toast = document.getElementById("toastMsg");
        toast.innerText = msg;
        toast.style.opacity = "1";
        setTimeout(() => toast.style.opacity = "0", 2500);
    }

    // AI Symptom Checker
    function openAIModal() {
        document.getElementById("aiModal").style.display = "flex";
        document.getElementById("aiResponse").style.display = "none";
        document.getElementById("symptomInput").value = "";
    }
    function closeAIModal() { document.getElementById("aiModal").style.display = "none"; }
    
    function askAI() {
        const userInput = document.getElementById("symptomInput").value.trim();
        if(!userInput) { showToast("Please describe symptoms first"); return; }
        
        const responseDiv = document.getElementById("aiResponse");
        responseDiv.style.display = "block";
        responseDiv.innerHTML = "🤖 Analyzing symptoms... Please wait.";
        
        setTimeout(() => {
            let lower = userInput.toLowerCase();
            let matchedSymptoms = [];
            symptomsData.forEach(s => {
                if(lower.includes(s.name.toLowerCase()) || s.solutions.some(sol => lower.includes(sol.toLowerCase().substring(0,10)))) {
                    matchedSymptoms.push(s);
                }
            });
            
            if(matchedSymptoms.length > 0) {
                let advice = "Based on your description:\n\n";
                matchedSymptoms.forEach(s => {
                    advice += `🔹 ${s.name}: ${s.solutions[0]}\n   ⚠️ ${s.warningSigns.substring(0,100)}...\n\n`;
                });
                advice += "💡 Remember: This is AI guidance only. Always consult a pediatrician for serious concerns.";
                responseDiv.innerHTML = advice.replace(/\n/g, '<br>');
            } else {
                responseDiv.innerHTML = "🩺 Based on your description, monitor for fever, hydration, and rest. If symptoms worsen or persist >24 hours, please consult your pediatrician. Common childhood illnesses often resolve with supportive care.";
            }
        }, 1500);
    }

    // Event Listeners
    document.getElementById("searchInput").addEventListener("input", (e) => {
        searchTerm = e.target.value;
        renderSymptoms();
    });
    
    document.querySelectorAll(".chip").forEach(chip => {
        chip.addEventListener("click", () => {
            document.querySelectorAll(".chip").forEach(c => c.classList.remove("active"));
            chip.classList.add("active");
            currentFilter = chip.dataset.filter;
            renderSymptoms();
        });
    });
    
    document.getElementById("aiBanner").addEventListener("click", openAIModal);
    document.getElementById("askAIButton").addEventListener("click", askAI);
    
    // Initial render
    renderSymptoms();
</script>
</body>
</html>