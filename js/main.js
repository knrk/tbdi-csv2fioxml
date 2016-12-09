function toggleClass(dest, cls) {
  document.querySelector(dest).classList.toggle(cls);
}
function toggleSettings() {
  toggleClass(".flip-container", "settings");
}
function toggleSub() {
  toggleClass(".mdl-checkbox + .sub", "hide");
}
