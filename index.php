<?php
session_start();
$is_logged_in = false;
$dashboard_url = 'login.php';

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
    if (in_array(strtolower($_SESSION['role']), $admin_roles)) {
        $dashboard_url = 'dashboard.php';
    } else {
        $dashboard_url = 'nurse_dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareMeds | Medication Alert & Healthcare System</title>
   
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
   
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="css/landing.css?v=<?php echo time(); ?>">
</head>
<body>

    <header id="header">
        <div class="container">
            <div class="nav-wrapper">
                <a href="#home" class="logo">
                    <img src="image/caremeds_logo.png" alt="CareMeds Logo" class="logo-img">
                    <div class="logo-text-wrapper">
                        <span class="logo-title">CareMeds</span>
                        <span class="logo-subtitle">Medication Alert System</span>
                    </div>
                </a>
                <ul class="nav-menu">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <div>
                    <a href="login.php" class="btn-login"><i data-lucide="log-in" style="width:16px; height:16px;"></i> Login</a>
                </div>
            </div>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i data-lucide="plus-circle" style="width: 14px; height: 14px; stroke-width: 3;"></i>
                        <span>PUSAT JAGAAN KENANGAN HAJAH RAHMAH</span>
                    </div>
                    <h1>CareMeds<br><span>Medication Alert System</span></h1>
                    <p class="hero-desc">A comprehensive healthcare management system designed to simplify patient care, medication tracking, and hospital appointment management for Pusat Jagaan Kenangan Hajah Rahmah.</p>
                    <div class="hero-actions">
                        <a href="login.php" class="btn-primary">Login to System <i data-lucide="key-round" style="width: 16px; height: 16px;"></i></a>
                        <a href="#about" class="btn-secondary">Learn More <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i></a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-img-wrapper">
                        <img src="image/doc.jpeg" alt="Nurse caring for elderly resident">
                    </div>
                    <div class="hero-overlay-card">
                        <div class="hero-overlay-icon">
                            <i data-lucide="shield-check"></i>
                        </div>
                        <div class="hero-overlay-info">
                            <h4>CareMeds Active</h4>
                            <p>Real-time Alerts & Safety Monitoring</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-visual">
                    <img src="image/HR_building.png" alt="Pusat Jagaan Kenangan Hajah Rahmah Front Building">
                </div>
                <div class="about-content">
                    <span class="about-tag">ABOUT CAREMEDS</span>
                    <h3>Better Care Through<br>Smart Technology</h3>
                    <p>CareMeds is a centralized healthcare management platform developed to improve operational efficiency and patient care at Pusat Jagaan Kenangan Hajah Rahmah. The system enables healthcare staff to manage patient records, medication schedules, hospital appointments, and daily reports securely and efficiently.</p>
                    <div class="about-features">
                        <div class="about-item">
                            <div class="about-item-icon">
                                <i data-lucide="shield" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div class="about-item-text">
                                <h4>Secure & Reliable</h4>
                                <p>Protecting patient data with advanced database security standards.</p>
                            </div>
                        </div>
                        <div class="about-item">
                            <div class="about-item-icon">
                                <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div class="about-item-text">
                                <h4>Efficient Workflow</h4>
                                <p>Streamline care processes and eliminate error-prone paperwork.</p>
                            </div>
                        </div>
                        <div class="about-item">
                            <div class="about-item-icon">
                                <i data-lucide="heart" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div class="about-item-text">
                                <h4>Better Patient Care</h4>
                                <p>Providing timely alerts and accurate information to caregivers for better health support.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="services">
        <div class="container">
            <span class="section-tag">OUR SERVICES</span>
            <h2 class="section-title">Comprehensive Healthcare Management</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i data-lucide="users"></i>
                    </div>
                    <h3>Patient Management</h3>
                    <p>Manage resident profiles, medical records, emergency contacts, and personal information in a secure, centralized directory.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i data-lucide="pill"></i>
                    </div>
                    <h3>Medication Tracking</h3>
                    <p>Monitor prescription schedules, track medication adherence, and receive timely administration alerts for resident safety.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i data-lucide="calendar"></i>
                    </div>
                    <h3>Hospital Appointments</h3>
                    <p>Schedule specialist appointments, hospital visits, and clinical consultations with automatic tracking of accompanying staff.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i data-lucide="file-bar-chart-2"></i>
                    </div>
                    <h3>Reporting & Analytics</h3>
                    <p>Analyze medical compliance rates, room occupancies, and demographic distribution trends to support decision making.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <span class="section-tag">SYSTEM FEATURES</span>
            <h2 class="section-title">Powerful Features for Healthcare Excellence</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="lock"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Secure Login</h3>
                        <p>Role-based access controls for Administrators, Managers, Nurses, and Caregivers.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="clipboard-list"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Patient Records</h3>
                        <p>Detailed profile logs including allergies, contact information, and joining dates.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="bell"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Medication Alerts</h3>
                        <p>Automatic notifications for nurses to record and verify medication doses.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="calendar-days"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Appointment Scheduling</h3>
                        <p>Assign clinical coordinators and track medical consultation statuses seamlessly.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="user-cog"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Staff Management</h3>
                        <p>Admin control panel for staff access levels, credentials, and profile creation.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="file-text"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Report Generation</h3>
                        <p>Print-friendly reports for medication compliance, resident occupancies, and audits.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-choose">
        <div class="container">
            <div class="why-grid">
                <div class="why-content">
                    <span class="why-tag">WHY CHOOSE CAREMEDS?</span>
                    <h3>Enhancing Care, Improving Lives</h3>
                    <div class="why-checklist">
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Centralized patient information</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Improved operational efficiency</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Medication reminders and alerts</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Role-based access control</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Reduced paperwork and errors</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Secure and confidential</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>Faster access to patient data</span>
                        </div>
                        <div class="why-check-item">
                            <div class="why-check-icon"><i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 4;"></i></div>
                            <span>User-friendly interface</span>
                        </div>
                    </div>
                </div>
                <div class="why-visual">
                    <img src="image/doc3.jpeg" alt="Nurse guiding patient with a digital tablet">
                </div>
            </div>
        </div>
    </section>

    <section class="stats-banner">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h2>150+</h2>
                    <p>Patients Managed</p>
                </div>
                <div class="stat-item">
                    <h2>20+</h2>
                    <p>Healthcare Staff</p>
                </div>
                <div class="stat-item">
                    <h2>1,200+</h2>
                    <p>Medication Records</p>
                </div>
                <div class="stat-item">
                    <h2>98%</h2>
                    <p>Appointment Success Rate</p>
                </div>
            </div>
        </div>
    </section>

    <section class="workflow">
        <div class="container">
            <span class="section-tag">HOW CAREMEDS WORKS</span>
            <h2 class="section-title">A Simple Workflow for Better Care</h2>
            <div class="workflow-steps-wrapper">
                
                <div class="workflow-step">
                    <div class="workflow-icon-circle">
                        <i data-lucide="user-plus"></i>
                        <span class="workflow-step-num">1</span>
                    </div>
                    <h4>Register Patient</h4>
                    <p>Add new resident profile data to the database directory.</p>
                </div>
                
                <div class="workflow-arrow"><i data-lucide="chevron-right"></i></div>

                <div class="workflow-step">
                    <div class="workflow-icon-circle">
                        <i data-lucide="file-heart"></i>
                        <span class="workflow-step-num">2</span>
                    </div>
                    <h4>Create Health Record</h4>
                    <p>Record medical profiles, room names, and emergency contact details.</p>
                </div>

                <div class="workflow-arrow"><i data-lucide="chevron-right"></i></div>

                <div class="workflow-step">
                    <div class="workflow-icon-circle">
                        <i data-lucide="activity"></i>
                        <span class="workflow-step-num">3</span>
                    </div>
                    <h4>Assign Medication</h4>
                    <p>Input prescriptions and schedule alerts for medication tracking.</p>
                </div>

                <div class="workflow-arrow"><i data-lucide="chevron-right"></i></div>

                <div class="workflow-step">
                    <div class="workflow-icon-circle">
                        <i data-lucide="calendar-clock"></i>
                        <span class="workflow-step-num">4</span>
                    </div>
                    <h4>Schedule Appointment</h4>
                    <p>Set up hospital visits and consultations with specific caregivers.</p>
                </div>

                <div class="workflow-arrow"><i data-lucide="chevron-right"></i></div>

                <div class="workflow-step">
                    <div class="workflow-icon-circle">
                        <i data-lucide="printer"></i>
                        <span class="workflow-step-num">5</span>
                    </div>
                    <h4>Generate Report</h4>
                    <p>Review and print dynamic reports for tracking system compliance.</p>
                </div>

            </div>
        </div>
    </section>

    <section id="team" class="team">
        <div class="container">
            <span class="section-tag">MEET OUR HEALTHCARE TEAM</span>
            <h2 class="section-title">Professional Care Staff & Administration</h2>
            <div class="team-grid">
                
                <div class="team-card">
                    <img src="image/team1.jpeg" alt="Administrator profile photo" class="team-img">
                    <h4>Administrator</h4>
                    <p>System Administrator</p>
                </div>

                <div class="team-card">
                    <img src="image/team4.jpeg" alt="Nurse profile photo" class="team-img">
                    <h4>Nurse</h4>
                    <p>Patient Care & Monitoring</p>
                </div>

                <div class="team-card">
                    <img src="image/team2.jpeg" alt="Caregiver profile photo" class="team-img">
                    <h4>Caregiver</h4>
                    <p>Daily Care & Support</p>
                </div>

                <div class="team-card">
                    <img src="image/team5.jpeg" alt="Physiotherapist profile photo" class="team-img">
                    <h4>Physiotherapist</h4>
                    <p>Rehabilitation Specialist</p>
                </div>

                <div class="team-card">
                    <img src="image/team3.jpeg" alt="Management profile photo" class="team-img">
                    <h4>Management</h4>
                    <p>Operations Management</p>
                </div>

            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-info">
                        <h4>Pusat Jagaan Kenangan Hajah Rahmah</h4>
                        <p>Healthcare service provider committed to delivering professional, reliable, and compassionate care services.</p>
                    </div>
                    <div class="footer-info">
                        <h4>Address</h4>
                        <div class="footer-info-item">
                            <i data-lucide="map-pin" class="footer-info-icon"></i>
                            <p> LOT 1677, Jalan Sekolah Kebangsaan Lundang, Kampung Lundang, 15200 Kota Bharu, Kelantan</p>
                        </div>
                    </div>
                    <div class="footer-info">
                        <h4>Contact Info</h4>
                        <div class="footer-info-item">
                            <i data-lucide="phone" class="footer-info-icon"></i>
                            <p>+60 19-806 0769</p>
                        </div>
                        <div class="footer-info-item">
                            <i data-lucide="mail" class="footer-info-icon"></i>
                            <p>shaliscare77@gmail.com</p>
                        </div>
                    </div>
                    <div class="footer-info">
                        <h4>Operating Hours</h4>
                        <div class="footer-info-item">
                            <i data-lucide="clock" class="footer-info-icon"></i>
                            <p>Sunday - Saturday | 8:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-wrapper">
                    <div class="footer-logo-text">
                        <img src="image/caremeds_logo.png" alt="CareMeds Logo" style="height: 24px; width: auto; object-fit: contain;">
                        <span>CareMeds</span>
                    </div>
                    <p>&copy; 2026 CareMeds: Medication Alert System. All rights reserved.</p>
                    <div class="footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>