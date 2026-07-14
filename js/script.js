// search form toggle
searchForm = document.querySelector('.search-form');

document.querySelector('#search-btn').onclick = () =>{
  searchForm.classList.toggle('active');
}

// login form toggle
let loginForm = document.querySelector('.login-form-container');

document.querySelector('#login-btn').onclick = () =>{
  loginForm.classList.toggle('active');
}

document.querySelector('#close-login-btn').onclick = () =>{
  loginForm.classList.remove('active');
}

// login form AJAX submission
const loginFormEl = document.getElementById('login-form');
if(loginFormEl){
  loginFormEl.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const errorDiv = document.getElementById('login-error');
    const submitBtn = loginFormEl.querySelector('input[type="submit"]');

    errorDiv.style.display = 'none';
    submitBtn.value = 'Signing in...';
    submitBtn.disabled = true;

    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    fetch('auth/api_login.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if(data.success){
        loginForm.classList.remove('active');
        showToast('Login successful! Redirecting...', 'success');
        setTimeout(() => {
          window.location.href = 'index.php';
        }, 1000);
      } else {
        errorDiv.textContent = data.message;
        errorDiv.style.display = 'block';
        submitBtn.value = 'sign in';
        submitBtn.disabled = false;
      }
    })
    .catch(() => {
      errorDiv.textContent = 'Connection error. Please try again.';
      errorDiv.style.display = 'block';
      submitBtn.value = 'sign in';
      submitBtn.disabled = false;
    });
  });
}

// sticky nav bar
window.onscroll = () =>{
  searchForm.classList.remove('active');

  if(window.scrollY > 80){
    document.querySelector('.header .header-2').classList.add('active');
  }else{
    document.querySelector('.header .header-2').classList.remove('active');
  }
}

window.onload = () =>{
  if(window.scrollY > 80){
    document.querySelector('.header .header-2').classList.add('active');
  }else{
    document.querySelector('.header .header-2').classList.remove('active');
  }

  fadeOut();
  loadCartFromStorage();
  loadWishlistFromStorage();
  updateCartCount();
  animateCounters();
  loadCategoryBooks();
}

function loader(){
  document.querySelector('.loader-container').classList.add('active');
}

function fadeOut(){
  setTimeout(loader, 4000);
}

// ==================== CART FUNCTIONALITY ====================

let cart = JSON.parse(localStorage.getItem('cart')) || [];
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
let booksData = [];

function saveCartToStorage(){
  localStorage.setItem('cart', JSON.stringify(cart));
}

function saveWishlistToStorage(){
  localStorage.setItem('wishlist', JSON.stringify(wishlist));
}

function updateCartCount(){
  const cartCount = document.getElementById('cart-count');
  if(cartCount){
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    cartCount.style.display = totalItems > 0 ? 'block' : 'none';
  }
}

function updateCartTotal(){
  const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
  const cartTotal = document.getElementById('cart-total');
  if(cartTotal){
    cartTotal.textContent = '$' + total.toFixed(2);
  }
}

function renderCart(){
  const cartItems = document.getElementById('cart-items');
  const cartTotal = document.getElementById('cart-total');

  if(!cartItems) return;

  if(cart.length === 0){
    cartItems.innerHTML = `
      <div class="empty-message">
        <i class="fas fa-shopping-cart"></i>
        <p>Your cart is empty</p>
      </div>
    `;
    if(cartTotal) cartTotal.textContent = '$0.00';
    return;
  }

  cartItems.innerHTML = cart.map((item, index) => `
    <div class="cart-item">
      <img src="${item.image || 'image/book-1.png'}" alt="${item.name}" class="cart-item-image">
      <div class="cart-item-info">
        <span class="cart-item-name">${item.name}</span>
        <span class="cart-item-author">${item.author || ''}</span>
        <span class="cart-item-price">$${item.price}</span>
      </div>
      <div class="cart-item-quantity">
        <button class="qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
        <span>${item.quantity}</span>
        <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
      </div>
      <i class="fas fa-trash cart-item-remove" onclick="removeFromCart(${index})"></i>
    </div>
  `).join('');

  updateCartTotal();
}

function addToCart(bookId, name, price, image, author){
  const existingItem = cart.find(item => item.id === bookId);

  if(existingItem){
    existingItem.quantity += 1;
  } else {
    cart.push({
      id: bookId,
      name: name,
      price: price,
      quantity: 1,
      image: image || 'image/book-1.png',
      author: author || ''
    });
  }

  saveCartToStorage();
  updateCartCount();
  renderCart();
  showToast(`${name} added to cart!`, 'success');
}

function updateQuantity(index, change){
  cart[index].quantity += change;
  if(cart[index].quantity <= 0){
    cart.splice(index, 1);
  }
  saveCartToStorage();
  updateCartCount();
  renderCart();
}

function removeFromCart(index){
  const item = cart[index];
  cart.splice(index, 1);
  saveCartToStorage();
  updateCartCount();
  renderCart();
  showToast(`${item.name} removed from cart`, 'info');
}

function clearCart(){
  cart = [];
  saveCartToStorage();
  updateCartCount();
  renderCart();
}

function loadCartFromStorage(){
  renderCart();
}

// ==================== BOOKS DATA ====================

// Sample books data for all categories (used when PHP backend is not available)
const sampleBooks = {
  'fiction': [
    {id: 1, title: 'The Great Gatsby', author: 'F. Scott Fitzgerald', price: 12.99, image: 'image/book-1.png', category: 'Fiction'},
    {id: 2, title: 'To Kill a Mockingbird', author: 'Harper Lee', price: 14.99, image: 'image/book-2.png', category: 'Fiction'},
    {id: 3, title: '1984', author: 'George Orwell', price: 11.99, image: 'image/book-3.png', category: 'Fiction'},
    {id: 4, title: 'Pride and Prejudice', author: 'Jane Austen', price: 10.99, image: 'image/book-4.png', category: 'Fiction'},
    {id: 5, title: 'The Catcher in the Rye', author: 'J.D. Salinger', price: 13.99, image: 'image/book-5.png', category: 'Fiction'},
    {id: 6, title: 'Brave New World', author: 'Aldous Huxley', price: 12.99, image: 'image/book-6.png', category: 'Fiction'},
    {id: 7, title: 'The Hobbit', author: 'J.R.R. Tolkien', price: 15.99, image: 'image/book-7.png', category: 'Fiction'},
    {id: 8, title: "Harry Potter and the Sorcerer's Stone", author: 'J.K. Rowling', price: 16.99, image: 'image/book-8.png', category: 'Fiction'},
  ],
  'non-fiction': [
    {id: 9, title: 'Sapiens', author: 'Yuval Noah Harari', price: 18.99, image: 'image/book-4.png', category: 'Non-Fiction'},
    {id: 10, title: 'Educated', author: 'Tara Westover', price: 17.99, image: 'image/book-5.png', category: 'Non-Fiction'},
    {id: 11, title: 'Becoming', author: 'Michelle Obama', price: 19.99, image: 'image/book-6.png', category: 'Non-Fiction'},
    {id: 12, title: 'Thinking, Fast and Slow', author: 'Daniel Kahneman', price: 16.99, image: 'image/book-7.png', category: 'Non-Fiction'},
    {id: 13, title: 'The Power of Habit', author: 'Charles Duhigg', price: 15.99, image: 'image/book-8.png', category: 'Non-Fiction'},
    {id: 14, title: 'Atomic Habits', author: 'James Clear', price: 16.99, image: 'image/book-9.png', category: 'Non-Fiction'},
  ],
  'science': [
    {id: 15, title: 'A Brief History of Time', author: 'Stephen Hawking', price: 13.99, image: 'image/book-1.png', category: 'Science'},
    {id: 16, title: 'The Selfish Gene', author: 'Richard Dawkins', price: 15.99, image: 'image/book-2.png', category: 'Science'},
    {id: 17, title: 'Cosmos', author: 'Carl Sagan', price: 16.99, image: 'image/book-3.png', category: 'Science'},
    {id: 18, title: 'The Gene', author: 'Siddhartha Mukherjee', price: 18.99, image: 'image/book-4.png', category: 'Science'},
    {id: 19, title: 'Silent Spring', author: 'Rachel Carson', price: 14.99, image: 'image/book-5.png', category: 'Science'},
  ],
  'technology': [
    {id: 20, title: 'Clean Code', author: 'Robert C. Martin', price: 34.99, image: 'image/book-1.png', category: 'Technology'},
    {id: 21, title: 'The Pragmatic Programmer', author: 'Andrew Hunt', price: 42.99, image: 'image/book-2.png', category: 'Technology'},
    {id: 22, title: 'Introduction to Algorithms', author: 'Thomas Cormen', price: 89.99, image: 'image/book-3.png', category: 'Technology'},
    {id: 23, title: 'Code Complete', author: 'Steve McConnell', price: 49.99, image: 'image/book-4.png', category: 'Technology'},
    {id: 24, title: 'The Mythical Man-Month', author: 'Frederick Brooks', price: 39.99, image: 'image/book-6.png', category: 'Technology'},
  ],
  'business': [
    {id: 25, title: 'Zero to One', author: 'Peter Thiel', price: 17.99, image: 'image/book-2.png', category: 'Business'},
    {id: 26, title: 'The Lean Startup', author: 'Eric Ries', price: 16.99, image: 'image/book-3.png', category: 'Business'},
    {id: 27, title: 'Good to Great', author: 'Jim Collins', price: 19.99, image: 'image/book-4.png', category: 'Business'},
    {id: 28, title: 'Rich Dad Poor Dad', author: 'Robert Kiyosaki', price: 12.99, image: 'image/book-5.png', category: 'Business'},
    {id: 29, title: 'The 7 Habits of Highly Effective People', author: 'Stephen Covey', price: 17.99, image: 'image/book-6.png', category: 'Business'},
  ],
  'biography': [
    {id: 30, title: 'Steve Jobs', author: 'Walter Isaacson', price: 19.99, image: 'image/book-4.png', category: 'Biography'},
    {id: 31, title: 'Einstein', author: 'Walter Isaacson', price: 18.99, image: 'image/book-5.png', category: 'Biography'},
    {id: 32, title: 'Benjamin Franklin', author: 'Walter Isaacson', price: 17.99, image: 'image/book-6.png', category: 'Biography'},
    {id: 33, title: 'Long Walk to Freedom', author: 'Nelson Mandela', price: 16.99, image: 'image/book-7.png', category: 'Biography'},
    {id: 34, title: 'The Diary of a Young Girl', author: 'Anne Frank', price: 11.99, image: 'image/book-8.png', category: 'Biography'},
  ],
  'children': [
    {id: 35, title: 'The Very Hungry Caterpillar', author: 'Eric Carle', price: 8.99, image: 'image/book-1.png', category: 'Children'},
    {id: 36, title: 'Where the Wild Things Are', author: 'Maurice Sendak', price: 9.99, image: 'image/book-2.png', category: 'Children'},
    {id: 37, title: "Charlotte's Web", author: 'E.B. White', price: 10.99, image: 'image/book-3.png', category: 'Children'},
    {id: 38, title: 'The Gruffalo', author: 'Julia Donaldson', price: 8.99, image: 'image/book-4.png', category: 'Children'},
    {id: 39, title: 'Matilda', author: 'Roald Dahl', price: 11.99, image: 'image/book-5.png', category: 'Children'},
  ],
  'textbooks': [
    {id: 40, title: 'Calculus: Early Transcendentals', author: 'James Stewart', price: 89.99, image: 'image/book-1.png', category: 'Textbooks'},
    {id: 41, title: 'Campbell Biology', author: 'Lisa Urry', price: 119.99, image: 'image/book-2.png', category: 'Textbooks'},
    {id: 42, title: 'Organic Chemistry', author: 'Paula Bruice', price: 109.99, image: 'image/book-3.png', category: 'Textbooks'},
    {id: 43, title: 'Principles of Economics', author: 'Gregory Mankiw', price: 99.99, image: 'image/book-4.png', category: 'Textbooks'},
    {id: 44, title: 'Psychology', author: 'David Myers', price: 89.99, image: 'image/book-5.png', category: 'Textbooks'},
  ]
};

// Function to render books for a category
function renderCategoryBooks(category, containerId){
  const container = document.getElementById(containerId);
  if(!container) return;

  const books = sampleBooks[category] || [];

  if(books.length === 0){
    container.innerHTML = '<p class="no-books">No books available in this category.</p>';
    return;
  }

  container.innerHTML = books.map(book => `
    <div class="book-card">
      <div class="book-image">
        <img src="${book.image}" alt="${book.title}">
        <div class="book-overlay">
          <button class="add-btn add-to-cart-btn" onclick="addToCart(${book.id}, '${book.title.replace(/'/g, "\\'")}', ${book.price}, '${book.image}', '${book.author.replace(/'/g, "\\'")}')">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </div>
      <div class="book-info">
        <h3 class="book-title">${book.title}</h3>
        <p class="book-author">by ${book.author}</p>
        <div class="book-price">$${book.price.toFixed(2)}</div>
        <button class="wishlist-btn-small" onclick="addToWishlist(${book.id}, '${book.title.replace(/'/g, "\\'")}', ${book.price}, '${book.image}', '${book.author.replace(/'/g, "\\'")}')">
          <i class="fas fa-heart"></i>
        </button>
      </div>
    </div>
  `).join('');
}

// Load all category books
function loadCategoryBooks(){
  renderCategoryBooks('fiction', 'fiction-books');
  renderCategoryBooks('non-fiction', 'non-fiction-books');
  renderCategoryBooks('science', 'science-books');
  renderCategoryBooks('technology', 'technology-books');
  renderCategoryBooks('business', 'business-books');
  renderCategoryBooks('biography', 'biography-books');
  renderCategoryBooks('children', 'children-books');
  renderCategoryBooks('textbooks', 'textbooks-books');
}

// cart sidebar toggle
const cartSidebar = document.getElementById('cart-sidebar');
const cartIcon = document.getElementById('cart-icon');

if(cartIcon){
  cartIcon.addEventListener('click', (e) => {
    e.preventDefault();
    cartSidebar.classList.toggle('active');
  });
}

document.getElementById('close-cart').addEventListener('click', () => {
  cartSidebar.classList.remove('active');
});

// ==================== WISHLIST FUNCTIONALITY ====================

function updateWishlistUI(){
  const wishlistItems = document.getElementById('wishlist-items');

  if(!wishlistItems) return;

  if(wishlist.length === 0){
    wishlistItems.innerHTML = `
      <div class="empty-message">
        <i class="fas fa-heart"></i>
        <p>Your wishlist is empty</p>
      </div>
    `;
    return;
  }

  wishlistItems.innerHTML = wishlist.map((item, index) => `
    <div class="wishlist-item">
      <img src="${item.image || 'image/book-1.png'}" alt="${item.name}" class="wishlist-item-image">
      <div class="wishlist-item-info">
        <span class="wishlist-item-name">${item.name}</span>
        <span class="wishlist-item-author">${item.author || ''}</span>
        <span class="wishlist-item-price">$${item.price}</span>
      </div>
      <div style="display:flex; gap:1rem;">
        <i class="fas fa-cart-plus" style="cursor:pointer; color:var(--green);" onclick="addToCartFromWishlist(${index})"></i>
        <i class="fas fa-trash wishlist-item-remove" onclick="removeFromWishlist(${index})"></i>
      </div>
    </div>
  `).join('');
}

function addToWishlist(bookId, name, price, image, author){
  if(!wishlist.some(item => item.id === bookId)){
    wishlist.push({ id: bookId, name, price: parseFloat(price), image, author });
    saveWishlistToStorage();
    updateWishlistUI();
    showToast(`${name} added to wishlist!`, 'success');
  } else {
    showToast(`${name} is already in your wishlist!`, 'info');
  }
}

function removeFromWishlist(index){
  const item = wishlist[index];
  wishlist.splice(index, 1);
  saveWishlistToStorage();
  updateWishlistUI();
  showToast(`${item.name} removed from wishlist`, 'info');
}

function addToCartFromWishlist(index){
  const item = wishlist[index];
  addToCart(item.id, item.name, item.price, item.image, item.author);
  removeFromWishlist(index);
}

function loadWishlistFromStorage(){
  updateWishlistUI();
}

// wishlist sidebar toggle
const wishlistSidebar = document.getElementById('wishlist-sidebar');
const wishlistIcon = document.getElementById('wishlist-icon');

if(wishlistIcon){
  wishlistIcon.addEventListener('click', (e) => {
    e.preventDefault();
    wishlistSidebar.classList.toggle('active');
  });
}

document.getElementById('close-wishlist').addEventListener('click', () => {
  wishlistSidebar.classList.remove('active');
});

// ==================== TOAST NOTIFICATIONS ====================

function showToast(message, type = 'info'){
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;

  let icon = '';
  switch(type){
    case 'success': icon = 'fa-check-circle'; break;
    case 'error': icon = 'fa-times-circle'; break;
    default: icon = 'fa-info-circle';
  }

  toast.innerHTML = `
    <i class="fas ${icon}"></i>
    <span class="toast-message">${message}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ==================== ADD TO CART/WISHLIST EVENT LISTENERS ====================

document.addEventListener('click', (e) => {
  // add to cart from featured/arrivals
  if(e.target.classList.contains('add-to-cart')){
    e.preventDefault();
    const name = e.target.getAttribute('data-name');
    const price = parseFloat(e.target.getAttribute('data-price'));
    addToCart(Date.now(), name, price, 'image/book-1.png', '');
  }

  // add to wishlist from featured/arrivals
  if(e.target.classList.contains('wishlist-btn')){
    e.preventDefault();
    const name = e.target.getAttribute('data-name');
    const price = parseFloat(e.target.getAttribute('data-price'));
    addToWishlist(Date.now(), name, price, 'image/book-1.png', '');
    e.target.classList.add('added');
  }
});

// ==================== NEWSLETTER FORM ====================

document.getElementById('newsletter-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const email = document.getElementById('newsletter-email').value;
  if(email){
    showToast('Thank you for subscribing!', 'success');
    document.getElementById('newsletter-email').value = '';
  }
});

// ==================== CHECKOUT ====================

document.getElementById('checkout-btn').addEventListener('click', () => {
  if(cart.length > 0){
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);

    showToast(`Processing order of ${itemCount} items for $${total.toFixed(2)}...`, 'info');

    // Simulate checkout process
    setTimeout(() => {
      cart = [];
      saveCartToStorage();
      updateCartCount();
      renderCart();
      cartSidebar.classList.remove('active');
      showToast('Order placed successfully! Thank you for shopping.', 'success');
    }, 2000);
  } else {
    showToast('Your cart is empty!', 'error');
  }
});

// ==================== SCROLL ANIMATIONS ====================

function animateOnScroll(){
  const elements = document.querySelectorAll('.icons-container .icons, .featured .box, .arrivals .box, .reviews .box, .blogs .box');

  elements.forEach(el => {
    const rect = el.getBoundingClientRect();
    const isVisible = rect.top < window.innerHeight - 100;

    if(isVisible){
      el.classList.add('animated');
    }
  });
}

window.addEventListener('scroll', animateOnScroll);

// ==================== COUNTER ANIMATION ====================

function animateCounters(){
  const counters = document.querySelectorAll('.counter');
  counters.forEach(counter => {
    const target = parseInt(counter.getAttribute('data-target'));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;

    const updateCounter = () => {
      current += step;
      if(current < target){
        counter.textContent = Math.ceil(current);
        requestAnimationFrame(updateCounter);
      } else {
        counter.textContent = target;
      }
    };

    updateCounter();
  });
}

// ==================== SWIPER INITIALIZATION ====================

var swiper = new Swiper(".books-slider", {
  loop:true,
  centeredSlides: true,
  autoplay: {
    delay: 9500,
    disableOnInteraction: false,
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

var swiper = new Swiper(".featured-slider", {
  spaceBetween: 10,
  loop:true,
  centeredSlides: true,
  autoplay: {
    delay: 9500,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    450: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    1024: {
      slidesPerView: 4,
    },
  },
});

var swiper = new Swiper(".arrivals-slider", {
  spaceBetween: 10,
  loop:true,
  centeredSlides: true,
  autoplay: {
    delay: 9500,
    disableOnInteraction: false,
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

var swiper = new Swiper(".reviews-slider", {
  spaceBetween: 10,
  grabCursor:true,
  loop:true,
  centeredSlides: true,
  autoplay: {
    delay: 9500,
    disableOnInteraction: false,
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

var swiper = new Swiper(".blogs-slider", {
  spaceBetween: 10,
  grabCursor:true,
  loop:true,
  centeredSlides: true,
  autoplay: {
    delay: 9500,
    disableOnInteraction: false,
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

// ==================== SEARCH FUNCTIONALITY ====================

const searchBox = document.getElementById('search-box');
if(searchBox){
  searchBox.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const featuredBoxes = document.querySelectorAll('.featured .swiper-slide .box');

    featuredBoxes.forEach(box => {
      const title = box.querySelector('.content h3').textContent.toLowerCase();
      if(title.includes(searchTerm)){
        box.style.display = 'block';
      } else {
        box.style.display = 'none';
      }
    });
  });
}
