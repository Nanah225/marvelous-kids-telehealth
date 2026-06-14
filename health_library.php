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

// Get child age for personalized content
$child_age_years = null;
$query = mysqli_query($conn, "SELECT dob FROM child_profiles WHERE parent_id='$parent_id'");
if($profile = mysqli_fetch_assoc($query)){
    if(!empty($profile['dob'])){
        $birth = new DateTime($profile['dob']);
        $today = new DateTime();
        $age = $birth->diff($today);
        $child_age_years = $age->y;
    }
}

// Determine age group for recommendations
$age_group = "toddler";
if($child_age_years !== null){
    if($child_age_years < 1) $age_group = "infant";
    elseif($child_age_years < 3) $age_group = "toddler";
    elseif($child_age_years < 6) $age_group = "preschool";
    elseif($child_age_years < 12) $age_group = "school";
    else $age_group = "teen";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Health Library</title>
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

        .phone {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
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
            transition: 0.2s;
        }
        .back-btn:active { transform: scale(0.95); }
        .header h1 { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .age-recommendation { font-size: 11px; opacity: 0.9; margin-top: 6px; }

        /* Search Bar */
        .search-section {
            padding: 16px;
            background: white;
            border-bottom: 1px solid #eef2f8;
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
        .search-box button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        /* Category Tabs */
        .categories {
            display: flex;
            gap: 12px;
            padding: 16px;
            overflow-x: auto;
            background: white;
            border-bottom: 1px solid #eef2f8;
        }
        .category-btn {
            background: #f0f4f9;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: 0.2s;
        }
        .category-btn.active {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
        }
        .category-btn:active { transform: scale(0.96); }

        /* Content Area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        /* Featured Section */
        .featured-card {
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e0e7ff;
        }
        .featured-badge {
            background: #1f6eeb;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
            margin-bottom: 12px;
        }
        .featured-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f3a5f;
            margin-bottom: 8px;
        }
        .featured-desc {
            font-size: 13px;
            color: #4b6b8f;
            margin-bottom: 16px;
            line-height: 1.4;
        }
        .watch-btn {
            background: #1f6eeb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 13px;
            cursor: pointer;
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
        .section-title:first-of-type { margin-top: 0; }

        /* Video Grid */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .video-card {
            background: #f9fbfe;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid #eef2f8;
        }
        .video-card:active { transform: scale(0.97); }
        .video-thumb {
            width: 100%;
            height: 110px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        .video-info {
            padding: 10px;
        }
        .video-title {
            font-size: 13px;
            font-weight: 600;
            color: #1f3a5f;
            line-height: 1.3;
        }
        .video-duration {
            font-size: 10px;
            color: #9ab3cf;
            margin-top: 4px;
        }

        /* Article List */
        .article-list {
            margin-bottom: 24px;
        }
        .article-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #eef2f8;
            cursor: pointer;
        }
        .article-icon {
            width: 50px;
            height: 50px;
            background: #e8f3fe;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .article-content {
            flex: 1;
        }
        .article-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f3a5f;
            margin-bottom: 4px;
        }
        .article-summary {
            font-size: 12px;
            color: #7f8c9a;
            line-height: 1.3;
        }
        .read-more {
            color: #1f6eeb;
            font-size: 11px;
            margin-top: 4px;
            display: inline-block;
        }

        /* Health Talk Cards */
        .talk-card {
            background: white;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 14px;
            border: 1px solid #eef2f8;
            display: flex;
            gap: 14px;
            cursor: pointer;
        }
        .talk-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .talk-info {
            flex: 1;
        }
        .talk-title {
            font-size: 15px;
            font-weight: 700;
            color: #1f3a5f;
        }
        .talk-speaker {
            font-size: 12px;
            color: #1f6eeb;
            margin: 4px 0;
        }
        .talk-desc {
            font-size: 12px;
            color: #7f8c9a;
        }

        /* Modal for Video/Article */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 3000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .modal-content {
            width: 90%;
            max-width: 400px;
            background: white;
            border-radius: 32px;
            overflow: hidden;
        }
        .modal-video {
            width: 100%;
            height: 240px;
            background: #000;
        }
        .modal-video iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .modal-text {
            padding: 20px;
        }
        .close-modal {
            position: absolute;
            top: 40px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-btn {
            background: #1f6eeb;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            width: 100%;
            margin-top: 16px;
            cursor: pointer;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 10px 18px;
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
            <div>📚 Health Library</div>
        </div>
        <h1>📖 Learn & Grow</h1>
        <div class="age-recommendation">🎯 Personalized for <?php echo ucfirst($age_group); ?> • <?php echo htmlspecialchars($child_name); ?></div>
    </div>

    <div class="search-section">
        <div class="search-box">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search articles, videos, health topics...">
            <button id="searchBtn">⚡</button>
        </div>
    </div>

    <div class="categories">
        <button class="category-btn active" data-category="all">All</button>
        <button class="category-btn" data-category="vaccines">💉 Vaccines</button>
        <button class="category-btn" data-category="nutrition">🥗 Nutrition</button>
        <button class="category-btn" data-category="development">📈 Development</button>
        <button class="category-btn" data-category="safety">🛡️ Safety</button>
        <button class="category-btn" data-category="common_illness">🤒 Common Illness</button>
    </div>

    <div class="content" id="contentContainer">
        <!-- Content will be dynamically loaded -->
        <div style="text-align: center; padding: 40px;">Loading health resources...</div>
    </div>
</div>

<!-- Modal for Videos/Articles -->
<div id="mediaModal" class="modal">
    <div class="close-modal" onclick="closeModal()">&times;</div>
    <div class="modal-content" id="modalContent">
        <div class="modal-video" id="modalVideo"></div>
        <div class="modal-text" id="modalText"></div>
    </div>
</div>

<div id="toastMsg" class="toast"></div>

<script>
    // Complete Health Library Database
    const healthLibrary = {
        videos: [
            { id: 1, title: "Understanding Baby Vaccines: What Every Parent Should Know", category: "vaccines", duration: "8:24", thumbnail: "🎥", youtubeId: "dQw4w9WgXcQ", description: "Complete guide to childhood immunization schedule and safety." },
            { id: 2, title: "Healthy Eating Habits for Toddlers", category: "nutrition", duration: "6:15", thumbnail: "🥗", youtubeId: "dQw4w9WgXcQ", description: "Tips for picky eaters and balanced nutrition." },
            { id: 3, title: "Baby Development Milestones: 0-12 Months", category: "development", duration: "10:30", thumbnail: "👶", youtubeId: "dQw4w9WgXcQ", description: "Track your baby's growth and development stages." },
            { id: 4, title: "Child Safety at Home: Prevention Guide", category: "safety", duration: "7:45", thumbnail: "🏠", youtubeId: "dQw4w9WgXcQ", description: "Childproofing your home and preventing accidents." },
            { id: 5, title: "Managing Fever in Children", category: "common_illness", duration: "5:20", thumbnail: "🌡️", youtubeId: "dQw4w9WgXcQ", description: "When to worry and home care tips for fever." },
            { id: 6, title: "Introducing Solid Foods: A Complete Guide", category: "nutrition", duration: "9:12", thumbnail: "🍎", youtubeId: "dQw4w9WgXcQ", description: "When and how to start solids safely." }
        ],
        articles: [
            { id: 1, title: "Complete Vaccination Schedule for Children", category: "vaccines", summary: "CDC recommended immunization schedule from birth to 18 years.", icon: "💉", content: "<p>Vaccines are crucial for protecting children from serious diseases. The recommended schedule includes Hepatitis B at birth, DTaP at 2 months, MMR at 12-15 months, and boosters through childhood. Always consult your pediatrician for personalized advice.</p><p>Key vaccines: Hepatitis B, DTaP, Hib, PCV, Polio, MMR, Varicella, HPV (age 11-12).</p>" },
            { id: 2, title: "10 Superfoods for Growing Kids", category: "nutrition", summary: "Nutrient-dense foods that support brain development and growth.", icon: "🥑", content: "<p>Superfoods include: Eggs (choline for brain), Yogurt (probiotics for gut), Salmon (omega-3s), Sweet potatoes (vitamin A), Berries (antioxidants), Nuts (healthy fats), Oats (fiber), Broccoli (calcium), Beans (protein), Avocados (healthy fats).</p>" },
            { id: 3, title: "Recognizing Developmental Delays Early", category: "development", summary: "Early signs and when to seek professional help.", icon: "📊", content: "<p>Warning signs: Not smiling by 3 months, not sitting by 9 months, not walking by 18 months, not speaking by 2 years. Early intervention is key for best outcomes.</p>" },
            { id: 4, title: "Childproofing Your Home: Room by Room Guide", category: "safety", summary: "Essential safety measures for every area of your home.", icon: "🔒", content: "<p>Kitchen: Lock cabinets, use stove guards. Bathroom: Set water heater to 120°F, use non-slip mats. Living room: Anchor furniture, cover outlets. Stairs: Install safety gates.</p>" },
            { id: 5, title: "Common Childhood Illnesses: Symptoms & Care", category: "common_illness", summary: "Guide to managing colds, flu, ear infections, and more.", icon: "🤒", content: "<p>Common illnesses include: Common cold (rest, fluids), Flu (fever, body aches - antivirals if early), Ear infection (pain, fever - antibiotics sometimes), Strep throat (sore throat - antibiotics needed). Always consult doctor for persistent symptoms.</p>" },
            { id: 6, title: "Baby Sleep Training Methods", category: "development", summary: "Gentle approaches to help your baby sleep through the night.", icon: "😴", content: "<p>Sleep training methods: Ferber method (gradual checking), Chair method (gradual distance), Pick-up-put-down (soothing then setting down). Consistency is key for success.</p>" },
            { id: 7, title: "Allergy Prevention in Infants", category: "common_illness", summary: "Latest research on introducing allergens early.", icon: "🥜", content: "<p>New guidelines recommend introducing peanuts, eggs, and other allergens between 4-6 months to prevent allergies. Start with small amounts and watch for reactions.</p>" },
            { id: 8, title: "Screen Time Guidelines by Age", category: "safety", summary: "Healthy limits for digital devices.", icon: "📱", content: "<p>Under 18 months: No screen time except video chat. 18-24 months: Limited high-quality programming. 2-5 years: 1 hour per day. 6+ years: Consistent limits, prioritize sleep and activity.</p>" }
        ],
        healthTalks: [
            { id: 1, title: "Childhood Obesity Prevention", speaker: "Dr. Sarah Johnson", specialty: "Pediatric Nutrition", description: "Understanding healthy weight management in children.", duration: "45 min", date: "March 25, 2026" },
            { id: 2, title: "Mental Health in Teens", speaker: "Dr. Michael Chen", specialty: "Adolescent Psychiatry", description: "Recognizing anxiety, depression, and when to seek help.", duration: "50 min", date: "April 5, 2026" },
            { id: 3, title: "The Importance of Play in Development", speaker: "Dr. Emily Rodriguez", specialty: "Child Development", description: "How play shapes brain development and social skills.", duration: "35 min", date: "April 12, 2026" }
        ]
    };

    let currentCategory = "all";
    let searchTerm = "";

    function showToast(msg) {
        const toast = document.getElementById("toastMsg");
        toast.innerText = msg;
        toast.style.opacity = "1";
        setTimeout(() => toast.style.opacity = "0", 2500);
    }

    function renderContent() {
        const container = document.getElementById("contentContainer");
        
        // Filter videos
        let filteredVideos = healthLibrary.videos.filter(v => 
            (currentCategory === "all" || v.category === currentCategory) &&
            (searchTerm === "" || v.title.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        
        // Filter articles
        let filteredArticles = healthLibrary.articles.filter(a => 
            (currentCategory === "all" || a.category === currentCategory) &&
            (searchTerm === "" || a.title.toLowerCase().includes(searchTerm.toLowerCase()) || a.summary.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        
        // Filter health talks
        let filteredTalks = healthLibrary.healthTalks.filter(t => 
            searchTerm === "" || t.title.toLowerCase().includes(searchTerm.toLowerCase()) || t.speaker.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        let html = `
            <!-- Featured Content -->
            <div class="featured-card">
                <div class="featured-badge">⭐ Featured Health Talk</div>
                <div class="featured-title">🎙️ Weekly Health Webinar</div>
                <div class="featured-desc">Join our live session with pediatric experts. This week: "Building Strong Immunity in Children"</div>
                <button class="watch-btn" onclick="showToast('Registration opens soon! Stay tuned.')">📅 Register Now →</button>
            </div>
        `;
        
        // Videos Section
        if(filteredVideos.length > 0) {
            html += `<div class="section-title">
                        <span>🎬 Educational Videos</span>
                        <span style="font-size: 12px;">${filteredVideos.length} videos</span>
                    </div>
                    <div class="video-grid">`;
            filteredVideos.forEach(video => {
                html += `
                    <div class="video-card" onclick="playVideo(${video.id})">
                        <div class="video-thumb" style="background: linear-gradient(135deg, #1f6eeb, #16b3a3);">
                            <div class="play-overlay">▶️</div>
                            <div style="position: absolute; bottom: 8px; right: 8px; font-size: 20px;">${video.thumbnail}</div>
                        </div>
                        <div class="video-info">
                            <div class="video-title">${video.title}</div>
                            <div class="video-duration">⏱️ ${video.duration}</div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }
        
        // Articles Section
        if(filteredArticles.length > 0) {
            html += `<div class="section-title">
                        <span>📖 Health Articles</span>
                        <span style="font-size: 12px;">${filteredArticles.length} articles</span>
                    </div>
                    <div class="article-list">`;
            filteredArticles.forEach(article => {
                html += `
                    <div class="article-item" onclick="openArticle(${article.id})">
                        <div class="article-icon">${article.icon}</div>
                        <div class="article-content">
                            <div class="article-title">${article.title}</div>
                            <div class="article-summary">${article.summary.substring(0, 80)}...</div>
                            <div class="read-more">Read more →</div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }
        
        // Health Talks Section
        if(filteredTalks.length > 0 && currentCategory === "all") {
            html += `<div class="section-title">🎙️ Upcoming Health Talks</div>`;
            filteredTalks.forEach(talk => {
                html += `
                    <div class="talk-card" onclick="showToast('Registration opens soon! Mark your calendar.')">
                        <div class="talk-avatar">🎤</div>
                        <div class="talk-info">
                            <div class="talk-title">${talk.title}</div>
                            <div class="talk-speaker">👨‍⚕️ ${talk.speaker} • ${talk.specialty}</div>
                            <div class="talk-desc">${talk.description}</div>
                            <div class="talk-desc">📅 ${talk.date} • ⏱️ ${talk.duration}</div>
                        </div>
                    </div>
                `;
            });
        }
        
        if(filteredVideos.length === 0 && filteredArticles.length === 0) {
            html = `<div style="text-align: center; padding: 60px 20px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📚</div>
                        <div style="color: #7f8c9a;">No results found for "${searchTerm}"</div>
                        <div style="color: #9ab3cf; font-size: 13px; margin-top: 8px;">Try different keywords or browse categories</div>
                    </div>`;
        }
        
        container.innerHTML = html;
    }
    
    function playVideo(videoId) {
        const video = healthLibrary.videos.find(v => v.id === videoId);
        if(video) {
            const modal = document.getElementById("mediaModal");
            const modalVideo = document.getElementById("modalVideo");
            const modalText = document.getElementById("modalText");
            
            modalVideo.innerHTML = `<iframe src="https://www.youtube.com/embed/${video.youtubeId}" frameborder="0" allowfullscreen></iframe>`;
            modalText.innerHTML = `
                <h3 style="margin-bottom: 8px;">${video.title}</h3>
                <p style="color: #666; font-size: 14px;">${video.description}</p>
                <button class="modal-btn" onclick="closeModal()">Close</button>
            `;
            modal.style.display = "flex";
            showToast(`Now playing: ${video.title}`);
        }
    }
    
    function openArticle(articleId) {
        const article = healthLibrary.articles.find(a => a.id === articleId);
        if(article) {
            const modal = document.getElementById("mediaModal");
            const modalVideo = document.getElementById("modalVideo");
            const modalText = document.getElementById("modalText");
            
            modalVideo.style.display = "none";
            modalText.innerHTML = `
                <h3 style="margin-bottom: 12px;">${article.title}</h3>
                ${article.content}
                <button class="modal-btn" onclick="closeModal()">Close</button>
            `;
            modal.style.display = "flex";
            showToast(`Reading: ${article.title}`);
        }
    }
    
    function closeModal() {
        document.getElementById("mediaModal").style.display = "none";
        document.getElementById("modalVideo").innerHTML = "";
        document.getElementById("modalVideo").style.display = "block";
    }
    
    // Search and filter
    document.getElementById("searchInput").addEventListener("input", (e) => {
        searchTerm = e.target.value;
        renderContent();
    });
    
    document.getElementById("searchBtn").addEventListener("click", () => {
        searchTerm = document.getElementById("searchInput").value;
        renderContent();
    });
    
    document.querySelectorAll(".category-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".category-btn").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            currentCategory = btn.dataset.category;
            renderContent();
        });
    });
    
    // Load content on page load
    renderContent();
    
    // Add interactive links to featured content
    function initLinks() {
        // Make sure all external links show toast
        const allInteractive = document.querySelectorAll('[onclick]');
        console.log("Health library ready with " + allInteractive.length + " interactive elements");
    }
    
    setTimeout(initLinks, 500);
</script>
</body>
</html>