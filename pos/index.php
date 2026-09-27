<?php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'STAFF']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';
$cashiers = $pdo->query("SELECT id, name FROM users WHERE status = 'ACTIVE'")->fetchAll();
?>
<div class="min-h-[calc(100vh-4rem)] md:h-[calc(100vh-4rem)] flex flex-col md:flex-row bg-transparent -m-4 lg:-m-8">
    <div class="flex-1 flex flex-col min-h-[50vh] md:h-full glass-panel lg:rounded-xl lg:mr-4 overflow-hidden mb-4 md:mb-0">
        <div class="p-4 border-b border-white/50 bg-white/40 flex gap-3 shrink-0">
            <div class="flex-1 relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-gray-500"></i>
                <input type="text" id="posSearch" placeholder="Search product (F2)..." class="w-full pl-10 pr-4 py-2 bg-white/50 border border-white/60 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:bg-white outline-none text-sm font-medium transition-all backdrop-blur-sm shadow-sm">
            </div>
            <select id="posCategory" class="w-40 px-3 py-2 bg-white/50 border border-white/60 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:bg-white outline-none text-sm text-gray-700 backdrop-blur-sm shadow-sm">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50/20">
            <div id="productGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <div class="col-span-full text-center text-gray-500 py-12 flex flex-col items-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mb-2"></i><span>Loading products...</span></div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-96 flex flex-col glass-panel lg:rounded-xl overflow-hidden shrink-0 min-h-[50vh] md:h-full">
        <div class="p-4 border-b border-white/50 bg-indigo-50/40 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-gray-800 flex items-center"><i data-lucide="shopping-cart" class="w-5 h-5 mr-2 text-indigo-600"></i> Current Sale</h3>
            <button onclick="clearCart()" class="text-sm text-red-500 hover:text-red-700 font-medium">Clear (F4)</button>
        </div>
        <div class="flex-1 overflow-y-auto p-2" id="cartItemsContainer">
            <div id="emptyCartState" class="h-full flex flex-col items-center justify-center text-gray-400">
                <i data-lucide="shopping-bag" class="w-12 h-12 mb-3 opacity-30"></i><p class="text-sm font-medium">Cart is empty</p>
            </div>
            <div id="cartList" class="space-y-2 hidden"></div>
        </div>
        <div class="border-t border-white/50 bg-white/40 p-4 shrink-0 shadow-lg z-10 backdrop-blur-md">
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span id="cartSubtotal" class="font-medium"> 0.00</span></div>
                <div class="flex justify-between items-center text-gray-600 group">
                    <span class="flex items-center cursor-pointer border-b border-dashed border-gray-400" onclick="promptDiscount()">Discount <i data-lucide="edit-2" class="w-3 h-3 ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i></span>
                    <span id="cartDiscount" class="font-medium text-red-500">- 0.00</span>
                </div>
                <div class="pt-2 border-t border-white/50 flex justify-between items-center">
                    <span class="font-bold text-gray-900 text-lg">Total</span>
                    <span id="cartTotal" class="font-bold text-indigo-700 text-2xl"> 0.00</span>
                </div>
            </div>
            <button onclick="openCheckoutModal()" id="btnCheckout" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex justify-between items-center" disabled>
                <span>Checkout (F8)</span><i data-lucide="arrow-right-circle" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
</div>
<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeCheckoutModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white/90 backdrop-blur-xl rounded-2xl text-left overflow-hidden shadow-2xl border border-white/50 transform transition-all sm:my-8 sm:max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200/50 flex justify-between items-center bg-white/50">
                <h3 class="text-lg font-bold text-gray-900">Complete Payment</h3>
                <button onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="px-6 py-5">
                <div class="bg-indigo-50/70 rounded-xl p-4 flex justify-between items-center mb-6 border border-indigo-100 shadow-inner">
                    <span class="text-indigo-800 font-medium">Amount Due:</span>
                    <span id="checkoutAmountDue" class="text-3xl font-bold text-indigo-700 tracking-tight">₱0.00</span>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Cashier / Sold By</label>
                    <input type="text" id="checkoutCashier" list="cashierNames" class="w-full px-3 py-2 border border-gray-300 rounded-lg outline-none mb-4" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'System') ?>">
                    <datalist id="cashierNames">
                        <?php foreach($cashiers as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>">
                        <?php endforeach; ?>
                    </datalist>

                    <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="paymentMethod" value="CASH" class="peer sr-only" checked>
                            <div class="p-3 border-2 border-transparent bg-white shadow-sm rounded-xl text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 hover:bg-gray-50 transition-all">
                                <i data-lucide="banknote" class="w-6 h-6 mx-auto mb-1"></i>
                                <span class="text-sm font-bold">Cash</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="paymentMethod" value="GCASH" class="peer sr-only">
                            <div class="p-3 border-2 border-transparent bg-white shadow-sm rounded-xl text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 hover:bg-gray-50 transition-all">
                                <i data-lucide="smartphone" class="w-6 h-6 mx-auto mb-1"></i>
                                <span class="text-sm font-bold">GCash</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-end mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Amount Received (₱)</label>
                    </div>
                    <input type="number" id="paymentAmount" step="0.01" class="w-full px-4 py-3 text-2xl font-bold bg-white/80 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-right shadow-inner transition-all placeholder-gray-300" placeholder="0.00">
                    
                    <!-- Quick Cash Buttons -->
                    <div class="grid grid-cols-4 gap-2 mt-3" id="quickCashButtons">
                        <button type="button" onclick="setPaymentAmount('exact')" class="py-2 px-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors shadow-sm">Exact</button>
                        <button type="button" onclick="setPaymentAmount(100)" class="py-2 px-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors shadow-sm">₱100</button>
                        <button type="button" onclick="setPaymentAmount(500)" class="py-2 px-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors shadow-sm">₱500</button>
                        <button type="button" onclick="setPaymentAmount(1000)" class="py-2 px-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors shadow-sm">₱1000</button>
                    </div>
                </div>

                <div class="flex justify-between items-center bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <span class="text-gray-600 font-bold uppercase text-xs tracking-wider">Change Due:</span>
                    <span id="checkoutChange" class="font-black text-gray-900 text-2xl">₱0.00</span>
                </div>
            </div>
            
            <div class="px-6 py-4 flex gap-3 border-t border-gray-200/50 bg-white/50">
                <button onclick="closeCheckoutModal()" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-3.5 rounded-xl font-bold hover:bg-gray-50 hover:shadow transition">Cancel</button>
                <button id="btnConfirmPayment" onclick="processCheckout()" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-3.5 rounded-xl font-bold transition shadow-lg shadow-green-200/50 disabled:opacity-50 disabled:shadow-none flex justify-center items-center">
                    <span>Confirm Sale</span>
                </button>
            </div>
        </div>
    </div>
</div>
<iframe id="receiptFrame" class="hidden"></iframe>
<script src="/1902_pos/assets/js/pos.js"></script>
<?php require_once '../includes/footer.php'; ?>