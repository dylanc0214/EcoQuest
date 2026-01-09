<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoQuest</title>
    <link  href="../assets/css/style.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
<body>
    <?php include("../includes/header.php"); ?>

    <main>
        <section class="hero">
            <div class="hero-left">
                <h1 class="hero-slogan">EcoQuest: Go Green, Earn <span>Rewards</span> 🌱</h1>
                <p class="hero-description">
                    Join weekly sustainability quests, upload proof, earn points, and make a real impact
                    through TREE PLANTING, rewards, and recognition. Be part of the APU Green Campus movement!
                </p>
                <a href="sign_up.php?action=register" class="btn-signup">Sign Up Today!</a>
                <p class="login-link">Already have an account? <a href="sign_up.php?action=login">Login</a></p>
            </div>

            <div class="hero-right" data-aos="zoom-out" data-aos-delay="200">
                <img src="../assets/images/hero_placeholder.png" alt="Lush green landscape with stream and foliage, symbolizing nature and impact">
            </div>
        </section>
    </main>

    <div class="homepage-content">
        <h2>Our Growing Community</h2>
        <div class="stat-1" data-aos="fade-right" data-aos-delay="600">
            <label>1,200+</label>
            <p>Quest Completed</p>
        </div>
        <div class="stat-2" data-aos="fade-left" data-aos-delay="600">
            <label>500+</label>
            <p>Active Student</p>
        </div>
        <div class="stat-3" data-aos="fade-right" data-aos-delay="600">
            <label>50+</label>
            <p>Campus Event</p>
        </div>
        <div class="img-tree" data-aos="fade-up" data-aos-anchor-placement="top-center">
            <img src="../assets/images/tree.png" alt="Just Some Tree">
        </div>

    </div>

    <!-- Features Section with Auto-scroll -->
    <section id="features" class="features-section">
        <div class="feature-section-header">
            <h2>Why Choose EcoQuest?</h2>
            <p>Everything you need to make a sustainable impact</p>
        </div>
        <div class="features-container">
            <div class="features-scroll">
                <div class="feature-card">
                    <div class="feature-icon-index">🎯</div>
                    <h3>Weekly Quests</h3>
                    <p>Join exciting eco-challenges every week. From tree planting to recycling drives, there's always something new to explore.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">⭐</div>
                    <h3>Earn Points</h3>
                    <p>Complete quests and earn points for every approved submission. Climb the leaderboard and become a campus eco-champion!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">🏅</div>
                    <h3>Collect Badges</h3>
                    <p>Unlock exclusive badges for your achievements. Show off your commitment to sustainability with unique rewards.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">💬</div>
                    <h3>Community Chat</h3>
                    <p>Connect with fellow eco-warriors. Share tips, celebrate wins, and build a community of sustainability advocates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">📊</div>
                    <h3>Track Progress</h3>
                    <p>Monitor your impact with detailed dashboards. See your points, badges, and quest completion history at a glance.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">🌳</div>
                    <h3>Campus Events</h3>
                    <p>Stay updated on upcoming green initiatives. Join tree planting events, workshops, and sustainability campaigns.</p>
                </div>
                <!-- Duplicate for infinite scroll -->
                <div class="feature-card">
                    <div class="feature-icon-index">🎯</div>
                    <h3>Weekly Quests</h3>
                    <p>Join exciting eco-challenges every week. From tree planting to recycling drives, there's always something new to explore.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-index">⭐</div>
                    <h3>Earn Points</h3>
                    <p>Complete quests and earn points for every approved submission. Climb the leaderboard and become a campus eco-champion!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="HIW-header">
            <h2>How It Works</h2>
            <p>Get started in three simple steps</p>
        </div>
        <div class="steps-container">
            <div class="step-card">
                <div class="step-number"><label>1</label></div>
                <div class="step-icon">👤</div>
                <h3>Create Account</h3>
                <p>Sign up in seconds with your email. Join the community of eco-conscious students and staff.</p>
            </div>
            <div class="step-connector"><label class="arrow-right">→</label> <label class="arrow-down">→</label></div>
            <div class="step-card">
                <div class="step-number"><label>2</label></div>
                <div class="step-icon">🎯</div>
                <h3>Join Quests</h3>
                <p>Browse weekly quests and pick challenges that interest you. Each quest has clear instructions and rewards.</p>
            </div>
            <div class="step-connector"><label class="arrow-right">→</label> <label class="arrow-down">→</label></div>
            <div class="step-card">
                <div class="step-number"><label>3</label></div>
                <div class="step-icon">🏆</div>
                <h3>Earn & Compete</h3>
                <p>Complete quests, submit proof, and earn points. Climb the leaderboard and unlock badges!</p>
            </div>
        </div>
    </section>

    <section  class="cta-section">
        <div class="cta-container">
            <div class="cta-content">
                <h2>Ready to Start Your Journey?</h2>
                <p>Join hundreds of students making a real difference in campus sustainability. Sign up today and start earning rewards!</p>
                <a href="<?php echo $base_path; ?>pages/sign_up.php?action=register" class="cta-btn">Join the movement →</a>
            </div>
            <div class="cta-img-content">
                <img src="../assets/images/cta-section.jpeg" alt="register picture">
            </div>
        </div>
    </section>

    <?php include("../includes/footer.php"); ?>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ 
            duration: 1200
        });
    </script>
</body>
</html>