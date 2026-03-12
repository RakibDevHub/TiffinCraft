document.addEventListener("DOMContentLoaded", function () {

  const tabBtns = document.querySelectorAll(".tab-btn");
  const tabPanes = document.querySelectorAll(".tab-pane");

  const searchInput = document.getElementById("menuSearch");
  const categoryFilter = document.getElementById("categoryFilter");
  const spiceFilter = document.getElementById("spiceFilter");
  const clearFiltersBtn = document.getElementById("clearFiltersBtn");


  /* ------------------------------
     TAB SWITCHING
  ------------------------------ */
  tabBtns.forEach(btn => {
    btn.addEventListener("click", function () {

      tabBtns.forEach(b => b.classList.remove("active"));
      tabPanes.forEach(p => p.classList.remove("active"));

      this.classList.add("active");

      const tabId = this.dataset.tab;
      const activePane = document.getElementById(tabId + "Tab");

      activePane.classList.add("active");

      setTimeout(filterItems, 30);
    });
  });


  /* ------------------------------
     FILTER FUNCTION
  ------------------------------ */
  function filterItems() {

    const activeTab =
      document.querySelector(".tab-btn.active")?.dataset.tab || "all";

    const activePane = document.getElementById(activeTab + "Tab");

    const grid = activePane.querySelector(".menu-items-grid");
    const emptyState = activePane.querySelector(".empty-state");

    if (!grid) return;

    const search = searchInput.value.toLowerCase();
    const category = categoryFilter.value.toLowerCase();
    const spice = spiceFilter.value;

    const items = grid.querySelectorAll(".menu-item-card");

    let visible = 0;

    items.forEach(item => {

      item.style.display = "flex";

      const name = item.dataset.name || "";
      const cats = item.dataset.categories || "";
      const spiceLevel = item.dataset.spice || "";

      const matchSearch = !search || name.includes(search);
      const matchCategory = !category || cats.includes(category);
      const matchSpice = !spice || spiceLevel === spice;

      const show = matchSearch && matchCategory && matchSpice;

      if (!show) {
        item.style.display = "none";
      } else {
        visible++;
      }

    });


    /* ------------------------------
       EMPTY STATE CONTROL
    ------------------------------ */

    if (visible === 0) {
      grid.style.display = "none";
      if (emptyState) emptyState.style.display = "block";
    } else {
      grid.style.display = "grid";
      if (emptyState) emptyState.style.display = "none";
    }
  }

  window.filterItems = filterItems;


  /* ------------------------------
     CLEAR FILTERS
  ------------------------------ */
  function clearFilters() {

    searchInput.value = "";
    categoryFilter.value = "";
    spiceFilter.value = "";

    const activeTab =
      document.querySelector(".tab-btn.active")?.dataset.tab || "all";

    const activePane = document.getElementById(activeTab + "Tab");
    const grid = activePane.querySelector(".menu-items-grid");
    const emptyState = activePane.querySelector(".empty-state");

    document.querySelectorAll(".menu-item-card").forEach(card => {
      card.style.display = "flex";
    });

    if (grid) grid.style.display = "grid";
    if (emptyState) emptyState.style.display = "none";
  }

  window.clearFilters = clearFilters;


  /* ------------------------------
     FILTER EVENTS
  ------------------------------ */

  if (searchInput)
    searchInput.addEventListener("input", filterItems);

  if (categoryFilter)
    categoryFilter.addEventListener("change", filterItems);

  if (spiceFilter)
    spiceFilter.addEventListener("change", filterItems);

  if (clearFiltersBtn)
    clearFiltersBtn.addEventListener("click", clearFilters);

});