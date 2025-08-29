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
    fetch('api/dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            // Populate stats
            document.getElementById('total-students').textContent = data.stats.total_students;
            document.getElementById('monthly-fee').textContent = `$${data.stats.pending_fees}`;
            document.getElementById('attendance-rate').textContent = `${data.stats.today_attendance} %`;
            document.getElementById('attendance-progress').style.width = `${data.stats.today_attendance} %`;
            document.getElementById('attendance-progress').setAttribute('aria-valuenow', data.stats.today_attendance);
            document.getElementById('pending-requests').textContent = data.stats.pending_requests;

            // Load recent activities
            const timelineContainer = document.getElementById('activity-timeline');
            timelineContainer.innerHTML = '';
            data.recent_activity.forEach((activity, index) => {
                timelineContainer.innerHTML += `
                    <div class="activity-item d-flex">
                        <div class="activity-icon me-3">
                            <i class="fas fa-user-plus text-success fa-lg"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">${activity.description}</div>
                            <div class="activity-time text-muted small">${activity.time_ago}</div>
                        </div>
                    </div>
                    ${index < data.recent_activity.length - 1 ? '<hr>' : ''}
                `;
            });

            // Load announcements
            const announcementsContainer = document.getElementById('announcements-container');
            announcementsContainer.innerHTML = '';
            data.announcements.forEach(announcement => {
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

            // Initialize calendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: data.events
            });
            calendar.render();
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
});
