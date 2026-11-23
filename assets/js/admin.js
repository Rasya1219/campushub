document.addEventListener("DOMContentLoaded", function () {
  const formHapus = document.querySelectorAll(".form-hapus");

  formHapus.forEach(function (form) {
    form.addEventListener("submit", function (event) {
      const yakin = confirm(
        "Yakin ingin menghapus data dosen ini beserta akun loginnya?",
      );

      if (!yakin) {
        event.preventDefault();
      }
    });
  });
});

function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        button.textContent = "🚫";
    } else {
        input.type = "password";
        button.textContent = "👁️";
    }
}