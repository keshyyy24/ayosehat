document.addEventListener("DOMContentLoaded", () => {
  const paymentSelect = document.querySelector("[data-payment-select]");
  const bpjsSection = document.querySelector("[data-bpjs-section]");
  const bpjsInput = document.querySelector("[data-bpjs-input]");

  if (paymentSelect && bpjsSection && bpjsInput) {
    const syncPayment = () => {
      const isBpjs = paymentSelect.value === "BPJS";
      bpjsSection.style.display = isBpjs ? "block" : "none";
      bpjsInput.required = isBpjs;
    };

    paymentSelect.addEventListener("change", syncPayment);
    syncPayment();
  }

  document.querySelectorAll("[data-fasilitas-card]").forEach((card) => {
    card.addEventListener("click", () => {
      document.querySelectorAll("[data-fasilitas-card]").forEach((item) => {
        if (item !== card) item.classList.remove("active");
      });
      card.classList.toggle("active");
    });
  });

  const profileButton = document.querySelector("[data-profile-open]");
  const profileClose = document.querySelector("[data-profile-close]");
  const sidebar = document.querySelector("[data-profile-sidebar]");

  if (profileButton && profileClose && sidebar) {
    profileButton.addEventListener("click", () => {
      sidebar.classList.add("open");
    });
    profileClose.addEventListener("click", () => {
      sidebar.classList.remove("open");
    });
  }

  const searchInput = document.querySelector("[data-search]");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      const keyword = searchInput.value.toLowerCase();
      document.querySelectorAll("[data-search-item]").forEach((item) => {
        item.style.display = item.textContent.toLowerCase().includes(keyword)
          ? ""
          : "none";
      });
    });
  }
});
