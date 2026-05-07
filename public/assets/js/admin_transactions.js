// Change pagination limit
function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// View transaction details modal
function viewTransactionModal(transactionId) {
  // Hide all transaction details first
  document.querySelectorAll(".transaction-details-content").forEach((el) => {
    el.style.display = "none";
  });

  // Show the specific transaction details
  const detailsEl = document.getElementById(
    "transaction-details-" + transactionId,
  );
  if (detailsEl) {
    detailsEl.style.display = "block";

    // Show modal
    const modal = document.getElementById("transactionDetailsModal");
    if (modal) {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  }
}

// Close modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
  }
}

// Close all modals
function closeAllModals() {
  document.querySelectorAll(".modal-overlay.active").forEach((modal) => {
    modal.classList.remove("active");
  });
  document.body.style.overflow = "auto";
}

// Print transaction receipt
function printTransactionDetails() {
  const activeTransaction = document.querySelector(
    '.transaction-details-content[style*="display: block"]',
  );
  if (!activeTransaction) {
    console.warn("No active transaction details to print");
    return;
  }

  const printWindow = window.open("", "_blank");
  const content = activeTransaction.innerHTML;
  const transactionId =
    activeTransaction
      .closest('[id^="transaction-details-"]')
      ?.id.split("-")
      .pop() || "unknown";

  printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>TiffinCraft Transaction Receipt - #${transactionId}</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                        margin: 40px;
                        line-height: 1.5;
                        color: #333;
                    }
                    .receipt-container {
                        max-width: 800px;
                        margin: 0 auto;
                        background: #fff;
                    }
                    .receipt-header {
                        text-align: center;
                        padding: 20px 0;
                        border-bottom: 2px solid #4f46e5;
                        margin-bottom: 30px;
                    }
                    .receipt-header h1 {
                        margin: 0;
                        color: #4f46e5;
                        font-size: 28px;
                    }
                    .receipt-header h3 {
                        margin: 10px 0 0;
                        color: #666;
                        font-weight: normal;
                    }
                    .receipt-header p {
                        margin: 5px 0 0;
                        color: #999;
                        font-size: 12px;
                    }
                    .transaction-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #333;
                        padding-bottom: 15px;
                        margin-bottom: 25px;
                    }
                    .transaction-header h3 {
                        margin: 0;
                        color: #333;
                    }
                    .status-badge {
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                    }
                    .status-success { background: #d4edda; color: #155724; }
                    .status-pending { background: #fff3cd; color: #856404; }
                    .status-failed { background: #f8d7da; color: #721c24; }
                    .status-refunded { background: #cce5ff; color: #004085; }
                    .status-cancelled { background: #e2e3e5; color: #383d41; }
                    .details-grid {
                        display: grid;
                        grid-template-columns: repeat(1, 1fr);
                        gap: 25px;
                        margin-bottom: 30px;
                    }
                    .detail-section {
                        background: #f9fafb;
                        border-radius: 12px;
                        padding: 20px;
                    }
                    .detail-section h4 {
                        margin: 0 0 15px 0;
                        color: #4f46e5;
                        font-size: 16px;
                        border-bottom: 1px solid #e5e7eb;
                        padding-bottom: 8px;
                    }
                    .detail-row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 12px;
                        padding-bottom: 8px;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    .detail-row:last-child {
                        border-bottom: none;
                        margin-bottom: 0;
                        padding-bottom: 0;
                    }
                    .detail-label {
                        font-weight: 600;
                        color: #6b7280;
                    }
                    .detail-value {
                        color: #1f2937;
                        text-align: right;
                    }
                    .receipt-footer {
                        text-align: center;
                        padding-top: 30px;
                        margin-top: 30px;
                        border-top: 1px solid #e5e7eb;
                        color: #9ca3af;
                        font-size: 12px;
                    }
                    @media print {
                        body {
                            margin: 0;
                            padding: 20px;
                        }
                        .no-print {
                            display: none;
                        }
                        .detail-section {
                            break-inside: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="receipt-container">
                    <div class="receipt-header">
                        <h1>TiffinCraft</h1>
                        <h3>Transaction Receipt</h3>
                        <p>#${transactionId}</p>
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                    </div>
                    ${content}
                    <div class="receipt-footer">
                        <p>This is a computer-generated receipt. No signature is required.</p>
                        <p>&copy; ${new Date().getFullYear()} TiffinCraft. All rights reserved.</p>
                    </div>
                </div>
            </body>
        </html>
    `);
  printWindow.document.close();
  printWindow.print();
}

// Submit filter form
function submitFilterForm(formId) {
  const form = document.getElementById(formId);
  if (form) {
    form.submit();
  }
}

// Submit search on Enter key
function setupSearchOnEnter() {
  const searchInput = document.querySelector(".search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        const searchForm = document.getElementById("searchForm");
        if (searchForm) {
          searchForm.submit();
        }
      }
    });
  }
}

// Clear all filters and reload
function clearAllFilters() {
  window.location.href = "/admin/dashboard/transactions";
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Setup status filter change event
  const statusFilter = document.querySelector('select[name="status"]');
  if (statusFilter) {
    statusFilter.addEventListener("change", function () {
      const statusForm = document.getElementById("statusForm");
      if (statusForm) {
        statusForm.submit();
      }
    });
  }

  // Setup type filter change event
  const typeFilter = document.querySelector('select[name="reference_type"]');
  if (typeFilter) {
    typeFilter.addEventListener("change", function () {
      const typeForm = document.getElementById("typeForm");
      if (typeForm) {
        typeForm.submit();
      }
    });
  }

  // Setup clear filters button
  const clearFiltersBtn = document.getElementById("clearFiltersBtn");
  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      clearAllFilters();
    });
  }

  // Setup search on Enter key
  setupSearchOnEnter();

  // Modal overlay close
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        overlay.classList.remove("active");
        document.body.style.overflow = "auto";
      }
    });
  });

  // ESC key to close modals
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeAllModals();
    }
  });
});
