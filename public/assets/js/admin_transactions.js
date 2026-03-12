// // Change limit function
// function changeLimit(limit) {
//   const url = new URL(window.location.href);
//   url.searchParams.set("limit", limit);
//   url.searchParams.set("page", 1);
//   window.location.href = url.toString();
// }

// // Reject functions
// function rejectWithdraw(withdrawId) {
//   document.getElementById("rejectWithdrawId").value = withdrawId;
//   document.getElementById("rejectWithdrawModal").classList.add("active");
// }

// function rejectRefund(refundId) {
//   document.getElementById("rejectRefundId").value = refundId;
//   document.getElementById("rejectRefundModal").classList.add("active");
// }

// // Close modal function
// function closeModal(modalId) {
//   document.getElementById(modalId).classList.remove("active");
// }

// // View details functions (placeholder - implement as needed)
// function viewWithdrawalDetails(withdrawId) {
//   console.log("View withdrawal details:", withdrawId);
//   // Implement modal or redirect to withdrawal details page
// }

// function viewRefundDetails(refundId) {
//   console.log("View refund details:", refundId);
//   // Implement modal or redirect to refund details page
// }

// // Tab switching functionality
// document.addEventListener("DOMContentLoaded", function () {
//   const tabBtns = document.querySelectorAll(".tab-btn");
//   const tabContents = document.querySelectorAll(".tab-content");

//   tabBtns.forEach((btn) => {
//     btn.addEventListener("click", function () {
//       const tabId = this.dataset.tab;

//       // Update active tab
//       tabBtns.forEach((b) => b.classList.remove("active"));
//       this.classList.add("active");

//       // Show corresponding content
//       tabContents.forEach((content) => content.classList.remove("active"));
//       document.getElementById(tabId + "Tab").classList.add("active");
//     });
//   });

//   // Filter functionality for transactions tab
//   const searchInput = document.getElementById("transactionSearch");
//   const typeFilter = document.getElementById("typeFilter");
//   const statusFilter = document.getElementById("statusFilter");
//   const clearFiltersBtn = document.getElementById("clearFilters");

//   function filterTransactions() {
//     const searchText = searchInput.value.toLowerCase();
//     const typeValue = typeFilter.value;
//     const statusValue = statusFilter.value;
//     const rows = document.querySelectorAll("#transactionsTab tbody tr");

//     rows.forEach((row) => {
//       const transactionId = row.cells[0].textContent.toLowerCase();
//       const userName = row.cells[1].textContent.toLowerCase();
//       const type = row.getAttribute("data-type");
//       const status = row.getAttribute("data-status");

//       const searchMatch =
//         !searchText ||
//         transactionId.includes(searchText) ||
//         userName.includes(searchText);
//       const typeMatch = !typeValue || type === typeValue;
//       const statusMatch = !statusValue || status === statusValue;

//       row.style.display = searchMatch && typeMatch && statusMatch ? "" : "none";
//     });
//   }

//   if (searchInput) {
//     searchInput.addEventListener("input", filterTransactions);
//   }
//   if (typeFilter) {
//     typeFilter.addEventListener("change", filterTransactions);
//   }
//   if (statusFilter) {
//     statusFilter.addEventListener("change", filterTransactions);
//   }
//   if (clearFiltersBtn) {
//     clearFiltersBtn.addEventListener("click", function () {
//       if (searchInput) searchInput.value = "";
//       if (typeFilter) typeFilter.value = "";
//       if (statusFilter) statusFilter.value = "";
//       filterTransactions();
//     });
//   }

//   // Close modals when clicking outside
//   document.querySelectorAll(".modal-overlay").forEach((overlay) => {
//     overlay.addEventListener("click", (e) => {
//       if (e.target === overlay) {
//         overlay.classList.remove("active");
//       }
//     });
//   });

//   // Close modals with escape key
//   document.addEventListener("keydown", (e) => {
//     if (e.key === "Escape") {
//       document.querySelectorAll(".modal-overlay").forEach((overlay) => {
//         overlay.classList.remove("active");
//       });
//     }
//   });
// });
