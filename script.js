
// function login() {
//   const username = document.getElementById('username').value;
//   const password = document.getElementById('password').value;
  
//   // would validate info here
//   showPage('orders');
// }

// function logout() {

//   document.getElementById('username').value = '';
//   document.getElementById('password').value = '';
  
//   // NAVIGATE BACK TO LOGIN
//   showPage('login');
  

//   navigate('orders');
// }

function showPage(pageName) {
  const pages = document.querySelectorAll('.page');
  pages.forEach(page => page.classList.remove('active'));

  const targetPage = document.getElementById(pageName + 'Page');
  if (targetPage) {
    targetPage.classList.add('active');
  }
}

function navigate(section) {
  // Hide all sections
  const sections = ['ordersSection', 'inventorySection', 'mplSection', 'skusSection', 'placeholderContent'];
  sections.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });

  // Show the requested section
  const sectionIds = {
    inventory: 'inventorySection',
    orders:    'ordersSection',
    mpl:       'mplSection',
    skus:      'skusSection'
  };

  const targetId = sectionIds[section];
  if (targetId) {
    const el = document.getElementById(targetId);
    if (el) el.style.display = 'flex';
  }

  // Update title
  const titleMap = {
    inventory: 'Inventory',
    orders:    'Orders',
    mpl:       'MPL',
    skus:      'SKU Management'
  };

  const contentTitle = document.getElementById('contentTitle');
  if (contentTitle) {
    contentTitle.textContent = titleMap[section] || section.charAt(0).toUpperCase() + section.slice(1);
  }

  // Update active nav button
  const navButtons = document.querySelectorAll('.nav-button');
  navButtons.forEach(btn => {
    btn.classList.toggle('active', btn.textContent.trim().toLowerCase() === (titleMap[section] || section).toLowerCase());
  });
}

window.addEventListener('DOMContentLoaded', () => {
  // Check if URL says to open a specific section
  const params = new URLSearchParams(window.location.search);
  const section = params.get('section') || 'inventory';
  navigate(section);

  if (params.get('sku_added')) {
    setTimeout(() => showToast('SKU added successfully.', 'success'), 100);
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const passwordInput = document.getElementById('password');
  const usernameInput = document.getElementById('username');

  if (passwordInput) {
    passwordInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') login();
    });
  }

  if (usernameInput) {
    usernameInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') login();
    });
  }
});