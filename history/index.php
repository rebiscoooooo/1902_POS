<?php
// history/index.php
require_once '../includes/auth.php';
requireRole(['ADMIN', 'PARTNER']);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/db.php';

$filter_user = $_GET['user'] ?? '';
$filter_type = $_GET['type'] ?? '';

$query = "SELECT l.*, u.name as user_name, u.role as user_role 
          FROM activity_logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filter_user !== '') {
    $query .= " AND l.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_type !== '') {
    $query .= " AND l.activity_type = ?";
    $params[] = $filter_type;
}

$query .= " ORDER BY l.created_at DESC LIMIT 200"; // limit to recent for performance

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get filter options
$usersStmt = $pdo->query("SELECT id, name FROM users ORDER BY name ASC");
$users = $usersStmt->fetchAll();

$typesStmt = $pdo->query("SELECT DISTINCT activity_type FROM activity_logs ORDER BY activity_type ASC");
$types = $typesStmt->fetchAll();
?>

<div class="max-w-7xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">System History Log</h2>
            <p class="text-gray-500 text-sm">Audit trail of all system activities.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 shrink-0 flex flex-col md:flex-row gap-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4 w-full">
            <div class="w-full md:w-64">
                <select name="user" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none text-sm bg-gray-50 text-gray-700" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-full md:w-64">
                <select name="type" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none text-sm bg-gray-50 text-gray-700" onchange="this.form.submit()">
                    <option value="">All Activity Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t['activity_type']) ?>" <?= $filter_type == $t['activity_type'] ? 'selected' : '' ?>><?= htmlspecialchars($t['activity_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="index.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-lg font-medium transition text-sm flex items-center justify-center">
                Clear Filters
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Activity</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No activity logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500"><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                <?php if ($log['user_role']): ?>
                                <span class="ml-1 text-[10px] text-gray-400 border border-gray-200 px-1 rounded"><?= $log['user_role'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-gray-100 text-gray-700 rounded-md whitespace-nowrap">
                                    <?= htmlspecialchars($log['activity_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-700"><?= htmlspecialchars($log['description']) ?></td>
                            <td class="px-6 py-3 text-right text-xs text-gray-400 font-mono"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
