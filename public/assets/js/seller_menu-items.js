function changeLimit(newLimit) {
    const url = new URL(window.location.href);
    url.searchParams.set("limit", newLimit);
    url.searchParams.set("page", 1);
    window.location.href = url.toString();
}

// Modal functions
function openAddModal() {
    document.getElementById("addModal").classList.add("active");
}

function editItem(item) {
    document.getElementById("edit_item_id").value = item.ITEM_ID;
    document.getElementById("edit_name").value = item.NAME;
    document.getElementById("edit_description").value = item.DESCRIPTION || "";
    document.getElementById("edit_price").value = item.PRICE;
    document.getElementById("edit_portion_size").value = item.PORTION_SIZE || "";
    document.getElementById("edit_spice_level").value = item.SPICE_LEVEL;
    document.getElementById("edit_daily_stock").value = item.DAILY_STOCK;
    document.getElementById("edit_is_available").checked = item.IS_AVAILABLE == 1;

    // Clear previous image preview
    const preview = document.getElementById("editPreview");
    if (preview) preview.innerHTML = "";

    // Show current image if exists
    if (item.ITEM_IMAGE) {
        const container = document.createElement("div");
        container.style.marginTop = "1rem";
        container.style.textAlign = "center";
        container.innerHTML = `
            <p style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Current Image:</p>
            <img src="/uploads/menu/${escapeHtml(item.ITEM_IMAGE)}" 
                 style="max-width: 100px; max-height: 100px; border-radius: 8px; border: 1px solid var(--gray-200);">
        `;
        preview.appendChild(container);
    }

    // Check category checkboxes
    const categories = (item.CATEGORY_IDS || "").split(",");
    document.querySelectorAll(".edit-category").forEach(checkbox => {
        const categoryId = checkbox.value;
        checkbox.checked = categories.includes(categoryId);
    });

    document.getElementById("editModal").classList.add("active");
}

function openDeleteModal(itemId, itemName) {
    document.getElementById("deleteItemId").value = itemId;
    document.getElementById("deleteItemName").textContent = itemName;
    document.getElementById("deleteModal").classList.add("active");
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("active");
    }
    
    // Reset forms when closing modals
    if (modalId === "addModal") {
        const form = document.querySelector("#addModal form");
        if (form) form.reset();
        const preview = document.getElementById("addPreview");
        if (preview) preview.innerHTML = "";
    }
    if (modalId === "editModal") {
        const preview = document.getElementById("editPreview");
        if (preview) preview.innerHTML = "";
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    preview.innerHTML = "";

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `
                <div style="margin-top: 1rem; text-align: center;">
                    <p style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">New Image Preview:</p>
                    <img src="${e.target.result}" 
                         style="max-width: 100px; max-height: 100px; border-radius: 8px; border: 1px solid var(--gray-200);">
                </div>
            `;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function filterItems() {
    const activeTab = document.querySelector(".tab-btn.active")?.dataset.tab || "all";
    const activePane = document.getElementById(activeTab + "Tab");
    
    if (!activePane) return;
    
    const grid = activePane.querySelector(".menu-items-grid");
    const emptyState = activePane.querySelector(".empty-state");
    
    if (!grid) return;
    
    const searchText = (document.getElementById("menuSearch")?.value || "").toLowerCase();
    const categoryValue = (document.getElementById("categoryFilter")?.value || "").toLowerCase();
    const spiceValue = (document.getElementById("spiceFilter")?.value || "");
    
    const items = grid.querySelectorAll(".menu-item-card");
    let visibleCount = 0;
    
    items.forEach((item) => {
        const name = item.dataset.name || "";
        const categories = item.dataset.categories || "";
        const spiceLevel = item.dataset.spice || "";
        
        const searchMatch = !searchText || name.includes(searchText);
        const categoryMatch = !categoryValue || categories.includes(categoryValue);
        const spiceMatch = !spiceValue || spiceLevel === spiceValue;
        
        const shouldShow = searchMatch && categoryMatch && spiceMatch;
        
        item.style.display = shouldShow ? "flex" : "none";
        if (shouldShow) visibleCount++;
    });
    
    // Handle empty state
    if (visibleCount === 0) {
        if (grid) grid.style.display = "none";
        if (emptyState) emptyState.style.display = "block";
    } else {
        if (grid) grid.style.display = "grid";
        if (emptyState) emptyState.style.display = "none";
    }
}

function clearFilters() {
    const search = document.getElementById("menuSearch");
    const category = document.getElementById("categoryFilter");
    const spice = document.getElementById("spiceFilter");
    
    if (search) search.value = "";
    if (category) category.value = "";
    if (spice) spice.value = "";
    
    // Reset all tabs
    const allTabs = document.querySelectorAll(".tab-pane");
    allTabs.forEach(tab => {
        const grid = tab.querySelector(".menu-items-grid");
        const emptyState = tab.querySelector(".empty-state");
        const items = tab.querySelectorAll(".menu-item-card");
        
        items.forEach(item => {
            item.style.display = "flex";
        });
        
        if (grid) grid.style.display = "grid";
        if (emptyState) emptyState.style.display = "none";
    });
    
    filterItems();
}

// Helper functions
function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
    // Tab switching
    const tabBtns = document.querySelectorAll(".tab-btn");
    const tabPanes = document.querySelectorAll(".tab-pane");
    
    tabBtns.forEach((btn) => {
        btn.addEventListener("click", function () {
            const tabId = this.dataset.tab;
            
            // Update active tab button
            tabBtns.forEach((b) => b.classList.remove("active"));
            this.classList.add("active");
            
            // Show corresponding content
            tabPanes.forEach((pane) => pane.classList.remove("active"));
            const activePane = document.getElementById(tabId + "Tab");
            if (activePane) activePane.classList.add("active");
            
            // Re-apply filters when switching tabs
            setTimeout(filterItems, 50);
        });
    });
    
    // Event listeners for filters
    const menuSearch = document.getElementById("menuSearch");
    if (menuSearch) menuSearch.addEventListener("input", filterItems);
    
    const categoryFilter = document.getElementById("categoryFilter");
    if (categoryFilter) categoryFilter.addEventListener("change", filterItems);
    
    const spiceFilter = document.getElementById("spiceFilter");
    if (spiceFilter) spiceFilter.addEventListener("change", filterItems);
    
    const clearFiltersBtn = document.getElementById("clearFiltersBtn");
    if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);
    
    // Add Item button
    const addItemBtn = document.querySelector(".btn-primary[onclick='openAddModal()']");
    if (addItemBtn && !addItemBtn.hasAttribute("disabled")) {
        addItemBtn.addEventListener("click", openAddModal);
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
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-overlay.active").forEach((modal) => {
                modal.classList.remove("active");
            });
            document.body.style.overflow = "auto";
        }
    });
    
    // Stock form confirmation
    const stockForms = document.querySelectorAll(".stock-form");
    stockForms.forEach((form) => {
        form.addEventListener("submit", function (e) {
            const stockInput = this.querySelector(".stock-input");
            if (stockInput && stockInput.value < 0) {
                e.preventDefault();
                alert("Stock cannot be negative");
            }
        });
    });
    
    // Toggle form confirmation
    const toggleForms = document.querySelectorAll(".toggle-form");
    toggleForms.forEach((form) => {
        form.addEventListener("submit", function (e) {
            const isAvailable = this.querySelector("input[name='is_available']").value;
            const action = isAvailable === "1" ? "show" : "hide";
            if (!confirm(`Are you sure you want to ${action} this item?`)) {
                e.preventDefault();
            }
        });
    });
    
    // Initial filter
    filterItems();
});