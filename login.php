<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha_input = $_POST['captcha'] ?? '';

    if (!isset($_SESSION['captcha']) || (int)$captcha_input !== $_SESSION['captcha']) {
        $error = 'Incorrect captcha answer.';
    } elseif ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password, email, role, department FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['department'] = $user['department'];
            session_write_close();
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter username, password, and captcha.';
    }
}

// Generate new captcha for the form
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha'] = $num1 + $num2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ITSM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        /* Basic scrollbar styling just in case */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
    <!-- Inline script to prevent theme flashing on login -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0f172a',
                            card: '#1e293b',
                            border: '#334155'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-dark-bg transition-colors duration-200 flex items-center justify-center h-screen">

<div class="max-w-md w-full bg-white dark:bg-dark-card rounded-xl shadow-lg border border-slate-200 dark:border-dark-border overflow-hidden transition-colors">
    <div class="bg-slate-900 dark:bg-slate-950 p-6 text-center transition-colors">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md mx-auto mb-3">
            TS
        </div>
        <h2 class="text-2xl font-bold text-white tracking-wide">ITSM Central</h2>
        <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Sign in to your account</p>
    </div>
    
    <div class="p-8">
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 border border-red-200 text-sm rounded-lg p-3 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-400 dark:placeholder-slate-600 shadow-sm"
                       placeholder="e.g. admin1">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm"
                       placeholder="••••••••">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Human Verification: What is <?= $num1 ?> + <?= $num2 ?>?</label>
                <input type="number" name="captcha" required 
                       class="w-full px-4 py-2 border border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-400 dark:placeholder-slate-600 shadow-sm"
                       placeholder="Enter the sum">
            </div>
            
            <div class="pt-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all">
                    Sign In
                </button>
            </div>
        </form>
        
    </div>
</div>

</body>
</html>
