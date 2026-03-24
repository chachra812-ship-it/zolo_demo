<?php
session_start();
require_once 'config.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'config.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
// 👇 ADD THIS - Handle Avail/Extend actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'avail') {
        $purchase_id = (int)$_POST['service_id'];
        $stmt = $conn->prepare("UPDATE user_purchases SET status = 'used' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $purchase_id, $userId);
        $stmt->execute();
        $_SESSION['success'] = '✅ Service marked as used!';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'extend') {
        $purchase_id = (int)$_POST['service_id'];
        $new_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt = $conn->prepare("UPDATE user_purchases SET expiration_date = ?, status = 'active' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_date, $purchase_id, $userId);
        $stmt->execute();
        $_SESSION['success'] = '✅ Service extended 30 days!';
    }
    header("Location: dashboard.php");
    exit;
}

// 👇 Add success message display vars
$successMsg = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// ✅ FIXED: Get purchased services with proper expiration logic
$purchasedServices = [];
$stmt = $conn->prepare("
    SELECT up.id, up.service_id, up.status, up.service_price as total_price, up.created_at, up.expiration_date,
           up.service_title,
           CASE 
               WHEN up.status = 'used' THEN 'used'
               WHEN up.expiration_date < NOW() THEN 'expired'
               ELSE 'active'
           END as current_status
    FROM user_purchases up 
    WHERE up.user_id = ? 
    ORDER BY up.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$purchasedServices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count services properly
$activeCount = 0;
$usedCount = 0;
$expiredCount = 0;

foreach ($purchasedServices as $service) {
    if ($service['current_status'] === 'active') $activeCount++;
    elseif ($service['current_status'] === 'used') $usedCount++;
    else $expiredCount++;
}

$totalServices = count($purchasedServices);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Top Bar */
        .top-bar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 8px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
        .top-bar-left { color: #666; }
        .top-bar-right { display: flex; align-items: center; gap: 15px; }
        .verified-badge { 
            background: #28a745; 
            color: white; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600;
        }
        .logout-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220,53,69,0.4); }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        .dashboard-header .container {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .welcome-section p { opacity: 0.95; font-size: 1.1rem; }
        .user-stats { display: flex; gap: 25px; }
        .stat-card {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .stat-card .number { font-size: 2.5rem; font-weight: 800; margin-bottom: 5px; }
        .stat-card .label { font-size: 0.95rem; opacity: 0.9; }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 50px;
            flex-wrap: wrap;
            padding: 25px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .action-btn {
            padding: 15px 30px;
            background: rgba(255,255,255,0.9);
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .action-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102,126,234,0.4);
        }
        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 60px 0 30px 0;
            padding-bottom: 20px;
            border-bottom: 4px solid #e9ecef;
        }
        .section-header h2 {
            font-size: 2.2rem;
            color: #1a1a2e;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        .service-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            padding: 40px 30px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            transition: all 0.4s;
            border: 1px solid rgba(255,255,255,0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        }
        .service-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 30px 80px rgba(0,0,0,0.2);
        }
        .service-card .icon { font-size: 5rem; margin-bottom: 25px; }
        .service-card h3 {
            color: #1a1a2e;
            margin-bottom: 15px;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .service-card p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        .service-card .price {
            font-size: 2.8rem;
            font-weight: 900;
            color: #28a745;
            margin-bottom: 25px;
            text-shadow: 0 2px 10px rgba(40,167,69,0.3);
        }
        .buy-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .buy-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(102,126,234,0.5);
        }

        /* Table Styles */
        .table-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 60px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .table-container table { width: 100%; border-collapse: collapse; }
        .table-container th,
        .table-container td {
            padding: 20px 25px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .table-container th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #1a1a2e;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .table-container tr:hover {
            background: linear-gradient(90deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
        }

        /* Status Badges */
        .status-badge {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        .status-used {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }
        .status-expired {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            box-shadow: 0 5px 15px rgba(255,193,7,0.4);
        }

        /* Action Buttons */
        .avail-btn, .extend-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            font-weight: 800;
            cursor: pointer;
            margin: 5px;
            font-size: 0.95rem;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .avail-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }
        .avail-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(40,167,69,0.6); }
        .extend-btn {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #212529;
            box-shadow: 0 5px 15px rgba(255,193,7,0.4);
        }
        .extend-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255,193,7,0.6); }
        .avail-btn:disabled {
            background: #ccc;
            color: #999;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 40px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 3px dashed #dee2e6;
            margin-bottom: 60px;
        }
        .empty-state .icon { font-size: 8rem; margin-bottom: 30px; opacity: 0.7; }
        .empty-state h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 2.2rem;
            font-weight: 700;
        }
        .empty-state p {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }
        .modal.show { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content {
            background: white;
            padding: 60px;
            border-radius: 30px;
            max-width: 550px;
            width: 90%;
            text-align: center;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
            transform: scale(0.7);
            animation: modalPop 0.4s ease-out forwards;
        }
        @keyframes modalPop {
            to { transform: scale(1); }
        }
        .modal-icon { font-size: 5rem; margin-bottom: 25px; }
        .modal-content h2 {
            color: #1a1a2e;
            margin-bottom: 20px;
            font-size: 2rem;
            font-weight: 800;
        }
        .modal-btn {
            padding: 18px 45px;
            margin: 10px;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .modal-btn.primary {
                        background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 10px 30px rgba(40,167,69,0.4);
        }
        .modal-btn.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(40,167,69,0.6);
        }
        .modal-btn.secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header .container { flex-direction: column; text-align: center; }
            .user-stats { justify-content: center; gap: 15px; }
            .stat-card { padding: 15px 20px; }
            .stat-card .number { font-size: 2rem; }
            .quick-actions { flex-direction: column; }
            .services-grid { grid-template-columns: 1fr; }
            .section-header h2 { font-size: 1.8rem; }
            .table-container th, .table-container td { padding: 15px 15px; font-size: 0.9rem; }
            .top-bar-container { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <span>👤 Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <span class="verified-badge">✓ Verified</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="welcome-section">
                <h1>👋 Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p>Manage your services, track purchases & extend subscriptions</p>
            </div>
            <div class="user-stats">
                <div class="stat-card">
                    <div class="number"><?php echo $totalServices; ?></div>
                    <div class="label">Total Services</div>
                </div>
                <div class="stat-card">
                    <div class="number" style="color: #28a745;"><?php echo $activeCount; ?></div>
                    <div class="label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="number" style="color: #dc3545;"><?php echo $usedCount; ?></div>
                    <div class="label">Used</div>
                </div>
                <div class="stat-card">
                    <div class="number" style="color: #ffc107;"><?php echo $expiredCount; ?></div>
                    <div class="label">Expired</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Container -->
    <div class="dashboard-container">

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="index.php" class="action-btn">🏠 Home</a>
            <a href="dashboard.php" class="action-btn primary">📊 Dashboard</a>
            <a href="extend_service.php" class="action-btn">⏰ Extend Service</a>
        </div>



<div class="section-header">
    <h2>📦 My Purchased Services (<?= $totalServices ?> Total)</h2>
</div>
<?php if ($successMsg): ?>
<div style="background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; padding: 20px; border-radius: 15px; margin: 20px 0; border: 1px solid #28a745; text-align: center; font-weight: 600; font-size: 1.1rem;">
    <?= htmlspecialchars($successMsg) ?>
</div>
<?php endif; ?>

<?php if (!empty($purchasedServices)): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Price</th>
                    <th>Purchased</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchasedServices as $service): 
                    $isExpired = strtotime($service['expiration_date']) < time();
                    $statusClass = $service['current_status'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($service['service_title'] ?? 'Service') ?></strong>
                        <br><small>ID: #<?= $service['id'] ?></small>
                    </td>
                    <td><strong>$<?= number_format($service['service_price'] ?? 0, 2) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($service['created_at'])) ?></td>
                    <td>
                        <strong style="color: <?= $isExpired ? '#dc3545' : '#28a745' ?>">
                            <?= date('M d, Y', strtotime($service['expiration_date'])) ?>
                        </strong>
                        <?php if (!$isExpired): ?>
                            <br><small><?= floor((strtotime($service['expiration_date']) - time()) / (60*60*24)) ?> days left</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge status-<?= $statusClass ?>"><?= ucfirst($statusClass) ?></span></td>
                    <td>
                        <?php if ($statusClass === 'active'): ?>
                            <button class="avail-btn" onclick="availService(<?= $service['id'] ?>, '<?= addslashes($service['service_title'] ?? '') ?>')">
                                ✅ Avail Service
                            </button>
                            <br>
                            <button class="extend-btn" onclick="extendService(<?= $service['id'] ?>)">
                                ⏰ Extend
                            </button>
                        <?php elseif ($statusClass === 'expired'): ?>
                            <button class="extend-btn" onclick="extendService(<?= $service['id'] ?>)">
                                🔄 Renew Service
                            </button>
                        <?php else: ?>
                            <button class="avail-btn" disabled style="background: #6c757d;">
                                ✔️ Used
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="icon">📦</div>
        <h3>No Services Purchased Yet</h3>
        <p>Start by browsing our premium services above and make your first purchase!</p>
        <a href="#availableServices" class="buy-btn" style="display: inline-block; width: auto; padding: 20px 50px;">
            🛒 Start Shopping
        </a>
    </div>
<?php endif; ?>

<!-- Success Modal -->
<div class="modal" id="successModal">
    <div class="modal-content">
        <div class="modal-icon" id="modalIcon">🎉</div>
        <h2 id="modalTitle">Success!</h2>
        <p id="modalMessage">Operation completed successfully!</p>
        <button class="modal-btn primary" onclick="closeModal()">Continue</button>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <div class="modal-icon" id="confirmIcon">⚠️</div>
        <h2 id="confirmTitle">Confirm Action</h2>
        <p id="confirmMessage">Are you sure you want to proceed?</p>
        <button class="modal-btn secondary" onclick="closeConfirmModal()">Cancel</button>
        <button class="modal-btn primary" id="confirmBtn" onclick="confirmAction()">Confirm</button>
    </div>
</div>

<!-- Hidden Forms FIRST -->
<form id="availForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="avail">
    <input type="hidden" name="service_id" id="avail_service_id">
</form>
<form id="extendForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="extend">
    <input type="hidden" name="service_id" id="extend_service_id">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let pendingAction = null;

    // ✅ FIXED buyService (if you need it)
    window.buyService = function(serviceId, serviceTitle, servicePrice, btn) {
        if (!btn) return alert('❌ Button not found');
        showConfirmModal('💳 Purchase', `Buy "${serviceTitle}" for $${servicePrice}?`, '🛒', function() {
            btn.disabled = true;
            btn.innerHTML = '⏳ Processing...';
            // Your buy logic here
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '💳 Buy Now';
                showSuccessModal('✅ Coming Soon!', 'Buy functionality will be added');
            }, 1000);
        });
    };

    // ✅ FIXED availService (uses your PHP forms)
    window.availService = function(purchaseId, serviceTitle) {
        showConfirmModal('🎉 Avail Service', `Mark "${serviceTitle}" as used?`, '✅', function() {
            document.getElementById('avail_service_id').value = purchaseId;
            document.getElementById('availForm').submit();
        });
    };

    // ✅ FIXED extendService (uses your PHP forms)
    window.extendService = function(purchaseId) {
        showConfirmModal('⏰ Extend Service', 'Extend by 30 days?', '🔄', function() {
            document.getElementById('extend_service_id').value = purchaseId;
            document.getElementById('extendForm').submit();
        });
    };

    // Modal functions (KEEP your existing modals)
    window.showConfirmModal = function(title, message, icon, callback) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmIcon').textContent = icon;
        pendingAction = callback;
        document.getElementById('confirmModal').style.display = 'flex';
    };

    window.showSuccessModal = function(title, message) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('successModal').style.display = 'flex';
    };

    window.confirmAction = function() {
        if (pendingAction) pendingAction();
        document.getElementById('confirmModal').style.display = 'none';
        pendingAction = null;
    };

    window.closeModal = function() {
        document.getElementById('successModal').style.display = 'none';
    };

    window.closeConfirmModal = function() {
        document.getElementById('confirmModal').style.display = 'none';
        pendingAction = null;
    };

    // Click outside to close
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    };

    console.log('✅ ALL FUNCTIONS LOADED');
});
</script>


<style>
.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}
@keyframes spin { to { transform: rotate(360deg); } }
.modal.show { display: flex !important; }
.buy-btn, .avail-btn, .extend-btn { transition: all 0.3s; }
.buy-btn:hover, .avail-btn:hover, .extend-btn:hover { transform: translateY(-2px); }
</style>

<?php include 'purchase.php'; ?>