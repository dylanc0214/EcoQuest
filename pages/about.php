<?php
// pages/about.php
session_start();

include("../includes/header.php");

// Array of team members (for the card layout)
$team_members = [
        ['name' => 'Team Leader', 'role' => 'Project Manager & PHP Backend', 'img' => 'https://placehold.co/100x100/1D4C43/FAFAF0?text=TL'],
        ['name' => 'Web Designer', 'role' => 'Front-End Development & CSS', 'img' => 'https://placehold.co/100x100/71B48D/1D4C43?text=WD'],
        ['name' => 'Database Expert', 'role' => 'MySQL/DB Integration & Security', 'img' => 'https://placehold.co/100x100/FF9900/2C3E50?text=DB'],
];
?>

<main class="about-page">
    <div class="container">
        <h1 class="page-title">Our Mission: Saving the Planet, APU Style 🌍</h1>
        <p class="page-subtitle">Welcome to **EcoQuest**! We're here to turn environmental action into an epic game for the entire APU community.</p>

        <!-- NEW: TWO-COLUMN CONTAINER for Goal and How-To -->
        <div class="about-columns">

            <!-- Column 1: Goal & Purpose -->
            <section class="about-section column-item">
                <h2>The Big Goal 🎯</h2>
                <p>We saw too much single-use plastic around campus, so we built **EcoQuest**! Our primary purpose is to **reduce plastic waste** across the Asia Pacific University campus by incentivizing sustainable behavior through gamification. Confirm fun, confirm effective!</p>
                <p>Every small action, like using a reusable container or **saying "no" to a plastic straw**, is rewarded with **Points**. These points climb you up the Leaderboard and let you redeem cool rewards from the Marketplace.</p>
            </section>

            <!-- Column 2: How It Works -->
            <section class="about-section column-item">
                <h2>How You Join the Quest 🚀</h2>
                <ul class="how-it-works-list">
                    <li><span class="step-number">1.</span> **Register:** Sign up and become a verified EcoQuest Student.</li>
                    <li><span class="step-number">2.</span> **Complete Quests:** Pick challenges like "Bring Your Own Cup Day" or "The Carpool Crew."</li>
                    <li><span class="step-number">3.</span> **Submit Proof:** Take a picture or provide a log (our Moderators will review, so no cheating, okay?).</li>
                    <li><span class="step-number">4.</span> **Earn Rewards:** Collect Points and spend them on vouchers, merch, or experiences in the Rewards Marketplace!</li>
                </ul>
            </section>
        </div>
        <!-- END OF TWO-COLUMN CONTAINER -->

        <!-- Section 3: The Team (Remains full width) -->
        <section class="about-section team-section">
            <h2>Meet the Dev Team 🧑‍💻</h2>
            <p>This whole system was built with love (and maybe too much coffee) by a group of **APU IT (SE) students** for the Responsive Web Design & Development (RWDD) assignment.</p>

            <div class="team-grid">
                <?php foreach ($team_members as $member): ?>
                    <div class="team-card">
                        <img src="<?php echo $member['img']; ?>" alt="Team Member Avatar" class="member-avatar">
                        <h4 class="member-name"><?php echo htmlspecialchars($member['name']); ?></h4>
                        <p class="member-role"><?php echo htmlspecialchars($member['role']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 4: Contact (Remains full width) -->
        <section class="about-section contact-section">
            <h2>Contact & Resources</h2>
            <p>Got questions or found a bug? Please report it to our system administrator:</p>
            <p>Email: <a href="mailto:admin@ecoquest.apu.my">admin@ecoquest.apu.my</a></p>
            <p>Code Version: Alpha 1.0 (RWDD September 2025)</p>
        </section>

    </div>
</main>

<?php include("../includes/footer.php"); ?>
