<?php
session_start();
// Agar admin pehle se logged in hai toh dashboard bhej do
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Admin Access | RentHub Command Center</title>

    <!-- 2. Premium Favicon (Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- 4. Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --gold: #D4AF37; 
            --dark-bg: #000000; 
            --card-bg: #0A0A0A; 
            --input-bg: #050505;
            --border: rgba(212,175,55,0.2); 
        }

        body { 
            background: var(--dark-bg); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            overflow: hidden;
        }

        /* Luxury Glow Effect */
        body::after {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            background: var(--gold);
            filter: blur(180px);
            opacity: 0.08;
            z-index: -1;
        }

        .login-card { 
            background: var(--card-bg); 
            padding: 50px 45px; 
            border-radius: 40px; 
            width: 100%; 
            max-width: 420px; 
            border: 1px solid var(--border);
            box-shadow: 0 40px 100px rgba(0,0,0,0.9);
            position: relative;
        }

        .brand-logo {
            color: var(--gold);
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            text-shadow: 0 0 20px rgba(212,175,55,0.3);
        }

        .form-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 10px;
            margin-left: 5px;
        }

        .form-control { 
            background: var(--input-bg); 
            border: 1px solid #1a1a1a; 
            color: #fff; 
            padding: 16px 20px; 
            border-radius: 18px; 
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus { 
            background: #080808; 
            border-color: var(--gold); 
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1); 
            color: #fff;
        }

        .btn-login { 
            background: var(--gold); 
            border: none; 
            color: #000; 
            font-weight: 800; 
            padding: 16px; 
            border-radius: 18px; 
            width: 100%; 
            margin-top: 20px;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-login:hover { 
            background: #e5be40; 
            transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(212,175,55,0.25);
        }

        .alert-custom {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #ff4d4d;
            border-radius: 15px;
            font-size: 13px;
            padding: 12px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <i class="fa-solid fa-crown brand-logo"></i>
        <h2 class="fw-800 m-0">RentHub <span class="text-gold">OS</span></h2>
        <p class="text-muted small">Terminal Access Protocol</p>
    </div>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-custom text-center">
            <i class="fa-solid fa-circle-exclamation me-2"></i> Access Denied: Invalid Credentials
        </div>
    <?php endif; ?>
    
    <form action="auth.php" method="POST">
        <div class="mb-4">
            <label class="form-label">Admin Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter identifier" required autocomplete="off">
        </div>
        
        <div class="mb-4">
            <label class="form-label">Access Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-login">
            AUTHENTICATE <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
        </button>
    </form>

    <div class="text-center mt-5">
        <p style="font-size: 10px; color: #333; letter-spacing: 2px;">SECURED BY RENTHUB CORE V2.0</p>
    </div>
</div>

</body>
</html>