function changeLimit(newLimit) {
  const url = new URL(window.location.href);
  url.searchParams.set("limit", newLimit);
  url.searchParams.set("page", 1);
  window.location.href = url.toString();
}

// Modal functions
function openAddCategoryModal() {
  document.getElementById("addCategoryModal").classList.add("active");
}

function openEditCategoryModal(id, name, description, image) {
  document.getElementById("editCategoryId").value = id;
  document.getElementById("editCategoryName").value = name;
  document.getElementById("editCategoryDescription").value = description;

  const container = document.getElementById("currentImageContainer");
  if (image) {
    container.innerHTML = `
      <p>Current Image:</p>
      <img src="/uploads/categories/${image}" style="max-width: 100px; max-height: 100px; border-radius: 4px;">
    `;
  } else {
    container.innerHTML = "<p>No current image</p>";
  }

  document.getElementById("editCategoryModal").classList.add("active");
}

function openDeleteCategoryModal(id, name) {
  document.getElementById("deleteCategoryId").value = id;
  document.getElementById("deleteCategoryName").textContent = name;
  document.getElementById("deleteCategoryModal").classList.add("active");
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

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

function clearFilters() {
  const search = document.getElementById("categorySearch");

  if (search) search.value = "";

  filterCategories();
}

function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  preview.innerHTML = ""; // Clear old preview

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.innerHTML = `
        <img src="${e.target.result}" 
             style="max-width: 100px; max-height: 100px; border-radius: 4px; border:1px solid #ddd;">
      `;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  // Event listeners
  const addCategoryBtn = document.getElementById("addCategoryBtn");
  if (addCategoryBtn)
    addCategoryBtn.addEventListener("click", openAddCategoryModal);

  const clearFiltersBtn = document.getElementById("clearFilters");
  if (clearFiltersBtn) clearFiltersBtn.addEventListener("click", clearFilters);

  const categorySearch = document.getElementById("categorySearch");
  if (categorySearch)
    categorySearch.addEventListener("input", filterCategories);

  const addImageInput = document.getElementById("imageUpload");
  if (addImageInput) {
    addImageInput.addEventListener("change", function () {
      previewImage(this, "imagePreview");
    });
  }

  const editImageInput = document.getElementById("editImageUpload");
  if (editImageInput) {
    editImageInput.addEventListener("change", function () {
      previewImage(this, "editImagePreview");
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
