function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

function openSuspendModal(kitchenId, kitchenName) {
  document.getElementById("suspendKitchenId").value = kitchenId;
  document.getElementById("suspendKitchenName").value = kitchenName;
  document.getElementById("suspendModal").classList.add("active");
}

function openLiftSuspensionModal(
  kitchenId,
  kitchenName,
  suspensionReason,
  suspendedUntil
) {
  document.getElementById("liftSuspensionKitchenId").value = kitchenId;
  document.getElementById("liftSuspensionKitchenName").textContent =
    kitchenName;

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

function viewKitchenDetails(kitchenId) {
  document
    .querySelectorAll(".kitchen-details-content")
    .forEach((detail) => (detail.style.display = "none"));

  const kitchenDetail = document.getElementById("kitchen-details-" + kitchenId);
  if (kitchenDetail) kitchenDetail.style.display = "block";

  document.getElementById("kitchenDetailsModal").classList.add("active");

  const scrollContainer = document.querySelector(".modal-body");
  if (scrollContainer) scrollContainer.scrollTop = 0;
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

function filterKitchens() {
    const searchText = (document.getElementById("kitchenSearch")?.value || "").toLowerCase();
    const approvalValue = document.getElementById("approvalFilter")?.value || "";
    const statusValue = document.getElementById("statusFilter")?.value || "";
    const rows = document.querySelectorAll(".users-table tbody tr");

    rows.forEach((row) => {
        const kitchenName = row.querySelector(".user-details h4")?.textContent.toLowerCase() || "";
        const ownerName = row.querySelectorAll(".user-details h4")[1]?.textContent.toLowerCase() || "";
        const approval = row.dataset.approval;
        const combinedStatus = row.dataset.combinedStatus;

        // Search matching
        const searchMatch = !searchText ||
            kitchenName.includes(searchText) ||
            ownerName.includes(searchText);

        // Approval filter matching
        const approvalMatch = !approvalValue || approval === approvalValue;

        // Combined status filter matching
        let statusMatch = true;
        if (statusValue) {
            switch (statusValue) {
                case 'active':
                    statusMatch = combinedStatus === 'active';
                    break;
                case 'suspended':
                    statusMatch = combinedStatus === 'suspended';
                    break;
                case 'inactive':
                    statusMatch = combinedStatus === 'inactive';
                    break;
                case 'pending':
                    statusMatch = combinedStatus === 'pending';
                    break;
                case 'rejected':
                    statusMatch = combinedStatus === 'rejected';
                    break;
                default:
                    statusMatch = true;
            }
        }

        row.style.display = searchMatch && approvalMatch && statusMatch ? "" : "none";
    });
}

function clearFilters() {
  const search = document.getElementById("kitchenSearch");
  const approval = document.getElementById("approvalFilter");
  const status = document.getElementById("statusFilter");

  if (search) search.value = "";
  if (approval) approval.value = "";
  if (status) status.value = "";

  filterKitchens();
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {

  // Event listeners
  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  const kitchenSearch = document.getElementById("kitchenSearch");
  if (kitchenSearch) kitchenSearch.addEventListener("input", filterKitchens);

  const approvalFilter = document.getElementById("approvalFilter");
  if (approvalFilter) approvalFilter.addEventListener("change", filterKitchens);

  const statusFilter = document.getElementById("statusFilter");
  if (statusFilter) statusFilter.addEventListener("change", filterKitchens);

  // Modal overlay close
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("active");
    });
  });

  filterKitchens();
});
