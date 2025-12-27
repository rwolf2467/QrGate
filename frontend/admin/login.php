<?php
session_start();

// For now, we'll use a simple hardcoded password
// In a real application, you should use proper authentication
$admin_password = 'admin123'; // Change this in production!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if ($password === $admin_password) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}

// If already logged in, redirect to admin panel
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRGate Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #9333ea;
            --secondary: #ec4899;
            --dark: #0a0a0a;
            --darker: #111111;
            --border: #222222;
        }
        
        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Quicksand', sans-serif;
        }
        
        .login-card {
            background-color: var(--darker);
            border: 1px solid var(--border);
            border-radius: 8px;
            max-width: 400px;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: opacity 0.2s;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .input-field {
            background-color: var(--dark);
            border: 1px solid var(--border);
            color: white;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="login-card p-8 w-full mx-4">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-purple-900/50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-qrcode text-2xl text-purple-400"></i>
            </div>
            <h1 class="text-2xl font-bold">QRGate Admin</h1>
            <p class="text-gray-400 mt-2">Enter your admin credentials</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-900/50 border border-red-700 text-red-100 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-6">
                <label class="block mb-2">Admin Password</label>
                <input type="password" name="password" class="input-field" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Default password: <strong>admin123</strong></p>
            <p class="mt-2">Remember to change this in production!</p>
        </div>
    </div>
</body>
</html>