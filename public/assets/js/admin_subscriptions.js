function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Modal functions
function openAddPlanModal() {
  document.getElementById("addPlanModal").classList.add("active");
}

function openEditPlanModal(
  id,
  name,
  description,
  monthlyFee,
  commissionRate,
  maxItems,
  isActive,
  isHighlight
) {
  document.getElementById("editPlanId").value = id;
  document.getElementById("editPlanName").value = name;
  document.getElementById("editPlanDescription").value = description;
  document.getElementById("editMonthlyFee").value = monthlyFee;
  document.getElementById("editCommissionRate").value = commissionRate;
  document.getElementById("editMaxItems").value = maxItems;
  document.getElementById("editIsActive").checked = isActive == 1;
  document.getElementById("editIsHighlight").checked = isHighlight == 1;

  document.getElementById("editPlanModal").classList.add("active");
}

function openDeletePlanModal(id, name) {
  document.getElementById("deletePlanId").value = id;
  document.getElementById("deletePlanName").textContent =
    name || "Unknown Plan";
  document.getElementById("deletePlanModal").classList.add("active");
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

function filterPlans() {
  const searchText = (
    document.getElementById("planSearch")?.value || ""
  ).toLowerCase();
  const statusValue = document.getElementById("statusFilter")?.value || "";
  const rows = document.querySelectorAll(".users-table tbody tr");

  rows.forEach((row) => {
    const planName =
      row.querySelector(".plan-name h4")?.textContent.toLowerCase() || "";
    const status = row.dataset.status;

    const searchMatch = !searchText || planName.includes(searchText);
    const statusMatch = !statusValue || status === statusValue;

    row.style.display = searchMatch && statusMatch ? "" : "none";
  });
}

function clearFilters() {
  document.getElementById("planSearch").value = "";
  document.getElementById("statusFilter").value = "";

  filterPlans();
}

document.addEventListener("DOMContentLoaded", function () {
  // Event listeners
  const addPlanBtn = document.getElementById("addPlanBtn");
  if (addPlanBtn) addPlanBtn.addEventListener("click", openAddPlanModal);

  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  const planSearch = document.getElementById("planSearch");
  if (planSearch) planSearch.addEventListener("input", filterPlans);

  const statusFilter = document.getElementById("statusFilter");
  if (statusFilter) statusFilter.addEventListener("change", filterPlans);

  // Modal overlay close
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("active");
    });
  });

  filterPlans();
});
