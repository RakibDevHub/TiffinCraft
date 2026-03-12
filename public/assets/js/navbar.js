document.addEventListener("DOMContentLoaded", () => {
  const ctaBar = document.getElementById("ctaBar");
  const navbar = document.querySelector(".navbar");
  const closeBtn = document.getElementById("closeCta");

  if (!ctaBar || !navbar) return;

  // Track closed state only for current page session
  let ctaClosed = false;

  function showCTA() {
    if (ctaClosed) return; // don't show if user clicked close
    ctaBar.classList.add("show");
    navbar.style.top = `${ctaBar.offsetHeight}px`;
  }

  function hideCTA() {
    ctaBar.classList.remove("show");
    navbar.style.top = "0";
  }

  // Close button
  closeBtn?.addEventListener("click", () => {
    ctaClosed = true; // only current session
    hideCTA();
  });

  // Scroll logic
  window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
      showCTA();
    } else {
      hideCTA();
    }
  });

  // Initialize on page load
  if (window.scrollY > 50) showCTA();
});
