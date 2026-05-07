// Change items per page
function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Open add category modal
function openAddCategoryModal() {
  document.getElementById("addCategoryModal").classList.add("active");
}

// Open edit category modal and populate form
function openEditCategoryModal(id, name, description, image) {
  document.getElementById("editCategoryId").value = id;
  document.getElementById("editCategoryName").value = name;
  document.getElementById("editCategoryDescription").value = description;

  // Clear preview and file input
  const editPreview = document.getElementById("editImagePreview");
  if (editPreview) editPreview.innerHTML = "";

  const changeImage = document.getElementById("changeImage");
  if (changeImage) changeImage.value = "";

  // Hide clear button
  const clearBtn = document.getElementById("clearEditPreview");
  if (clearBtn) clearBtn.style.display = "none";

  // Show current image container
  const container = document.getElementById("currentImageContainer");
  if (image) {
    container.innerHTML = `
            <img src="/uploads/categories/${image}" style="width: 150px; height: 100px; border-radius: 4px;">
        `;
  } else {
    container.innerHTML = "<p>No current image</p>";
  }

  document.getElementById("editCategoryModal").classList.add("active");
}

// Open delete category modal
function openDeleteCategoryModal(id, name) {
  document.getElementById("deleteCategoryId").value = id;
  document.getElementById("deleteCategoryName").textContent = name;
  document.getElementById("deleteCategoryModal").classList.add("active");
}

// Close any modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove("active");
}

// Filter categories by search text
function filterCategories() {
  const searchText = (
    document.getElementById("categorySearch")?.value || ""
  ).toLowerCase();
  const rows = document.querySelectorAll(".users-table tbody tr");

  rows.forEach((row) => {
    const categoryName =
      row.querySelector(".user-details h4")?.textContent.toLowerCase() || "";
    const searchMatch = !searchText || categoryName.includes(searchText);
    row.style.display = searchMatch ? "" : "none";
  });
}

// Clear all filters
function clearFilters() {
  const search = document.getElementById("categorySearch");
  if (search) search.value = "";
  filterCategories();
}

// Preview image before upload with remove button
function previewImage(input, previewId, clearBtnId) {
  const preview = document.getElementById(previewId);
  const clearBtn = document.getElementById(clearBtnId);

  if (!preview) return;

  preview.innerHTML = "";

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.innerHTML = `
                <div style="position: relative; display: inline-block; margin-top: 10px;">
                    <img src="${e.target.result}" style="width: 150px; height: 100px; border-radius: 4px; border: 1px solid #ddd;">
                    <button type="button" class="remove-preview-btn" onclick="removePreview('${previewId}', '${clearBtnId}', '${input.id}')" 
                        style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; border-radius: 50%; background: #ef4444; color: white; border: none; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;">
                        ×
                    </button>
                </div>
            `;
      if (clearBtn) clearBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    if (clearBtn) clearBtn.style.display = "none";
  }
}

// Remove preview image
function removePreview(previewId, clearBtnId, inputId) {
  const preview = document.getElementById(previewId);
  const clearBtn = document.getElementById(clearBtnId);
  const fileInput = document.getElementById(inputId);

  if (preview) preview.innerHTML = "";
  if (fileInput) fileInput.value = "";
  if (clearBtn) clearBtn.style.display = "none";
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  const addCategoryBtn = document.getElementById("addCategoryBtn");
  if (addCategoryBtn)
    addCategoryBtn.addEventListener("click", openAddCategoryModal);

  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  const categorySearch = document.getElementById("categorySearch");
  if (categorySearch)
    categorySearch.addEventListener("input", filterCategories);

  // Add image preview with remove
  const addImageInput = document.getElementById("imageUpload");
  if (addImageInput) {
    addImageInput.addEventListener("change", function () {
      previewImage(this, "imagePreview", "clearAddPreview");
    });
  }

  // Edit image preview with remove
  const editImageInput = document.getElementById("changeImage");
  if (editImageInput) {
    editImageInput.addEventListener("change", function () {
      previewImage(this, "editImagePreview", "clearEditPreview");
    });
  }

  // Modal overlay close
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("active");
    });
  });

  filterCategories();
});
