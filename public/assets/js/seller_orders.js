// Simple filtering function
function filterOrders() {
    const searchText = document.getElementById("orderSearch").value.toLowerCase();
    const statusValue = document.getElementById("statusFilter").value.toLowerCase();
    const dateRange = document.getElementById("dateRangeFilter").value;
    const dateFrom = document.getElementById("dateFrom").value;
    const dateTo = document.getElementById("dateTo").value;
    
    const rows = document.querySelectorAll(".users-table tbody tr");
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    rows.forEach((row) => {
        const searchTextData = row.dataset.searchText || '';
        const status = row.dataset.status;
        const orderDate = row.dataset.orderDate;

        // Comprehensive search
        const searchMatch = !searchText || searchTextData.includes(searchText);
        const statusMatch = !statusValue || status === statusValue;

        // Date filtering
        let dateMatch = true;
        if (dateRange !== "all" && orderDate) {
            const orderDateObj = new Date(orderDate);
            orderDateObj.setHours(0, 0, 0, 0);

            switch (dateRange) {
                case "today":
                    dateMatch = orderDateObj.getTime() === today.getTime();
                    break;
                case "yesterday":
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    dateMatch = orderDateObj.getTime() === yesterday.getTime();
                    break;
                case "week":
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    dateMatch = orderDateObj >= startOfWeek;
                    break;
                case "month":
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    dateMatch = orderDateObj >= startOfMonth;
                    break;
                case "custom":
                    if (dateFrom) dateMatch = dateMatch && orderDate >= dateFrom;
                    if (dateTo) dateMatch = dateMatch && orderDate <= dateTo;
                    break;
            }
        }

        row.style.display = searchMatch && statusMatch && dateMatch ? "" : "none";
    });
}

function clearFilters() {
    document.getElementById("orderSearch").value = "";
    document.getElementById("statusFilter").value = "";
    document.getElementById("dateRangeFilter").value = "all";
    document.getElementById("dateFrom").value = "";
    document.getElementById("dateTo").value = "";
    document.getElementById("customDateRange").style.display = "none";
    filterOrders();
}

function toggleCustomDateRange() {
    const dateRangeFilter = document.getElementById("dateRangeFilter");
    const customDateRange = document.getElementById("customDateRange");

    if (dateRangeFilter.value === "custom") {
        customDateRange.style.display = "flex";
        customDateRange.style.gap = "8px";
    } else {
        customDateRange.style.display = "none";
        document.getElementById("dateFrom").value = "";
        document.getElementById("dateTo").value = "";
    }
}

let selectedOrderId = null;

function updateOrderStatus(orderId, status, needReason = false) {

    if (status === 'CANCELLED') {
        selectedOrderId = orderId;
        document.getElementById("cancelReasonInput").value = "";
        document.getElementById("cancelOrderModal").classList.add("active");
        return;
    }

    // For other statuses (no reason needed)
    document.getElementById("formOrderId").value = orderId;
    document.getElementById("formStatus").value = status;
    document.getElementById("formReason").value = "";
    document.getElementById("statusUpdateForm").submit();
}

function submitCancelOrder() {
    const reason = document.getElementById("cancelReasonInput").value.trim();

    if (!reason) {
        document.getElementById("cancelReasonInput").focus();
        return;
    }

    document.getElementById("formOrderId").value = selectedOrderId;
    document.getElementById("formStatus").value = "CANCELLED";
    document.getElementById("formReason").value = reason;

    document.getElementById("statusUpdateForm").submit();
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove("active");
}

function viewOrderDetails(orderId) {
    // Hide all order detail sections first
    document.querySelectorAll(".order-details-content").forEach((detail) => {
        detail.style.display = "none";
    });

    // Show the specific order details
    const orderDetail = document.getElementById("order-details-" + orderId);
    if (orderDetail) {
        orderDetail.style.display = "block";
    }

    document.getElementById("orderDetailsModal").classList.add("active");
}

// Initialize event listeners
document.addEventListener("DOMContentLoaded", function () {
    // Setup dropdown menus
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        const button = dropdown.querySelector('.btn-edit');
        const menu = dropdown.querySelector('.status-dropdown-menu');
        
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener('click', () => {
            menu.classList.remove('show');
        });
    });

    // Real-time filtering with debounce
    const searchInput = document.getElementById("orderSearch");
    let searchTimeout;
    
    searchInput.addEventListener("input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterOrders, 300);
    });
    
    // Filter listeners
    document.getElementById("statusFilter").addEventListener("change", filterOrders);
    document.getElementById("dateRangeFilter").addEventListener("change", function() {
        toggleCustomDateRange();
        filterOrders();
    });
    document.getElementById("dateFrom").addEventListener("change", filterOrders);
    document.getElementById("dateTo").addEventListener("change", filterOrders);

    // Clear filters button
    document.getElementById("clearFilters").addEventListener("click", clearFilters);

    // Modal overlay close
    document.querySelectorAll(".modal-overlay").forEach((overlay) => {
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) {
                overlay.classList.remove("active");
            }
        });
    });
    
    // Initialize custom date range visibility
    toggleCustomDateRange();
});