document.addEventListener("DOMContentLoaded", function () {
  const tabBtns = document.querySelectorAll(".tab-btn");
  const tabPanes = document.querySelectorAll(".tab-pane");
  const noResultsDiv = document.getElementById("noResults");
  const searchInput = document.getElementById("reviewSearch");
  const statusFilter = document.getElementById("statusFilter");
  const ratingFilter = document.getElementById("ratingFilter");
  const clearFiltersBtn = document.getElementById("clearFiltersBtn");
  const exportBtn = document.getElementById("exportReviews");

  // Store original reviews list HTML for each tab
  const originalReviewsLists = {};
  tabPanes.forEach((pane) => {
    const list = pane.querySelector(".reviews-list");
    if (list) {
      originalReviewsLists[pane.id] = list.cloneNode(true);
    }
  });

  // Tab click handlers
  tabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      tabBtns.forEach((b) => b.classList.remove("active"));
      tabPanes.forEach((p) => p.classList.remove("active"));

      this.classList.add("active");
      const tabId = this.dataset.tab;
      const activePane = document.getElementById(tabId + "Tab");
      activePane.classList.add("active");

      // Restore original reviews list for this tab if it was replaced
      const existingList = activePane.querySelector(".reviews-list");
      const emptyState = activePane.querySelector(".empty-state");

      if (!existingList && !emptyState && originalReviewsLists[activePane.id]) {
        activePane.appendChild(
          originalReviewsLists[activePane.id].cloneNode(true),
        );
      }

      // Re-apply search/filter when switching tabs
      if (window.filterReviews) {
        setTimeout(window.filterReviews, 50);
      }
    });
  });

  // Filter function
  window.filterReviews = function () {
    const activeTab =
      document.querySelector(".tab-btn.active")?.dataset.tab || "all";
    const activeTabPane = document.getElementById(activeTab + "Tab");

    // Check if there's an empty state (no reviews at all for this tab)
    const permanentEmptyState = activeTabPane.querySelector(".empty-state");
    if (permanentEmptyState && permanentEmptyState.id !== "noResults") {
      return; // This tab has no reviews at all, don't filter
    }

    // Get or create reviews list
    let activeList = activeTabPane.querySelector(".reviews-list");

    // If no reviews list exists and we're not showing permanent empty state, create one
    if (!activeList && !permanentEmptyState) {
      activeList = document.createElement("div");
      activeList.className = "reviews-list";
      activeTabPane.appendChild(activeList);
    }

    if (!activeList) return;

    const searchTerm = searchInput?.value.toLowerCase() || "";
    const statusValue = statusFilter?.value || "";
    const ratingValue = ratingFilter?.value || "";

    const reviews = activeList.querySelectorAll(".review-card");
    let visibleCount = 0;

    reviews.forEach((review) => {
      const searchText = review.dataset.search || "";
      const reviewStatus = review.dataset.status || "";
      const reviewRating = review.dataset.rating || "";

      const matchesStatus =
        statusValue === "" || reviewStatus === statusValue.toLowerCase();
      const matchesRating = ratingValue === "" || reviewRating === ratingValue;
      const matchesSearch = !searchTerm || searchText.includes(searchTerm);

      const shouldShow = matchesSearch && matchesStatus && matchesRating;

      review.style.display = shouldShow ? "block" : "none";
      if (shouldShow) visibleCount++;
    });

    // Handle no results message
    const existingNoResults = activeTabPane.querySelector("#noResults");

    if (visibleCount === 0) {
      // Hide the reviews list
      activeList.style.display = "none";

      // Show or create no results message
      if (existingNoResults) {
        existingNoResults.style.display = "block";
      } else {
        const newNoResults = noResultsDiv.cloneNode(true);
        newNoResults.style.display = "block";
        newNoResults.id = "noResults"; // Ensure ID is preserved
        activeTabPane.appendChild(newNoResults);
      }
    } else {
      // Show the reviews list
      activeList.style.display = "flex";

      // Hide any no results message
      if (existingNoResults) {
        existingNoResults.style.display = "none";
      }
    }
  };

  // Clear Filters function
  window.clearFilters = function () {
    if (searchInput) searchInput.value = "";
    if (statusFilter) statusFilter.value = "";
    if (ratingFilter) ratingFilter.value = "";

    // Force a complete refresh of the view
    const activeTab =
      document.querySelector(".tab-btn.active")?.dataset.tab || "all";
    const activeTabPane = document.getElementById(activeTab + "Tab");

    // Restore original reviews list
    if (originalReviewsLists[activeTabPane.id]) {
      const oldList = activeTabPane.querySelector(".reviews-list");
      if (oldList) oldList.remove();

      const newList = originalReviewsLists[activeTabPane.id].cloneNode(true);
      activeTabPane.appendChild(newList);
    }

    // Hide any no results message
    const noResults = activeTabPane.querySelector("#noResults");
    if (noResults) {
      noResults.style.display = "none";
    }

    // Re-apply filters (which will now show all reviews)
    if (window.filterReviews) {
      setTimeout(window.filterReviews, 50);
    }
  };

  // Add event listeners
  if (searchInput) searchInput.addEventListener("keyup", window.filterReviews);
  if (statusFilter)
    statusFilter.addEventListener("change", window.filterReviews);
  if (ratingFilter)
    ratingFilter.addEventListener("change", window.filterReviews);
  if (clearFiltersBtn)
    clearFiltersBtn.addEventListener("click", window.clearFilters);

  // Export functionality
  if (exportBtn) {
    exportBtn.addEventListener("click", function () {
      const activeTab =
        document.querySelector(".tab-btn.active")?.dataset.tab || "all";
      const activeTabPane = document.getElementById(activeTab + "Tab");
      const activeList = activeTabPane?.querySelector(".reviews-list");

      if (!activeList) return;

      const visibleReviews = Array.from(
        activeList.querySelectorAll('.review-card:not([style*="none"])'),
      );

      if (visibleReviews.length === 0) {
        alert("No reviews to export");
        return;
      }

      // Prepare CSV data
      let csvContent =
        "Reviewer Name,Reviewer Email,Rating,Comments,Date,Status,Reference Type,Reference Name\n";

      visibleReviews.forEach((review) => {
        const reviewerName =
          review.querySelector("h4")?.textContent?.replace(/,/g, ";") || "N/A";
        const reviewerEmail =
          review
            .querySelector(".reviewer-email")
            ?.textContent?.replace(/,/g, ";") || "N/A";
        const rating = review.dataset.rating || "N/A";
        const comments =
          review
            .querySelector(".review-content p")
            ?.textContent?.replace(/,/g, ";")
            .replace(/\n/g, " ") || "N/A";
        const date =
          review
            .querySelector(".review-date")
            ?.textContent?.replace(/,/g, ";")
            .trim() || "N/A";
        const status = review.dataset.status?.toUpperCase() || "N/A";

        // Get reference info
        let refType = "N/A";
        let refName = "N/A";
        const typeBadge = review.querySelector(".type-badge");
        if (typeBadge) {
          if (typeBadge.classList.contains("kitchen")) refType = "KITCHEN";
          else if (typeBadge.classList.contains("item")) refType = "ITEM";
          else if (typeBadge.classList.contains("platform"))
            refType = "TIFFINCRAFT";

          // Extract reference name from badge text
          const badgeText = typeBadge.textContent || "";
          if (refType === "ITEM") {
            refName = badgeText.replace("Menu Item", "").trim();
          } else if (refType === "KITCHEN") {
            refName = badgeText.replace("Kitchen Review", "").trim();
          } else if (refType === "TIFFINCRAFT") {
            refName = badgeText.replace("Platform Review", "").trim();
          }
        }

        csvContent += `"${reviewerName}","${reviewerEmail}","${rating}","${comments}","${date}","${status}","${refType}","${refName}"\n`;
      });

      // Create download link
      const blob = new Blob(["\uFEFF" + csvContent], {
        type: "text/csv;charset=utf-8;",
      }); // Add BOM for UTF-8
      const link = document.createElement("a");
      const url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute(
        "download",
        `reviews_export_${new Date().toISOString().split("T")[0]}.csv`,
      );
      link.style.visibility = "hidden";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    });
  }

  // Show/hide reason field based on status selection
  const editStatusSelect = document.getElementById("editReviewStatus");
  if (editStatusSelect) {
    editStatusSelect.addEventListener("change", function () {
      const reasonField = document.getElementById("reasonField");
      const reasonInput = document.getElementById("editReviewReason");

      if (this.value === "HIDDEN" || this.value === "REPORTED") {
        reasonField.style.display = "block";
        reasonInput.setAttribute("required", "required");
      } else {
        reasonField.style.display = "none";
        reasonInput.removeAttribute("required");
      }
    });
  }
});

// Change limit (keep this outside DOMContentLoaded since it's called from HTML)
function changeLimit(limit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", limit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Modal Functions
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
  }
}

function openViewReviewModal(review) {
  try {
    document.getElementById("viewReviewerName").textContent =
      review.REVIEWER_NAME || "Anonymous";
    document.getElementById("viewReviewerEmail").textContent =
      review.REVIEWER_EMAIL || "N/A";
    document.getElementById("viewReviewType").innerHTML = getReviewTypeBadge(
      review.REFERENCE_TYPE,
      review.REFERENCE_NAME,
    );
    document.getElementById("viewReferenceName").textContent =
      review.REFERENCE_NAME || "N/A";
    document.getElementById("viewRating").innerHTML =
      generateStarRating(review.RATING) + " (" + review.RATING + ")";
    document.getElementById("viewReviewDate").textContent = formatDate(
      review.REVIEW_DATE,
    );
    document.getElementById("viewReviewStatus").innerHTML = getStatusBadge(
      review.STATUS,
    );

    const commentsEl = document.getElementById("viewReviewComments");
    if (commentsEl) {
      commentsEl.innerHTML = review.COMMENTS
        ? nl2br(escapeHtml(review.COMMENTS))
        : "No comments";
    }

    const hiddenSection = document.getElementById("hiddenInfoSection");
    if (hiddenSection) {
      if (review.STATUS === "HIDDEN" && review.HIDDEN_BY_NAME) {
        document.getElementById("viewHiddenBy").textContent =
          review.HIDDEN_BY_NAME;
        document.getElementById("viewHiddenAt").textContent = review.HIDDEN_AT
          ? formatDate(review.HIDDEN_AT)
          : "N/A";
        document.getElementById("viewHiddenReason").textContent =
          review.HIDDEN_REASON || "No reason provided";
        hiddenSection.style.display = "block";
      } else {
        hiddenSection.style.display = "none";
      }
    }

    const modal = document.getElementById("viewReviewModal");
    if (modal) {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  } catch (e) {
    console.error("Error opening view modal:", e);
  }
}

function openEditReviewModal(reviewId, currentStatus) {
  try {
    document.getElementById("editReviewId").value = reviewId;
    const statusSelect = document.getElementById("editReviewStatus");
    if (statusSelect) {
      statusSelect.value = currentStatus;
      const event = new Event("change");
      statusSelect.dispatchEvent(event);
    }

    const modal = document.getElementById("editReviewModal");
    if (modal) {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  } catch (e) {
    console.error("Error opening edit modal:", e);
  }
}

function openDeleteReviewModal(reviewId, reviewerName) {
  try {
    document.getElementById("deleteReviewId").value = reviewId;
    document.getElementById("deleteReviewerName").textContent = reviewerName;

    const modal = document.getElementById("deleteReviewModal");
    if (modal) {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  } catch (e) {
    console.error("Error opening delete modal:", e);
  }
}

// Helper Functions
function getReviewTypeBadge(type, name) {
  switch (type) {
    case "KITCHEN":
      return '<span class="type-badge kitchen"><i class="fas fa-store"></i> Kitchen Review</span>';
    case "ITEM":
      return (
        '<span class="type-badge item"><i class="fas fa-utensils"></i> ' +
        escapeHtml(name || "") +
        "</span>"
      );
    case "TIFFINCRAFT":
      return '<span class="type-badge platform"><i class="fas fa-globe"></i> Platform Review</span>';
    default:
      return '<span class="type-badge">' + escapeHtml(type || "") + "</span>";
  }
}

function getStatusBadge(status) {
  const statusClasses = {
    PUBLIC: "status-badge public",
    HIDDEN: "status-badge hidden",
    REPORTED: "status-badge reported",
  };
  const icons = {
    PUBLIC: "check-circle",
    HIDDEN: "eye-slash",
    REPORTED: "flag",
  };
  const cls = statusClasses[status] || "status-badge";
  const icon = icons[status] || "info-circle";
  return (
    '<span class="' +
    cls +
    '"><i class="fas fa-' +
    icon +
    '"></i> ' +
    (status || "UNKNOWN") +
    "</span>"
  );
}

function generateStarRating(rating) {
  let stars = "";
  rating = parseInt(rating) || 0;
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      stars += '<i class="fas fa-star active"></i>';
    } else {
      stars += '<i class="fas fa-star"></i>';
    }
  }
  return stars;
}

function formatDate(dateString) {
  if (!dateString) return "";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch (e) {
    return dateString;
  }
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function nl2br(text) {
  if (!text) return "";
  return text.replace(/\n/g, "<br>");
}

// Close modal on outside click
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal-overlay")) {
    e.target.classList.remove("active");
    document.body.style.overflow = "auto";
  }
});

// Escape key to close modal
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal-overlay.active").forEach((modal) => {
      modal.classList.remove("active");
    });
    document.body.style.overflow = "auto";
  }
});
