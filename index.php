<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include common functions
require_once 'includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get user information
$stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set current page for sidebar highlighting
$currentPage = 'dashboard';

// Include header
include_once 'includes/header.php';

// Include sidebar
include_once 'includes/sidebar.php';
?>  

<!-- Main Content -->
    <div class="container-fluid px-4">
        <!-- Welcome Banner -->
        <div class="row mb-4 mt-3">
            <div class="col-12">
                <div class="welcome-banner card shadow-sm">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center">
                            <div>
                                <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                                <p class="text-muted">School Management System Dashboard</p>
                            </div>
                            <div class="ms-auto">
                                <div class="current-date-time">
                                    <div id="current-date" class="h5"></div>
                                    <div id="current-time" class="h6"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Students</div>
                                <div class="h4 mb-0 fw-bold text-gray-800" id="total-students">Loading...</div>
                            </div>
                            <div class="icon-circle bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-users fa-lg text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-success shadow-sm h-100">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Fee Collection (Monthly)</div>
                                <div class="h4 mb-0 fw-bold text-gray-800" id="monthly-fee">Loading...</div>
                            </div>
                            <div class="icon-circle bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-dollar-sign fa-lg text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-info shadow-sm h-100">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Attendance Rate</div>
                                <div class="d-flex align-items-center">
                                    <div class="h4 mb-0 fw-bold text-gray-800 me-2" id="attendance-rate">Loading...</div>
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="attendance-progress"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-warning shadow-sm h-100">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending Requests</div>
                                <div class="h4 mb-0 fw-bold text-gray-800" id="pending-requests">Loading...</div>
                            </div>
                            <div class="icon-circle bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-comments fa-lg text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Announcements -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm rounded-3 border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Announcements</h6>
                        <?php if ($user['role'] === 'admin'): ?>
                        <a href="#" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                            <i class="fas fa-plus me-1"></i> Add New
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0" id="announcements-container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Photos Carousel -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm rounded-3 border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Recent Events</h6>
                        <a href="gallery/index.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-images me-1"></i> View Gallery
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner rounded">
                                <div class="carousel-item active">
                                    <img src="assets/img/events/event1.jpg" class="d-block w-100" alt="School Event" style="height: 300px; object-fit: cover;">
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded-3">
                                        <h5>Annual Sports Day</h5>
                                        <p>Students participating in various sports activities.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/img/events/event2.jpg" class="d-block w-100" alt="School Event" style="height: 300px; object-fit: cover;">
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded-3">
                                        <h5>Science Exhibition</h5>
                                        <p>Showcasing innovative projects by our talented students.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/img/events/event3.jpg" class="d-block w-100" alt="School Event" style="height: 300px; object-fit: cover;">
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded-3">
                                        <h5>Cultural Festival</h5>
                                        <p>Celebrating diversity through cultural performances.</p>
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="row mb-4">
            <?php if ($user['role'] === 'admin'): ?>
            <div class="col-12 mb-3">
                <h5 class="text-dark fw-bold"><i class="fas fa-bolt me-2 text-primary"></i>Quick Access</h5>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-start border-primary border-4 shadow-sm h-100 py-3 px-4">
                    <div class="card-body p-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Communication Center</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Manage Messages</div>
                            </div>
                            <div class="icon-circle bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-envelope fa-lg text-primary"></i>
                            </div>
                        </div>
                        <a href="communication/index.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow-sm h-100 py-3 px-4">
                    <div class="card-body p-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">System Settings</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Configure System</div>
                            </div>
                            <div class="icon-circle bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-cog fa-lg text-success"></i>
                            </div>
                        </div>
                        <a href="settings/index.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-start border-info border-4 shadow-sm h-100 py-3 px-4">
                    <div class="card-body p-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">User Management</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Manage Accounts</div>
                            </div>
                            <div class="icon-circle bg-info bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-user-cog fa-lg text-info"></i>
                            </div>
                        </div>
                        <a href="users/index.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-start border-warning border-4 shadow-sm h-100 py-3 px-4">
                    <div class="card-body p-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Backup & Restore</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Database Tools</div>
                            </div>
                            <div class="icon-circle bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-database fa-lg text-warning"></i>
                            </div>
                        </div>
                        <a href="backup/index.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activities and Calendar Row -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm rounded-3 border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Recent Activities</h6>
                        <a href="activity-log.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-list me-1"></i> View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-timeline" id="activity-timeline">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm rounded-3 border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Calendar</h6>
                        <?php if ($user['role'] === 'admin'): ?>
                        <a href="#" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus me-1"></i> Add Event
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="addAnnouncementModalLabel">
                    <i class="fas fa-bullhorn me-2 text-primary"></i>Add New Announcement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="announcementForm">
                    <div class="mb-4">
                        <label for="announcementTitle" class="form-label fw-semibold">Title</label>
                        <input type="text" class="form-control form-control-lg rounded-3" id="announcementTitle" placeholder="Enter announcement title" required>
                    </div>
                    <div class="mb-4">
                        <label for="announcementContent" class="form-label fw-semibold">Content</label>
                        <textarea class="form-control rounded-3" id="announcementContent" rows="4" placeholder="Enter announcement details" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="announcementPriority" class="form-label fw-semibold">Priority</label>
                        <select class="form-select rounded-3" id="announcementPriority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="saveAnnouncement">
                    <i class="fas fa-save me-2"></i>Save Announcement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="addEventModalLabel">
                    <i class="fas fa-calendar-plus me-2 text-primary"></i>Add New Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="eventForm">
                    <div class="mb-4">
                        <label for="eventTitle" class="form-label fw-semibold">Event Title</label>
                        <input type="text" class="form-control form-control-lg rounded-3" id="eventTitle" placeholder="Enter event title" required>
                    </div>
                    <div class="mb-4">
                        <label for="eventDate" class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control rounded-3" id="eventDate" required>
                    </div>
                    <div class="mb-4">
                        <label for="eventDescription" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control rounded-3" id="eventDescription" rows="3" placeholder="Enter event details"></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="eventColor" class="form-label fw-semibold">Color</label>
                        <div class="d-flex gap-2 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eventColorRadio" id="colorBlue" value="#4e73df" checked>
                                <label class="form-check-label" for="colorBlue">
                                    <span class="color-swatch" style="background-color: #4e73df;"></span> Blue
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eventColorRadio" id="colorGreen" value="#1cc88a">
                                <label class="form-check-label" for="colorGreen">
                                    <span class="color-swatch" style="background-color: #1cc88a;"></span> Green
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eventColorRadio" id="colorYellow" value="#f6c23e">
                                <label class="form-check-label" for="colorYellow">
                                    <span class="color-swatch" style="background-color: #f6c23e;"></span> Yellow
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eventColorRadio" id="colorRed" value="#e74a3b">
                                <label class="form-check-label" for="colorRed">
                                    <span class="color-swatch" style="background-color: #e74a3b;"></span> Red
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eventColorRadio" id="colorCyan" value="#36b9cc">
                                <label class="form-check-label" for="colorCyan">
                                    <span class="color-swatch" style="background-color: #36b9cc;"></span> Cyan
                                </label>
                            </div>
                        </div>
                        <select class="form-select d-none" id="eventColor">
                            <option value="#4e73df" selected>Blue</option>
                            <option value="#1cc88a">Green</option>
                            <option value="#f6c23e">Yellow</option>
                            <option value="#e74a3b">Red</option>
                            <option value="#36b9cc">Cyan</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="saveEvent">
                    <i class="fas fa-save me-2"></i>Save Event
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.color-swatch {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 5px;
    vertical-align: middle;
}
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>

<script>
    // Update date and time
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
        document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US');
    }
    
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Fetch dashboard data
    document.addEventListener('DOMContentLoaded', function() {
        // Simulate loading data (in a real app, this would be an AJAX call to the server)
        setTimeout(() => {
            document.getElementById('total-students').textContent = '1,250';
            document.getElementById('monthly-fee').textContent = '$125,000';
            document.getElementById('attendance-rate').textContent = '92%';
            document.getElementById('attendance-progress').style.width = '92%';
            document.getElementById('attendance-progress').setAttribute('aria-valuenow', '92');
            document.getElementById('pending-requests').textContent = '18';
            
            // Load announcements
            const announcements = [
                { title: 'School Closed for Holidays', content: 'The school will remain closed from December 25 to January 5 for winter holidays.', priority: 'high', date: '2023-12-15' },
                { title: 'Parent-Teacher Meeting', content: 'Parent-Teacher meeting is scheduled for next Friday. All parents are requested to attend.', priority: 'medium', date: '2023-12-10' },
                { title: 'Annual Sports Day', content: 'Annual Sports Day will be held on December 20. All students are encouraged to participate.', priority: 'medium', date: '2023-12-05' }
            ];
            
            const announcementsContainer = document.getElementById('announcements-container');
            announcementsContainer.innerHTML = '';
            
            announcements.forEach(announcement => {
                const priorityClass = announcement.priority === 'high' ? 'text-danger' : 
                                      announcement.priority === 'medium' ? 'text-warning' : 'text-info';
                
                announcementsContainer.innerHTML += `
                    <div class="announcement-item mb-3">
                        <div class="d-flex justify-content-between">
                            <h5 class="${priorityClass}">${announcement.title}</h5>
                            <small class="text-muted">${announcement.date}</small>
                        </div>
                        <p>${announcement.content}</p>
                        <hr>
                    </div>
                `;
            });
            
            // Load activities
            const activities = [
                { type: 'registration', text: 'New student John Doe registered', time: '5 minutes ago', icon: 'fa-user-plus text-success' },
                { type: 'payment', text: 'Fee payment received from Sarah Smith', time: '2 hours ago', icon: 'fa-dollar-sign text-primary' },
                { type: 'attendance', text: 'Attendance marked for Class 10', time: '3 hours ago', icon: 'fa-clipboard-check text-info' },
                { type: 'exam', text: 'Mid-term exam results published', time: '1 day ago', icon: 'fa-file-alt text-warning' }
            ];
            
            const timelineContainer = document.getElementById('activity-timeline');
            timelineContainer.innerHTML = '';
            
            activities.forEach((activity, index) => {
                timelineContainer.innerHTML += `
                    <div class="activity-item d-flex">
                        <div class="activity-icon me-3">
                            <i class="fas ${activity.icon} fa-lg"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">${activity.text}</div>
                            <div class="activity-time text-muted small">${activity.time}</div>
                        </div>
                    </div>
                    ${index < activities.length - 1 ? '<hr>' : ''}
                `;
            });
            
            // Initialize calendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    {
                        title: 'Parent-Teacher Meeting',
                        start: '2023-12-15',
                        color: '#4e73df'
                    },
                    {
                        title: 'Annual Sports Day',
                        start: '2023-12-20',
                        color: '#1cc88a'
                    },
                    {
                        title: 'Winter Break Begins',
                        start: '2023-12-25',
                        color: '#f6c23e'
                    }
                ]
            });
            calendar.render();
            
            // Handle announcement form submission
            document.getElementById('saveAnnouncement').addEventListener('click', function() {
                const title = document.getElementById('announcementTitle').value;
                const content = document.getElementById('announcementContent').value;
                const priority = document.getElementById('announcementPriority').value;
                
                if (title && content) {
                    // In a real app, this would be an AJAX call to save to the server
                    const today = new Date().toISOString().split('T')[0];
                    const priorityClass = priority === 'high' ? 'text-danger' : 
                                        priority === 'medium' ? 'text-warning' : 'text-info';
                    
                    announcementsContainer.innerHTML = `
                        <div class="announcement-item mb-3">
                            <div class="d-flex justify-content-between">
                                <h5 class="${priorityClass}">${title}</h5>
                                <small class="text-muted">${today}</small>
                            </div>
                            <p>${content}</p>
                            <hr>
                        </div>
                    ` + announcementsContainer.innerHTML;
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addAnnouncementModal'));
                    modal.hide();
                    document.getElementById('announcementForm').reset();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: 'Announcement has been added successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                }
            });
            
            // Handle event form submission
            document.getElementById('saveEvent').addEventListener('click', function() {
                const title = document.getElementById('eventTitle').value;
                const date = document.getElementById('eventDate').value;
                const description = document.getElementById('eventDescription').value;
                const color = document.getElementById('eventColor').value;
                
                if (title && date) {
                    // In a real app, this would be an AJAX call to save to the server
                    calendar.addEvent({
                        title: title,
                        start: date,
                        color: color,
                        description: description
                    });
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                    modal.hide();
                    document.getElementById('eventForm').reset();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: 'Event has been added to the calendar.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }, 1000);
    });
</script>