<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'My Account');
$base_url = '';
requireLogin('login.php');

$uid  = (int)$_SESSION['user_id'];
$tab  = $_GET['tab'] ?? 'profile';

// Load user
$r    = $conn->query("SELECT * FROM users WHERE id = $uid");
$user = $r->fetch_assoc();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = sanitize($conn, $_POST['name']    ?? '');
    $phone   = sanitize($conn, $_POST['phone']   ?? '');
    $address = sanitize($conn, $_POST['address'] ?? '');
    $city    = sanitize($conn, $_POST['city']    ?? '');
    $state   = sanitize($conn, $_POST['state']   ?? '');
    $zip     = sanitize($conn, $_POST['zip']     ?? '');

    if ($name) {
        $conn->query("UPDATE users SET name='$name', phone='$phone', address='$address',
                      city='$city', state='$state', zip='$zip' WHERE id=$uid");
        $_SESSION['user_name'] = $name;
        setAlert('success', 'Profile updated successfully!');
    }
    redirect('account.php?tab=profile');
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $newPass  = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        setAlert('danger', 'Current password is incorrect.');
    } elseif (strlen($newPass) < 6) {
        setAlert('danger', 'New password must be at least 6 characters.');
    } elseif ($newPass !== $confirm) {
        setAlert('danger', 'Passwords do not match.');
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $h    = $conn->real_escape_string($hash);
        $conn->query("UPDATE users SET password='$h' WHERE id=$uid");
        setAlert('success', 'Password changed successfully!');
    }
    redirect('account.php?tab=security');
}

// Load orders
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
$orders = $orders ? $orders->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Account Header -->
<div class="account-header">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <div class="account-avatar"><?= strtoupper($user['name'][0]) ?></div>
            <div>
                <h2 class="text-white fw-bold mb-0"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="text-white-50 mb-0 small"><?= htmlspecialchars($user['email']) ?> · Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar Nav -->
        <div class="col-lg-3">
            <div class="p-3 bg-white rounded-3 shadow-sm">
                <div class="account-nav">
                    <a href="account.php?tab=profile" class="account-nav-item <?= $tab === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="account.php?tab=orders" class="account-nav-item <?= $tab === 'orders' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i> My Orders
                    </a>
                    <a href="account.php?tab=security" class="account-nav-item <?= $tab === 'security' ? 'active' : '' ?>">
                        <i class="fas fa-lock"></i> Security
                    </a>
                    <?php if (isAdmin()): ?>
                    <hr>
                    <a href="admin/index.php" class="account-nav-item">
                        <i class="fas fa-cog text-accent"></i> Admin Panel
                    </a>
                    <?php endif; ?>
                    <hr>
                    <a href="logout.php" class="account-nav-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">

            <?php renderAlert(); ?>

            <?php if ($tab === 'profile'): ?>
            <!-- Profile Tab -->
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h5 class="fw-bold mb-4">Personal Information</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($user['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small class="text-muted">Email cannot be changed.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Province</label>
                            <input type="text" name="state" class="form-control"
                                   value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ZIP</label>
                            <input type="text" name="zip" class="form-control"
                                   value="<?= htmlspecialchars($user['zip'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="update_profile" class="btn btn-navy">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <?php elseif ($tab === 'orders'): ?>
            <!-- Orders Tab -->
            <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold mb-0">Order History</h5>
                </div>
                <?php if (empty($orders)): ?>
                <div class="empty-state p-5">
                    <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                    <h5>No Orders Yet</h5>
                    <p>You haven't placed any orders yet. Start shopping!</p>
                    <a href="shop.php" class="btn btn-navy">Browse Products</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table orders-table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $itemCount = $conn->query("SELECT SUM(quantity) AS n FROM order_items WHERE order_id = {$order['id']}")->fetch_assoc()['n'];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                <td><?= (int)$itemCount ?> item<?= $itemCount != 1 ? 's' : '' ?></td>
                                <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                <td><?= statusBadge($order['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($tab === 'security'): ?>
            <!-- Security Tab -->
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h5 class="fw-bold mb-4">Change Password</h5>
                <form method="POST" style="max-width:420px">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-navy">
                        <i class="fas fa-key me-2"></i> Update Password
                    </button>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
