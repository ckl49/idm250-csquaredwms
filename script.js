
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
  // NAV BUTTON
  const navButtons = document.querySelectorAll('.nav-button');
  navButtons.forEach(btn => btn.classList.remove('active'));
  // document.getElementById('addButton').href = `create-form.php?table=${table}`;
  

  navButtons.forEach(btn => {
    if (btn.textContent.toLowerCase() === section) {
      btn.classList.add('active');
    }
  });
  
  // UPDATE TITLE
  const contentTitle = document.getElementById('contentTitle');
  contentTitle.textContent = section.charAt(0).toUpperCase() + section.slice(1);
  
  const ordersTable = document.getElementById('ordersSection');
  const inventoryTable = document.getElementById('inventorySection');
  const placeholderContent = document.getElementById('placeholderContent');
  const mplTable = document.getElementById('mplSection');
  
  if (section === 'orders') {
    placeholderContent.style.display = 'none';
    ordersTable.style.display = 'flex';
    inventoryTable.style.display = 'none';
    mplTable.style.display = 'none';

  } else if (section === 'inventory') {
    ordersTable.style.display = 'none';
    placeholderContent.style.display = 'none'; // fix this
    inventoryTable.style.display = 'flex';
    mplTable.style.display = 'none';
    
  } else if (section === 'mpl') {
    ordersTable.style.display = 'none';
    placeholderContent.style.display = 'none';
    inventoryTable.style.display = 'none';
    mplTable.style.display = 'flex';
  }
}

window.addEventListener('DOMContentLoaded', () => {
  navigate('inventory'); // Default to inventory page on load
})


document.addEventListener('DOMContentLoaded', function() {

  const passwordInput = document.getElementById('password');
  const usernameInput = document.getElementById('username');
  
  if (passwordInput) {
    passwordInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        login();
      }
    });
  }
  
  if (usernameInput) {
    usernameInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        login();
      }
    });
  }
});


