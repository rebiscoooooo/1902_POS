<?php
// inventory/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 space-y-4 md:space-y-0 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inventory Management</h2>
            <p class="text-gray-500 text-sm">Manage products, pricing, and stock levels.</p>
        </div>
        
        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
        <div class="flex items-center space-x-3">
            <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Product
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 shrink-0 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Search by name or SKU..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">
        </div>
        <div class="w-full md:w-48">
            <select id="categoryFilter" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">
                <option value="">All Categories</option>
                <!-- Filled via JS -->
            </select>
        </div>
        <div class="w-full md:w-48">
            <select id="statusFilter" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">
                <option value="">All Statuses</option>
                <option value="ACTIVE">Active</option>
                <option value="INACTIVE">Inactive</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>
        </div>
    </div>

    <!-- Table Container (Scrollable) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">SKU / Product</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4 text-right">Cost</th>
                        <th class="px-6 py-4 text-right">Price</th>
                        <th class="px-6 py-4 text-center">Stock</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody" class="divide-y divide-gray-100">
                    <!-- Skeleton Loader -->
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">Loading inventory...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Simple Footer/Pagination summary -->
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500 shrink-0">
            <span id="recordCount">Showing 0 products</span>
        </div>
    </div>

</div>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    
    <!-- Modal Dialog -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl w-full flex flex-col max-h-[90vh]">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center shrink-0">
                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modalTitle">Add Product</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="px-6 py-4 overflow-y-auto flex-1">
                <form id="productForm">
                    <input type="hidden" id="productId" name="id">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU / Code *</label>
                            <input type="text" id="productSku" name="sku" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                            <input type="text" id="productName" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="productCategory" name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">Select Category</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price (₱)</label>
                            <input type="number" step="0.01" min="0" id="productCost" name="cost_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (₱) *</label>
                            <input type="number" step="0.01" min="0" id="productPrice" name="selling_price" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Stock *</label>
                            <input type="number" id="productStock" name="stock_quantity" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level</label>
                            <input type="number" id="productReorder" name="reorder_level" value="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="productStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end space-x-3 shrink-0">
                <button type="button" onclick="closeModal()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 transition">Cancel</button>
                <button type="button" onclick="saveProduct()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Product
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let products = [];
    const userRole = '<?= $_SESSION['role'] ?>';
    
    document.addEventListener('DOMContentLoaded', () => {
        loadCategories();
        loadInventory();
        
        // Setup filter listeners
        document.getElementById('searchInput').addEventListener('input', debounce(loadInventory, 300));
        document.getElementById('categoryFilter').addEventListener('change', loadInventory);
        document.getElementById('statusFilter').addEventListener('change', loadInventory);
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async function loadCategories() {
        try {
            const res = await fetch('/1902_pos/ajax/inventory.php?action=get_categories');
            const data = await res.json();
            if (data.success) {
                const filter = document.getElementById('categoryFilter');
                const formSelect = document.getElementById('productCategory');
                
                data.data.forEach(c => {
                    filter.add(new Option(c.name, c.id));
                    formSelect.add(new Option(c.name, c.id));
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function loadInventory() {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        const tbody = document.getElementById('inventoryTableBody');
        
        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-gray-400"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto mb-2"></i>Loading...</td></tr>`;
        lucide.createIcons();

        try {
            const res = await fetch(`/1902_pos/ajax/inventory.php?action=list&search=${encodeURIComponent(search)}&category=${category}&status=${status}`);
            const data = await res.json();
            
            if (data.success) {
                products = data.data;
                renderTable();
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Failed to load inventory.</td></tr>`;
        }
    }

    function renderTable() {
        const tbody = document.getElementById('inventoryTableBody');
        document.getElementById('recordCount').innerText = `Showing ${products.length} products`;
        
        if (products.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">
                <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                <p>No products found.</p>
            </td></tr>`;
            lucide.createIcons();
            return;
        }

        tbody.innerHTML = '';
        
        products.forEach(p => {
            const costFormat = parseFloat(p.cost_price).toFixed(2);
            const priceFormat = parseFloat(p.selling_price).toFixed(2);
            const isLowStock = parseInt(p.stock_quantity) <= parseInt(p.reorder_level);
            
            let statusHtml = '';
            if (p.status === 'INACTIVE') {
                statusHtml = `<span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-gray-100 text-gray-600 rounded-full">Inactive</span>`;
            } else if (p.stock_quantity == 0) {
                statusHtml = `<span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-red-100 text-red-700 rounded-full">Out of Stock</span>`;
            } else if (isLowStock) {
                statusHtml = `<span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-orange-100 text-orange-700 rounded-full">Low Stock</span>`;
            } else {
                statusHtml = `<span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-green-100 text-green-700 rounded-full">Active</span>`;
            }

            let actionsHtml = '';
            if (userRole === 'ADMIN') {
                actionsHtml = `
                    <button onclick="editProduct(${p.id})" class="p-1.5 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition" title="Edit">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteProduct(${p.id})" class="p-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 transition" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors group';
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <p class="font-bold text-gray-900">${escapeHtml(p.name)}</p>
                    <p class="text-xs text-gray-500">${escapeHtml(p.sku)}</p>
                </td>
                <td class="px-6 py-4">${escapeHtml(p.category_name || '-')}</td>
                <td class="px-6 py-4 text-right">₱${costFormat}</td>
                <td class="px-6 py-4 text-right font-medium text-gray-900">₱${priceFormat}</td>
                <td class="px-6 py-4 text-center">
                    <span class="${isLowStock && p.status === 'ACTIVE' ? 'text-orange-600 font-bold' : ''}">${p.stock_quantity}</span>
                </td>
                <td class="px-6 py-4 text-center">${statusHtml}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        ${actionsHtml}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        lucide.createIcons();
    }

    function escapeHtml(unsafe) {
        if(!unsafe) return '';
        return (unsafe+'').replace(/[&<"'>]/g, function (m) {
            return {
                '&': '&amp;', '<': '&lt;', '>': '&gt;',
                '"': '&quot;', "'": '&#039;'
            }[m];
        });
    }

    const modal = document.getElementById('productModal');
    
    function openModal(id = null) {
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        
        if (id) {
            document.getElementById('modalTitle').innerText = 'Edit Product';
            const product = products.find(p => p.id == id);
            if (product) {
                document.getElementById('productId').value = product.id;
                document.getElementById('productSku').value = product.sku;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCategory').value = product.category_id || '';
                document.getElementById('productCost').value = product.cost_price;
                document.getElementById('productPrice').value = product.selling_price;
                document.getElementById('productStock').value = product.stock_quantity;
                document.getElementById('productReorder').value = product.reorder_level;
                document.getElementById('productStatus').value = product.status;
            }
        } else {
            document.getElementById('modalTitle').innerText = 'Add Product';
        }
        
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    async function saveProduct() {
        const form = document.getElementById('productForm');
        if(!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'save');

        try {
            const res = await fetch('/1902_pos/ajax/inventory.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal();
                loadInventory();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'A network error occurred.', 'error');
        }
    }

    function editProduct(id) {
        openModal(id);
    }

    function deleteProduct(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                try {
                    const res = await fetch('/1902_pos/ajax/inventory.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success');
                        loadInventory();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'A network error occurred.', 'error');
                }
            }
        })
    }
</script>

<?php require_once '../includes/footer.php'; ?>
