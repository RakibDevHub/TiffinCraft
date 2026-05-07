// Change items per page
function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Open add area modal
function openAddAreaModal() {
  document.getElementById("addAreaModal").classList.add("active");
}

// Open edit area modal and populate form
function openEditAreaModal(id, name, city, status) {
  document.getElementById("editAreaId").value = id;
  document.getElementById("editAreaName").value = name;
  document.getElementById("editAreaCity").value = city;
  document.getElementById("editAreaStatus").checked = status === "active";
  document.getElementById("editAreaModal").classList.add("active");
}

// Open delete area modal
function openDeleteAreaModal(id, name) {
  document.getElementById("deleteAreaId").value = id;
  document.getElementById("deleteAreaName").textContent = name;
  document.getElementById("deleteAreaModal").classList.add("active");
}

// Close any modal
function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

// Filter areas by search, status and city
function filterAreas() {
  const searchText = (
    document.getElementById("areaSearch")?.value || ""
  ).toLowerCase();
  const statusValue = document.getElementById("statusFilter")?.value || "";
  const cityValue = document.getElementById("cityFilter")?.value || "";
  const rows = document.querySelectorAll(".users-table tbody tr");

  rows.forEach((row) => {
    const areaName =
      row.querySelector(".user-details h4")?.textContent.toLowerCase() || "";
    const status = row.dataset.status;
    const city = row.dataset.city;

    const searchMatch = !searchText || areaName.includes(searchText);
    const statusMatch = !statusValue || status === statusValue;
    const cityMatch = !cityValue || city === cityValue;

    row.style.display = searchMatch && statusMatch && cityMatch ? "" : "none";
  });
}

// Clear all filters
function clearFilters() {
  document.getElementById("areaSearch").value = "";
  document.getElementById("statusFilter").value = "";
  document.getElementById("cityFilter").value = "";
  filterAreas();
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Add area button
  const addAreaBtn = document.getElementById("addAreaBtn");
  if (addAreaBtn) addAreaBtn.addEventListener("click", openAddAreaModal);

  // Clear filters button
  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  // Search input
  const areaSearch = document.getElementById("areaSearch");
  if (areaSearch) areaSearch.addEventListener("input", filterAreas);

  // Status filter
  const statusFilter = document.getElementById("statusFilter");
  if (statusFilter) statusFilter.addEventListener("change", filterAreas);

  // City filter
  const cityFilter = document.getElementById("cityFilter");
  if (cityFilter) cityFilter.addEventListener("change", filterAreas);

  // Close modal when clicking overlay
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("active");
    });
  });

  // Initial filter
  filterAreas();
});
