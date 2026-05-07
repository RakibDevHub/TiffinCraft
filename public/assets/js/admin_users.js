// Change items per page
function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Open suspend user modal
function openSuspendModal(userId, userName) {
  document.getElementById("suspendUserId").value = userId;
  document.getElementById("suspendUserName").value = userName;
  document.getElementById("suspendModal").classList.add("active");
}

// Open lift suspension modal with suspension details
function openLiftSuspensionModal(
  userId,
  userName,
  suspensionReason,
  suspendedUntil,
) {
  document.getElementById("liftSuspensionUserId").value = userId;
  document.getElementById("liftSuspensionUserName").textContent = userName;

  const detailsContainer = document.getElementById("suspensionDetails");
  if (suspensionReason) {
    document.getElementById("suspensionReason").textContent = suspensionReason;
    document.getElementById("suspendedUntil").textContent =
      suspendedUntil || "Permanent";
    detailsContainer.style.display = "block";
  } else {
    detailsContainer.style.display = "none";
  }

  document.getElementById("liftSuspensionModal").classList.add("active");
}

// Open delete user modal
function openDeleteModal(userId, userName) {
  document.getElementById("deleteUserId").value = userId;
  document.getElementById("deleteUserName").textContent = userName;
  document.getElementById("deleteModal").classList.add("active");
}

// Open add admin modal
function openAddAdminModal() {
  document.getElementById("addAdminModal").classList.add("active");
}

// View user details modal
function viewUserDetails(userId) {
  const allDetails = document.querySelectorAll(".user-details-content");
  allDetails.forEach((detail) => (detail.style.display = "none"));

  const userDetail = document.getElementById("user-details-" + userId);
  if (userDetail) {
    userDetail.style.display = "block";
  }

  document.getElementById("userDetailsModal").classList.add("active");

  const scrollContainer = document.querySelector(".modal-body");
  if (scrollContainer) scrollContainer.scrollTop = 0;
}

// Close any modal
function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

// Filter users by search, role and status
function filterUsers() {
  const searchText = (
    document.getElementById("userSearch")?.value || ""
  ).toLowerCase();
  const roleValue = document.getElementById("roleFilter")?.value || "";
  const statusValue = document.getElementById("statusFilter")?.value || "";
  const rows = document.querySelectorAll(".users-table tbody tr");

  rows.forEach((row) => {
    const userName =
      row.querySelector(".user-details h4")?.textContent.toLowerCase() || "";
    const userEmail =
      row.querySelector(".user-details p")?.textContent.toLowerCase() || "";
    const role = row.dataset.role;
    const status = row.dataset.status;

    const searchMatch =
      !searchText ||
      userName.includes(searchText) ||
      userEmail.includes(searchText);
    const roleMatch = !roleValue || role === roleValue;
    const statusMatch = !statusValue || status === statusValue;

    row.style.display = searchMatch && roleMatch && statusMatch ? "" : "none";
  });
}

// Clear all filters
function clearFilters() {
  const search = document.getElementById("userSearch");
  const role = document.getElementById("roleFilter");
  const status = document.getElementById("statusFilter");

  if (search) search.value = "";
  if (role) role.value = "";
  if (status) status.value = "";

  filterUsers();
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Add admin button
  const addAdminBtn = document.getElementById("addAdminBtn");
  if (addAdminBtn) addAdminBtn.addEventListener("click", openAddAdminModal);

  // Clear filters button
  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  // Search input
  const userSearch = document.getElementById("userSearch");
  if (userSearch) userSearch.addEventListener("input", filterUsers);

  // Role filter
  const roleFilter = document.getElementById("roleFilter");
  if (roleFilter) roleFilter.addEventListener("change", filterUsers);

  // Status filter
  const statusFilter = document.getElementById("statusFilter");
  if (statusFilter) statusFilter.addEventListener("change", filterUsers);

  // Close modal when clicking overlay
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("active");
    });
  });

  // Initial filter
  filterUsers();
});
