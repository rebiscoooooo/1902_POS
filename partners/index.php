<?php
// partners/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

$stmt = $pdo->query("SELECT id, name, username, email, commission_rate, balance 
                     FROM users 
                     WHERE role = 'PARTNER' 
                     ORDER BY name ASC");
$partners = $stmt->fetchAll();

// Get total commission per partner
foreach ($partners as &$p) {
    // Total Earned
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ?");
    $stmt->execute([$p['id']]);
    $p['total_commission'] = $stmt->fetchColumn();
    
    // Credited (Unwithdrawn from commissions perspective)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM user_commissions WHERE user_id = ? AND status = 'PENDING'");
    $stmt->execute([$p['id']]);
    $p['credited_commission'] = $stmt->fetchColumn();
    
    // Total Withdrawn
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM capital_transactions WHERE type = 'CASH_OUT' AND purpose = 'Commission Withdrawal' AND description = ?");
    $stmt->execute(["Withdrawal by " . $p['name']]);
    $p['total_withdrawn'] = $stmt->fetchColumn();
}
unset($p);


<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Partner Management</h2>
            <p class="text-gray-500 text-sm">Manage business partners, investments, and commissions.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Partner</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4 text-right">Available Balance</th>
                        <th class="px-6 py-4 text-center">Comm. Rate</th>
                        <th class="px-6 py-4 text-right">Total Earned</th>
                        <th class="px-6 py-4 text-right">Total Withdrawn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($partners)): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No partners found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($partners as $p): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900"><?= htmlspecialchars($p['name']) ?></p>
                                <p class="text-xs text-indigo-600">User: <?= htmlspecialchars($p['username']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($p['email'] ?? 'No email') ?></p>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">₱<?= number_format($p['balance'], 2) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 rounded-full">
                                    <?= number_format($p['commission_rate'], 2) ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-indigo-600 font-bold">₱<?= number_format($p['total_commission'], 2) ?></td>
                            <td class="px-6 py-4 text-right font-bold text-orange-600">₱<?= number_format($p['total_withdrawn'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
