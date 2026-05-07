// Change pagination limit
function changeLimit(newLimit) {
    const url = new URL(window.location.href);
    url.searchParams.set("limit", newLimit);
    url.searchParams.set("page", 1);
    window.location.href = url.toString();
}

// Suspend kitchen modal
function openSuspendModal(kitchenId, kitchenName) {
    document.getElementById("suspendKitchenId").value = kitchenId;
    document.getElementById("suspendKitchenName").value = kitchenName;
    document.getElementById("suspendModal").classList.add("active");
}

// Lift suspension modal
function openLiftSuspensionModal(kitchenId, kitchenName, suspensionReason, suspendedUntil) {
    document.getElementById("liftSuspensionKitchenId").value = kitchenId;
    document.getElementById("liftSuspensionKitchenName").textContent = kitchenName;

    const detailsContainer = document.getElementById("suspensionDetails");
    if (suspensionReason && suspensionReason !== '') {
        document.getElementById("suspensionReason").textContent = suspensionReason;
        document.getElementById("suspendedUntil").textContent = suspendedUntil || "Permanent";
        detailsContainer.style.display = "block";
    } else {
        detailsContainer.style.display = "none";
    }

    document.getElementById("liftSuspensionModal").classList.add("active");
}

// View kitchen details
function viewKitchenDetails(kitchenId) {
    document.querySelectorAll(".kitchen-details-content").forEach((detail) => {
        detail.style.display = "none";
    });

    const kitchenDetail = document.getElementById("kitchen-details-" + kitchenId);
    if (kitchenDetail) {
        kitchenDetail.style.display = "block";
    }

    document.getElementById("kitchenDetailsModal").classList.add("active");

    const scrollContainer = document.querySelector(".modal-body");
    if (scrollContainer) scrollContainer.scrollTop = 0;
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("active");
    }
}

// Filter kitchens based on search, and status
function filterKitchens() {
    const searchText = (document.getElementById("kitchenSearch")?.value || "").toLowerCase();
    const statusValue = document.getElementById("statusFilter")?.value || "";
    const rows = document.querySelectorAll(".users-table tbody tr");

    rows.forEach((row) => {
        const kitchenName = row.querySelector(".user-info .user-details h4")?.textContent.toLowerCase() || "";
        const ownerName = row.querySelectorAll(".user-details h4")[1]?.textContent.toLowerCase() || "";
        const combinedStatus = row.dataset.combinedStatus;

        // Search matching
        const searchMatch = !searchText || kitchenName.includes(searchText) || ownerName.includes(searchText);

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

        row.style.display = searchMatch && statusMatch ? "" : "none";
    });
}

// Clear all filters
function clearFilters() {
    const search = document.getElementById("kitchenSearch");
    const status = document.getElementById("statusFilter");

    if (search) search.value = "";
    if (status) status.value = "";

    filterKitchens();
}

// Print kitchen details report
function printKitchenDetails() {
    const activeKitchen = document.querySelector('.kitchen-details-content[style*="display: block"]');
    if (activeKitchen) {
        const kitchenName = activeKitchen.querySelector('h2')?.textContent || 'Kitchen Report';
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Kitchen Report - ${escapeHtml(kitchenName)}</title>
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
                        .report-container {
                            max-width: 1200px;
                            margin: 0 auto;
                            background: #fff;
                        }
                        .report-header {
                            text-align: center;
                            padding: 20px 0;
                            border-bottom: 2px solid #4f46e5;
                            margin-bottom: 30px;
                        }
                        .report-header h1 {
                            margin: 0;
                            color: #4f46e5;
                            font-size: 28px;
                        }
                        .report-header h3 {
                            margin: 10px 0 0;
                            color: #666;
                            font-weight: normal;
                        }
                        .report-header p {
                            margin: 5px 0 0;
                            color: #999;
                            font-size: 12px;
                        }
                        .kitchen-profile-header {
                            display: flex;
                            align-items: center;
                            margin-bottom: 30px;
                            border-bottom: 2px solid #333;
                            padding-bottom: 20px;
                        }
                        .kitchen-avatar-large img {
                            width: 80px;
                            height: 80px;
                            border-radius: 12px;
                            margin-right: 20px;
                            object-fit: cover;
                        }
                        .kitchen-profile-info h2 {
                            margin: 0 0 5px 0;
                            color: #333;
                        }
                        .kitchen-address {
                            color: #666;
                            margin: 0 0 10px 0;
                        }
                        .kitchen-badges {
                            margin-top: 10px;
                        }
                        .status-badge {
                            padding: 4px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: 600;
                            display: inline-block;
                            margin-right: 8px;
                        }
                        .status-approved, .status-success { background: #d4edda; color: #155724; }
                        .status-pending { background: #fff3cd; color: #856404; }
                        .status-rejected { background: #f8d7da; color: #721c24; }
                        .status-suspended { background: #e2e3e5; color: #383d41; }
                        .analytics-overview {
                            display: grid;
                            grid-template-columns: repeat(4, 1fr);
                            gap: 20px;
                            margin: 30px 0;
                        }
                        .analytics-card {
                            background: #f9fafb;
                            border-radius: 12px;
                            padding: 20px;
                            text-align: center;
                            border: 1px solid #e5e7eb;
                        }
                        .analytics-icon {
                            width: 48px;
                            height: 48px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto 12px;
                        }
                        .analytics-icon.revenue { background: #dbeafe; color: #2563eb; }
                        .analytics-icon.orders { background: #dcfce7; color: #16a34a; }
                        .analytics-icon.commission { background: #fef3c7; color: #d97706; }
                        .analytics-icon.balance { background: #e0e7ff; color: #4f46e5; }
                        .analytics-icon i { font-size: 24px; }
                        .analytics-content h3 {
                            margin: 0 0 5px 0;
                            font-size: 24px;
                            color: #1f2937;
                        }
                        .analytics-content p {
                            margin: 0;
                            color: #6b7280;
                            font-size: 14px;
                        }
                        .analytics-content small {
                            color: #9ca3af;
                            font-size: 12px;
                        }
                        .kitchen-details-flex {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 25px;
                            margin-top: 20px;
                        }
                        .detail-section {
                            background: #f9fafb;
                            border-radius: 12px;
                            padding: 20px;
                        }
                        .detail-section h3 {
                            margin: 0 0 15px 0;
                            font-size: 18px;
                            color: #4f46e5;
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
                        .text-success { color: #16a34a; }
                        .text-warning { color: #d97706; }
                        .text-danger { color: #dc2626; }
                        .text-primary { color: #4f46e5; }
                        .rating-stars i { color: #fbbf24; margin-right: 2px; }
                        .report-footer {
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
                    <div class="report-container">
                        <div class="report-header">
                            <h1>TiffinCraft</h1>
                            <h3>Kitchen Performance Report</h3>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                        </div>
                        ${activeKitchen.innerHTML}
                        <div class="report-footer">
                            <p>This is an official TiffinCraft report. Generated by system.</p>
                            <p>&copy; ${new Date().getFullYear()} TiffinCraft. All rights reserved.</p>
                        </div>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
    // Filter event listeners
    const kitchenSearch = document.getElementById("kitchenSearch");
    if (kitchenSearch) {
        kitchenSearch.addEventListener("input", filterKitchens);
    }

    const statusFilter = document.getElementById("statusFilter");
    if (statusFilter) {
        statusFilter.addEventListener("change", filterKitchens);
    }

    const clearFiltersBtn = document.getElementById("clearFilters");
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener("click", clearFilters);
    }

    // Modal overlay close
    document.querySelectorAll(".modal-overlay").forEach((overlay) => {
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) {
                overlay.classList.remove("active");
                document.body.style.overflow = "auto";
            }
        });
    });

    // Close modals with escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-overlay.active").forEach((modal) => {
                modal.classList.remove("active");
            });
            document.body.style.overflow = "auto";
        }
    });

    // Initial filter
    filterKitchens();
});