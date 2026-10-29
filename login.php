<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Login');
$base_url = '';

if (isLoggedIn()) redirect('account.php');

$activeTab = $_GET['tab'] ?? 'login';
$errors    = [];

// ── Login ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $errors[] = 'Fill in both fields to sign in.';
    } else {
        $esc = $conn->real_escape_string($email);
        $r   = $conn->query("SELECT * FROM users WHERE email = '$esc' AND is_active = 1");
        $user = $r ? $r->fetch_assoc() : null;

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];

            mergeGuestCart($conn, (int)$user['id']);

            $redirect = $_GET['redirect'] ?? ($user['role'] === 'admin' ? 'admin/index.php' : 'account.php');
            setAlert('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            redirect($redirect);
        } else {
            $errors[] = "That email and password don't match. Double-check both?";
        }
    }
    $activeTab = 'login';
}

// ── Register ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_register'])) {
    $name     = trim($_POST['reg_name']     ?? '');
    $email    = trim($_POST['reg_email']    ?? '');
    $password = trim($_POST['reg_password'] ?? '');
    $confirm  = trim($_POST['reg_confirm']  ?? '');

    if (!$name || !$email || !$password)
        $errors[] = 'Please fill out everything below.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "That email doesn't look right.";
    if (strlen($password) < 6)
        $errors[] = 'Pick a password that\'s at least 6 characters — for your own sake.';
    if ($password !== $confirm)
        $errors[] = "Those passwords don't match.";

    if (empty($errors)) {
        $esc   = $conn->real_escape_string($email);
        $chk   = $conn->query("SELECT id FROM users WHERE email = '$esc'");
        if ($chk && $chk->num_rows > 0) {
            $errors[] = 'Looks like you already have an account with that email. Sign in instead?';
        } else {
            $hash  = password_hash($password, PASSWORD_DEFAULT);
            $nEsc  = $conn->real_escape_string($name);
            $hEsc  = $conn->real_escape_string($hash);
            $conn->query("INSERT INTO users (name, email, password) VALUES ('$nEsc', '$esc', '$hEsc')");
            $newId = $conn->insert_id;

            $_SESSION['user_id']    = $newId;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['role']       = 'customer';

            mergeGuestCart($conn, $newId);
            setAlert('success', "Welcome to Trovery, $name! Your account is ready.");
            redirect('account.php');
        }
    }
    $activeTab = 'register';
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="row g-0">

                <!-- Left Decorative Panel -->
                <div class="col-lg-5 auth-side">
                    <div style="position:relative;z-index:1">
                        <a href="index.php" class="footer-brand mb-4 d-block">
                            <span class="brand-dot">T</span><span class="brand-text">rovery</span>
                        </a>
                        <h2>Local Roots.<br>National Reach.</h2>
                        <p class="mt-3">
                            Join thousands of Filipinos discovering the finest
                            local products — from Luzon to Mindanao.
                        </p>
                        <div class="mt-4 d-flex flex-column gap-3">
                            <div class="d-flex gap-2 align-items-center">
                                <div style="width:32px;height:32px;background:rgba(255,107,53,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-check text-accent"></i>
                                </div>
                                <span style="color:rgba(255,255,255,.8);font-size:.88rem">Access to 10,000+ authentic local products</span>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <div style="width:32px;height:32px;background:rgba(255,107,53,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-check text-accent"></i>
                                </div>
                                <span style="color:rgba(255,255,255,.8);font-size:.88rem">Track orders from purchase to doorstep</span>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <div style="width:32px;height:32px;background:rgba(255,107,53,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-check text-accent"></i>
                                </div>
                                <span style="color:rgba(255,255,255,.8);font-size:.88rem">Exclusive member deals and early access</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Form Panel -->
                <div class="col-lg-7 auth-form-panel">
                    <h4 class="fw-bold mb-1">
                        <?= $activeTab === 'register' ? 'Create an Account' : 'Welcome Back' ?>
                    </h4>
                    <p class="text-muted small mb-4">
                        <?= $activeTab === 'register' ? 'Start your Trovery journey today.' : 'Sign in to your Trovery account.' ?>
                    </p>

                    <!-- Tabs -->
                    <ul class="nav auth-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>"
                               href="login.php?tab=login">Sign In</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'register' ? 'active' : '' ?>"
                               href="login.php?tab=register">Create Account</a>
                        </li>
                    </ul>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errors as $e): ?>
                        <div><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($activeTab !== 'register'): ?>
                    <!-- LOGIN FORM -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="you@email.com" required autofocus
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Your password" required>
                        </div>
                        <button type="submit" name="do_login" class="btn btn-navy w-100 btn-lg">
                            Sign In
                        </button>
                        <div class="divider-text mt-3">or</div>
                        <a href="login.php?tab=register" class="btn btn-outline-navy w-100">
                            Create a New Account
                        </a>
                    </form>
                    <?php else: ?>
                    <!-- REGISTER FORM -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="reg_name" class="form-control"
                                   placeholder="Juan dela Cruz" required
                                   value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="reg_email" class="form-control"
                                   placeholder="you@email.com" required
                                   value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="reg_password" class="form-control"
                                   placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="reg_confirm" class="form-control"
                                   placeholder="Repeat your password" required>
                        </div>
                        <button type="submit" name="do_register" class="btn btn-accent w-100 btn-lg">
                            Create Account
                        </button>
                        <div class="divider-text mt-3">or</div>
                        <a href="login.php?tab=login" class="btn btn-outline-navy w-100">
                            Already Have an Account? Sign In
                        </a>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
