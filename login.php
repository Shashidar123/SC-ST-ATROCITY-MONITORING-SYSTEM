<?php
require_once 'includes/auth.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticate($username, $password)) {
        $role = getCurrentUserRole();
        header('Location: ' . getRoleRedirect($role));
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SC/ST Case Management Portal</title>
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2c5282;
            --secondary: #e53e3e;
            --accent: #dd6b20;
            --light: #f7fafc;
            --gray: #e2e8f0;
            --dark-gray: #4a5568;
            --white: #ffffff;
            --success: #38a169;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--primary);
            background-color: var(--light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            padding: 1rem 2rem;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo img {
            height: 40px;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .logo-text span {
            color: var(--secondary);
        }

        /* Main Content */
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(26, 54, 93, 0.05) 0%, rgba(44, 82, 130, 0.05) 100%);
        }

        .login-container {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border: 1px solid var(--gray);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--dark-gray);
        }

        .login-form .form-group {
            margin-bottom: 1.5rem;
        }

        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--primary);
        }

        .login-form input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray);
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: var(--transition);
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.1);
        }

        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input {
            width: auto;
        }

        .forgot-password {
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            width: 100%;
            text-align: center;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: #c53030;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--dark-gray);
            font-size: 0.875rem;
        }

        .login-footer a {
            color: var(--secondary);
            text-decoration: none;
            transition: var(--transition);
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--white);
            padding: 1.5rem 2rem;
            text-align: center;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--white);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/376px-Emblem_of_India.svg.png" alt="National Emblem">
                <div class="logo-text">SC/ST <span>Portal</span></div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="login-container">
            <div class="login-header">
                <h1>Login to Dashboard</h1>
                <p>Access your SC/ST Case Management account</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="error" style="color: var(--secondary); text-align:center; margin-bottom: 1rem; font-weight: 500;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <p>&copy; 2023 SC/ST Case Management Portal. All rights reserved.</p>
            <div class="footer-links">
                <a href="privacy.html">Privacy Policy</a>
                <a href="terms.html">Terms of Service</a>
                <a href="contact.html">Contact Us</a>
            </div>
        </div>
    </footer>
</body>
</html>