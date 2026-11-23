function togglePassword(id, button) {
  const input = document.getElementById(id);

  if (!input) return;

  if (input.type === "password") {
    input.type = "text";
    button.textContent = "🚫";
  } else {
    input.type = "password";
    button.textContent = "👁️";
  }
}
