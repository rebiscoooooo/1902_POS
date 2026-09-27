// assets/js/pos.js
let cart = [];
let discount = 0;
let products = [];

document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadProducts();

    // Event listeners
    document.getElementById('posSearch').addEventListener('input', debounce(loadProducts, 300));
    document.getElementById('posCategory').addEventListener('change', loadProducts);
    document.getElementById('paymentAmount').addEventListener('input', calculateChange);

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('posSearch').focus();
        } else if (e.key === 'F4') {
            e.preventDefault();
            clearCart();
        } else if (e.key === 'F8') {
            e.preventDefault();
            if(cart.length > 0 && document.getElementById('checkoutModal').classList.contains('hidden')) {
                openCheckoutModal();
            } else if (!document.getElementById('checkoutModal').classList.contains('hidden') && !document.getElementById('btnConfirmPayment').disabled) {
                processCheckout();
            }
        } else if (e.key === 'Escape') {
            closeCheckoutModal();
        }
    });
});

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

async function loadCategories() {
    try {
        const res = await fetch('/1902_pos/ajax/inventory.php?action=get_categories');
        const data = await res.json();
        if (data.success) {
            const select = document.getElementById('posCategory');
            data.data.forEach(c => select.add(new Option(c.name, c.id)));
        }
    } catch (e) {
        console.error('Error loading categories', e);
    }
}

async function loadProducts() {
    const search = document.getElementById('posSearch').value;
    const category = document.getElementById('posCategory').value;
    const grid = document.getElementById('productGrid');
    
    grid.innerHTML = `<div class="col-span-full text-center text-gray-400 py-12 flex flex-col items-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mb-2"></i><span>Loading...</span></div>`;
    lucide.createIcons();

    try {
        const res = await fetch(`/1902_pos/ajax/pos.php?action=get_products&search=${encodeURIComponent(search)}&category=${category}`);
        const data = await res.json();
        
        if (data.success) {
            products = data.data;
            renderProducts();
        }
    } catch (e) {
        console.error(e);
        grid.innerHTML = `<div class="col-span-full text-center text-red-500 py-12">Failed to load products.</div>`;
    }
}

function renderProducts() {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = '';

    if (products.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center text-gray-400 py-12 flex flex-col items-center"><i data-lucide="package-x" class="w-12 h-12 mb-3 opacity-30"></i><p>No products found</p></div>`;
        lucide.createIcons();
        return;
    }

    products.forEach(p => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl p-3 border border-gray-100 shadow-sm hover:shadow-md transition cursor-pointer flex flex-col relative h-36 select-none';
        card.onclick = () => addToCart(p);
        
        const price = parseFloat(p.selling_price).toFixed(2);
        
        card.innerHTML = `
            <div class="flex-1">
                <p class="font-bold text-gray-800 leading-tight mb-1 line-clamp-2 text-sm">${escapeHtml(p.name)}</p>
                <p class="text-xs text-gray-400 truncate">${escapeHtml(p.sku)}</p>
            </div>
            <div class="mt-auto flex justify-between items-end">
                <p class="font-bold text-indigo-600">₱${price}</p>
                <span class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${p.stock_quantity} left</span>
            </div>
        `;
        grid.appendChild(card);
    });
    
    lucide.createIcons();
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        if (existing.quantity < product.stock_quantity) {
            existing.quantity++;
        } else {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Insufficient stock', showConfirmButton: false, timer: 1500 });
            return;
        }
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    
    renderCart();
}

function updateQuantity(id, change) {
    const item = cart.find(i => i.id === id);
    if (item) {
        if (change > 0 && item.quantity >= item.stock_quantity) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Max stock reached', showConfirmButton: false, timer: 1500 });
            return;
        }
        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        renderCart();
    }
}

function clearCart() {
    if (cart.length === 0) return;
    Swal.fire({
        title: 'Clear Cart?',
        text: "Remove all items from current sale?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, clear it'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = [];
            discount = 0;
            renderCart();
        }
    });
}

async function promptDiscount() {
    const { value: amount } = await Swal.fire({
        title: 'Apply Discount',
        input: 'number',
        inputLabel: 'Amount (₱)',
        inputValue: discount || '',
        showCancelButton: true,
        inputValidator: (value) => {
            if (value < 0) return 'Discount cannot be negative';
            if (value > getSubtotal()) return 'Discount cannot exceed subtotal';
        }
    });

    if (amount !== undefined) {
        discount = parseFloat(amount || 0);
        renderCart();
    }
}

function getSubtotal() {
    return cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
}

function renderCart() {
    const container = document.getElementById('cartList');
    const emptyState = document.getElementById('emptyCartState');
    const btnCheckout = document.getElementById('btnCheckout');
    
    const subtotal = getSubtotal();
    const total = Math.max(0, subtotal - discount);

    document.getElementById('cartSubtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('cartDiscount').textContent = `-₱${discount.toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `₱${total.toFixed(2)}`;

    if (cart.length === 0) {
        emptyState.classList.remove('hidden');
        container.classList.add('hidden');
        btnCheckout.disabled = true;
        return;
    }

    emptyState.classList.add('hidden');
    container.classList.remove('hidden');
    btnCheckout.disabled = false;
    
    container.innerHTML = '';
    
    cart.forEach(item => {
        const itemTotal = item.quantity * item.selling_price;
        const div = document.createElement('div');
        div.className = 'bg-white border border-gray-100 rounded-lg p-2.5 flex items-center shadow-sm';
        div.innerHTML = `
            <div class="flex-1 pr-2">
                <p class="text-sm font-semibold text-gray-800 line-clamp-1">${escapeHtml(item.name)}</p>
                <p class="text-xs text-gray-500 font-medium">₱${parseFloat(item.selling_price).toFixed(2)}</p>
            </div>
            <div class="flex items-center bg-gray-50 rounded-md border border-gray-200">
                <button onclick="updateQuantity(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-l-md transition"><i data-lucide="minus" class="w-3 h-3"></i></button>
                <span class="w-8 text-center text-xs font-bold text-gray-900">${item.quantity}</span>
                <button onclick="updateQuantity(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-r-md transition"><i data-lucide="plus" class="w-3 h-3"></i></button>
            </div>
            <div class="w-16 text-right pl-2">
                <p class="text-sm font-bold text-indigo-700">₱${itemTotal.toFixed(2)}</p>
            </div>
        `;
        container.appendChild(div);
    });
    
    lucide.createIcons();
}

function openCheckoutModal() {
    const total = Math.max(0, getSubtotal() - discount);
    document.getElementById('checkoutAmountDue').textContent = `₱${total.toFixed(2)}`;
    document.getElementById('paymentAmount').value = '';
    document.getElementById('checkoutChange').textContent = `₱0.00`;
    document.getElementById('btnConfirmPayment').disabled = true;
    document.getElementById('checkoutModal').classList.remove('hidden');
    
    // Auto focus payment
    setTimeout(() => { document.getElementById('paymentAmount').focus(); }, 100);
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
}

function setPaymentAmount(amount) {
    const input = document.getElementById('paymentAmount');
    if (amount === 'exact') {
        const total = Math.max(0, getSubtotal() - discount);
        input.value = total.toFixed(2);
    } else {
        input.value = amount.toFixed(2);
    }
    calculateChange();
    input.focus();
}

function calculateChange() {
    const total = Math.max(0, getSubtotal() - discount);
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const changeStr = document.getElementById('checkoutChange');
    const btn = document.getElementById('btnConfirmPayment');
    
    if (payment >= total) {
        const change = payment - total;
        changeStr.textContent = `₱${change.toFixed(2)}`;
        changeStr.classList.remove('text-red-500');
        btn.disabled = false;
    } else {
        changeStr.textContent = `Insufficient`;
        changeStr.classList.add('text-red-500');
        btn.disabled = true;
    }
}

async function processCheckout() {
    const total = Math.max(0, getSubtotal() - discount);
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const method = document.querySelector('input[name="paymentMethod"]:checked').value;
    const cashierName = document.getElementById('checkoutCashier').value;
    
    if (payment < total) return;
    
    const btn = document.getElementById('btnConfirmPayment');
    const originalText = btn.innerHTML;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
    lucide.createIcons();
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'checkout');
    formData.append('cart', JSON.stringify(cart));
    formData.append('subtotal', getSubtotal());
    formData.append('discount', discount);
    formData.append('payment', payment);
    formData.append('payment_method', method);
    formData.append('cashier_name', cashierName);

    try {
        const res = await fetch('/1902_pos/ajax/pos.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            closeCheckoutModal();
            cart = [];
            discount = 0;
            renderCart();
            loadProducts(); // refresh stock
            
            Swal.fire({
                title: 'Payment Successful',
                text: `Change: ₱${(payment - total).toFixed(2)}`,
                icon: 'success',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Print Receipt'
            }).then(() => {
                // Open receipt in iframe and print silently
                const iframe = document.getElementById('receiptFrame');
                iframe.src = `/1902_pos/pos/receipt.php?id=${data.data.sale_id}&print=1`;
            });
        } else {
            Swal.fire('Error', data.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (e) {
        Swal.fire('Error', 'Network error occurred.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function escapeHtml(unsafe) {
    if(!unsafe) return '';
    return (unsafe+'').replace(/[&<"'>]/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
