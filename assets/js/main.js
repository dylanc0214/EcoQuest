// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {

    // ============================================
    // --- 1. MOBILE MENU TOGGLE ---
    // ============================================
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-links');
    
    // Global toggle function
    window.toggleMobileMenu = function() {
        if (navMenu && navToggle) {
            navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-active');
        }
    };

    if (navToggle) {
        navToggle.addEventListener('click', window.toggleMobileMenu);
    }

    // ============================================
    // --- 2. FAQ POPUP LOGIC ---
    // ============================================
    const faqToggleButton = document.getElementById('faq-toggle-button');
    const faqCloseButton = document.getElementById('faq-close-btn');
    const faqPopup = document.getElementById('faq-popup');
    const faqListContainer = document.getElementById('faq-list');
    
    let allFaqQuestions = [];
    let isFaqLoaded = false;

    const appendMessage = (text, sender) => {
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${sender}`;
        messageElement.textContent = text;
        faqListContainer.appendChild(messageElement);
        faqListContainer.scrollTop = faqListContainer.scrollHeight;
    };

    const displayQuestions = () => {
        faqListContainer.innerHTML = '';
        allFaqQuestions.forEach(item => {
            const questionButton = document.createElement('button');
            questionButton.className = 'faq-question-btn';
            questionButton.textContent = item.question;
            questionButton.onclick = () => displayAnswer(item.question, item.answer);
            faqListContainer.appendChild(questionButton);
        });
    };

    const displayAnswer = (question, answer) => {
        faqListContainer.innerHTML = '';
        const backButton = document.createElement('button');
        backButton.className = 'faq-back-btn';
        backButton.innerHTML = '&laquo; Back to questions';
        backButton.onclick = displayQuestions;
        faqListContainer.appendChild(backButton);
        appendMessage(question, 'user');
        setTimeout(() => appendMessage(answer, 'bot'), 1000);
    };

    const loadFaq = async () => {
        if (isFaqLoaded) { displayQuestions(); return; }
        try {
            faqListContainer.innerHTML = '<p>Loading questions...</p>';
            const basePath = window.location.pathname.includes('/Group7_EcoQuest/') ? '/Group7_EcoQuest/' : '/';
            // Adjusted path to use base path to avoid errors on sub-pages
            const response = await fetch(`${basePath}assets/data/faq.json`); 
            if (!response.ok) throw new Error('faq.json not found');
            const data = await response.json();
            if (data.questions && data.questions.length > 0) {
                allFaqQuestions = data.questions;
                displayQuestions();
                isFaqLoaded = true;
            } else {
                faqListContainer.innerHTML = '<p>No FAQs found.</p>';
            }
        } catch (error) {
            console.error('Error loading FAQ:', error);
            faqListContainer.innerHTML = '<p>Error loading questions.</p>';
        }
    };

    const togglePopup = () => {
        faqPopup.classList.toggle('show');
        if (faqPopup.classList.contains('show')) loadFaq();
    };

    if (faqToggleButton && faqPopup && faqCloseButton) {
        faqToggleButton.addEventListener('click', togglePopup);
        faqCloseButton.addEventListener('click', togglePopup);
    }

    // ============================================
    // --- 3. NOTIFICATION SYSTEM (NEW) ---
    // ============================================
    const notifBell = document.getElementById('notif-bell');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');

    // Only run if elements exist (user is logged in)
    if (notifBell && notifDropdown) {
        // Initial Fetch
        fetchNotifications();
        // Poll every 30 seconds
        setInterval(fetchNotifications, 30000);

        window.toggleNotifDropdown = function() {
            notifDropdown.classList.toggle('show');
            if (notifDropdown.classList.contains('show')) fetchNotifications();
        };

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notifBell.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });
    }

    async function fetchNotifications() {
        const basePath = window.location.pathname.includes('/Group7_EcoQuest/') ? '/Group7_EcoQuest/' : '/';
        const url = `${basePath}pages/notification_handler.php?action=fetch`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.error) return; 

            // Update Badge
            if (data.unread_count > 0) {
                notifBadge.style.display = 'block';
                notifBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
            } else {
                notifBadge.style.display = 'none';
            }

            // Update List
            if(notifList) {
                notifList.innerHTML = '';
                if (data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">No new notifications.</div>';
                } else {
                    data.notifications.forEach(notif => {
                        const item = document.createElement('a');
                        item.className = 'notif-item unread'; 
                        item.href = notif.link || '#';
                        item.innerHTML = `
                            <div class="notif-title">${notif.title}</div>
                            <div class="notif-message">${notif.message}</div>
                            <span class="notif-time">${notif.time}</span>
                        `;
                        notifList.appendChild(item);
                    });
                }
            }
        } catch (error) {
            console.error('Notification Error:', error);
        }
    }
});

/*================================================
   LOGIN & REGISTER
================================================*/

// View Password Toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    // Note: 'event' is deprecated in some contexts but works here because of inline onclick
    const toggle = event.target; 
    if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = '🙉';
    } else {
        input.type = 'password';
        toggle.textContent = '🙈';
    }
}

// Slider Logic for Login/Register Page
// We define these variables inside functions or check if they exist 
// to prevents errors on pages where 'login' div doesn't exist.

var x = document.getElementById('login');
var y = document.getElementById('register');
var z = document.getElementById('btn');       
function login(){
    x.style.left = "27px";
    y.style.right = "-550px";
    z.style.left = "0px"; // 0%
}
function register(){
    x.style.left = "-550px";
    y.style.right = "25px";
    z.style.left = "50%"; // Moves exactly halfway
}