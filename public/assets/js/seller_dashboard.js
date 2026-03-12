document.addEventListener("DOMContentLoaded", function () {
  // Update current time
  function updateCurrentTime() {
    const now = new Date();
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    };
    const currentTimeElement = document.getElementById("currentTime");
    if (currentTimeElement) {
      currentTimeElement.textContent = now.toLocaleDateString("en-US", options);
    }
  }

  updateCurrentTime();
  setInterval(updateCurrentTime, 60000);

  // Card hover effects
  const cards = document.querySelectorAll(".dashboard-card");
  cards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-2px)";
      this.style.boxShadow = "0 8px 25px rgba(0,0,0,0.15)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
      this.style.boxShadow = "0 4px 15px rgba(0,0,0,0.1)";
    });
  });

  // Optional: Add tooltips for status badges
  const statusBadges = document.querySelectorAll('.status-badge');
  statusBadges.forEach(badge => {
    badge.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.05)';
    });
    badge.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });

  // Optional: Add confirmation for quick actions that might be destructive
  const deleteButtons = document.querySelectorAll('.delete-btn, .btn-danger');
  deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      if (!confirm('Are you sure you want to proceed?')) {
        e.preventDefault();
      }
    });
  });
});