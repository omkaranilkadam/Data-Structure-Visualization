const themeMap = {
  dark: "light",
  light: "solar",
  solar: "dark"
};

// Initialize theme
let theme = localStorage.getItem('theme');
if (!theme || !themeMap[theme]) {
  theme = Object.keys(themeMap)[0];
  localStorage.setItem('theme', theme);
}
document.body.classList.add(theme);

function toggleTheme() {
  const current = theme;
  const next = themeMap[current];
  
  document.body.classList.replace(current, next);
  localStorage.setItem('theme', next);
  theme = next;
}

document.getElementById('themeButton').addEventListener('click', toggleTheme);