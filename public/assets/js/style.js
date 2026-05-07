// Custom Select Styler - Automatically applies to all .filter-select elements
(function () {
  "use strict";

  // Wait for DOM to be ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  function init() {
    // Find all selects with class 'filter-select'
    const selects = document.querySelectorAll("select.filter-select, .pagination-limit select, select.form-select");

    selects.forEach((select) => {
      // Don't wrap if already processed
      if (select.parentElement.classList.contains("custom-select-wrapper"))
        return;

      createCustomSelect(select);
    });
  }

  function createCustomSelect(originalSelect) {
    // Get options
    const options = Array.from(originalSelect.options);
    const selectedValue = originalSelect.value;

    // Create wrapper
    const wrapper = document.createElement("div");
    wrapper.className = "custom-select-wrapper";

    // Create trigger button
    const trigger = document.createElement("div");
    trigger.className = "custom-select-trigger";

    // Find selected option text
    const selectedOption = options.find((opt) => opt.value === selectedValue);
    const triggerText = document.createElement("span");
    triggerText.className = "custom-select-text";
    triggerText.textContent = selectedOption
      ? selectedOption.textContent
      : options[0].textContent;

    // Create chevron
    const chevron = document.createElement("span");
    chevron.className = "custom-select-chevron";
    chevron.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    `;

    trigger.appendChild(triggerText);
    trigger.appendChild(chevron);

    // Create dropdown
    const dropdown = document.createElement("div");
    dropdown.className = "custom-select-dropdown";

    // Add options
    options.forEach((option) => {
      const optionDiv = document.createElement("div");
      optionDiv.className = "custom-select-option";
      if (option.value === selectedValue) {
        optionDiv.classList.add("selected");
      }
      optionDiv.setAttribute("data-value", option.value);
      optionDiv.textContent = option.textContent;

      optionDiv.addEventListener("click", (e) => {
        e.stopPropagation();

        // Update original select
        originalSelect.value = option.value;

        // Trigger change event
        const changeEvent = new Event("change", { bubbles: true });
        originalSelect.dispatchEvent(changeEvent);

        // Update UI
        triggerText.textContent = option.textContent;

        // Update selected class
        dropdown.querySelectorAll(".custom-select-option").forEach((opt) => {
          opt.classList.remove("selected");
        });
        optionDiv.classList.add("selected");

        // Close dropdown
        wrapper.classList.remove("open");
      });

      dropdown.appendChild(optionDiv);
    });

    wrapper.appendChild(trigger);
    wrapper.appendChild(dropdown);

    // Hide original select
    originalSelect.style.position = "absolute";
    originalSelect.style.opacity = "0";
    originalSelect.style.pointerEvents = "none";
    originalSelect.style.zIndex = "-1";

    // Insert wrapper before original select and move original inside
    originalSelect.parentNode.insertBefore(wrapper, originalSelect);
    wrapper.appendChild(originalSelect);

    // Toggle dropdown on trigger click
    trigger.addEventListener("click", (e) => {
      e.stopPropagation();

      // Close all other open dropdowns
      document
        .querySelectorAll(".custom-select-wrapper.open")
        .forEach((openWrap) => {
          if (openWrap !== wrapper) {
            openWrap.classList.remove("open");
          }
        });

      wrapper.classList.toggle("open");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
      if (!wrapper.contains(e.target)) {
        wrapper.classList.remove("open");
      }
    });

    // Sync with original select changes (in case of programmatic changes)
    originalSelect.addEventListener("change", function () {
      const newValue = this.value;
      const newOption = options.find((opt) => opt.value === newValue);
      if (newOption) {
        triggerText.textContent = newOption.textContent;
        dropdown.querySelectorAll(".custom-select-option").forEach((opt) => {
          if (opt.getAttribute("data-value") === newValue) {
            opt.classList.add("selected");
          } else {
            opt.classList.remove("selected");
          }
        });
      }
    });
  }
})();
