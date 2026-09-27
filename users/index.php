<?php
// users/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC");
$users = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
            <p class="text-gray-500 text-sm">Manage access and roles for all staff.</p>
        </div>
        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
        <div class="mt-4 md:mt-0">
            <button onclick="openUserModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add New User
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Username / Email</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Balance</th>
                        <th class="px-6 py-4">Last Login</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs mr-3">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($u['name']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($u['username']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                                $roleColors = [
                                    'ADMIN' => 'bg-purple-100 text-purple-700',
                                    'PARTNER' => 'bg-blue-100 text-blue-700',
                                    'STAFF' => 'bg-gray-100 text-gray-700'
                                ];
                                $color = $roleColors[$u['role']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold <?= $color ?> rounded-full">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($u['status'] === 'ACTIVE'): ?>
                                <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-green-100 text-green-700 rounded-full">Active</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-semibold bg-red-100 text-red-700 rounded-full">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right font-medium">
                            ₱<?= number_format($u['balance'] ?? 0, 2) ?><br>
                            <span class="text-xs text-gray-500"><?= number_format($u['commission_rate'] ?? 0, 2) ?>% Comm.</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            <?= $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never' ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                                    <?php if ($u['id'] !== $_SESSION['user_id'] && !isSwitchedUser()): ?>
                                    <form action="switch.php" method="POST" class="inline">
                                        <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="p-1.5 bg-amber-50 text-amber-600 rounded hover:bg-amber-100 transition flex items-center text-xs px-2 font-medium" title="Switch to this user">
                                            <i data-lucide="eye" class="w-3 h-3 mr-1"></i> View As
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($u['balance'] > 0): ?>
                                    <button onclick="withdrawBalance(<?= $u['id'] ?>, <?= $u['balance'] ?>)" class="p-1.5 bg-green-50 text-green-600 rounded hover:bg-green-100 transition flex items-center text-xs px-2 font-medium" title="Withdraw Balance">
                                        <i data-lucide="banknote" class="w-3 h-3 mr-1"></i> Withdraw
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="openUserModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-1.5 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition" title="Edit User">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?= $u['id'] ?>)" class="p-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 transition" title="Delete User">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">View Only</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeUserModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-900" id="userModalTitle">Add New User</h3>
                <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="userName" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" id="userUsername" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="userEmail" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select id="userRole" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="STAFF">Staff</option>
                                <option value="PARTNER">Partner</option>
                                <option value="ADMIN">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="userStatus" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="ACTIVE">Active</option>
                                <option value="INACTIVE">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" id="userCommissionRate" name="commission_rate" value="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-xs text-gray-400 font-normal" id="passwordHint">(Leave blank to keep current password)</span></label>
                        <input type="password" id="userPassword" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                    <button type="button" onclick="closeUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" id="btnSaveUser" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition flex items-center">
                        <span>Save User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/1902_pos/assets/js/users.js"></script>
<?php require_once '../includes/footer.php'; ?>
