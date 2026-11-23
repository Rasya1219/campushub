document.addEventListener("DOMContentLoaded", function () {
  const tombol = document.getElementById("themeToggle");

  // Ambil tema yang tersimpan
  const tema = localStorage.getItem("theme");

  if (tema === "dark") {
    document.documentElement.classList.add("dark");
  }

  function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === "password") {
      input.type = "text";
      button.textContent = "🚫";
    } else {
      input.type = "password";
      button.textContent = "👁";
    }
  }
  function updateIcon() {
    if (!tombol) return;

    tombol.textContent = document.documentElement.classList.contains("dark")
      ? "☀️"
      : "🌙";
  }

  updateIcon();

  if (tombol) {
    tombol.addEventListener("click", function () {
      document.documentElement.classList.toggle("dark");

      const dark = document.documentElement.classList.contains("dark");

      localStorage.setItem("theme", dark ? "dark" : "light");
      updateIcon();
    });
  }
});
