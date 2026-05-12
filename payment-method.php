<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Data receiving from checkout
// Note: total_price already includes the service fee from checkout.php
$product_id = $_POST['product_id'] ?? '';
$total_amount = $_POST['total_price'] ?? 0; 
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

// Basic Security Redirect
if (empty($product_id)) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Page Title -->
    <title>Secure Checkout | RentHubPro</title>

    <!-- 2. Premium Favicon (Executive Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 3. Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- 4. Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --gold: #D4AF37; --navy: #0A192F; }
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .pay-card { 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: 2px solid transparent; 
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .pay-card:hover { 
            border-color: var(--gold); 
            transform: translateY(-8px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .pay-card img { 
            height: 60px; 
            object-fit: contain; 
            margin-bottom: 20px; 
        }
        .method-btn { border: none; background: none; width: 100%; padding: 0; }
        
        .amount-banner {
            background: var(--navy);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            
            <div class="amount-banner shadow">
                <p class="mb-1 opacity-75 text-uppercase small fw-bold">Final Amount to Pay</p>
                <h2 class="display-6 fw-bold">Rs. <?= number_format($total_amount) ?></h2>
            </div>

            <h3 class="fw-bold mb-4">Choose Payment <span style="color: var(--gold);">Method</span></h3>
            <p class="text-muted mb-5">Please select your preferred mobile wallet to proceed with the booking.</p>

            <div class="row g-4">
                <div class="col-md-6">
                    <form action="../backend/payments/jazzcash-mock.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <input type="hidden" name="total_price" value="<?= $total_amount ?>">
                        <input type="hidden" name="start_date" value="<?= $start_date ?>">
                        <input type="hidden" name="end_date" value="<?= $end_date ?>">
                        
                        <button type="submit" class="method-btn">
                            <div class="card pay-card p-5 bg-white">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAA/1BMVEX/////xgruIycAAADtAAD/wgC6urr7+/ttbW2Wlpb/xABmZmbDw8MqKip7e3v/9uLPz8//8tOkpKT19fX/7sX/zgbtAAvuGyDa2tr/yQmvr6+9vb3uFhvuGyjr6+t1dXX2np+NjY1cXFz96Oj72dr/5aZNTU0zMzPT09MgICDl5eVFRUX6ycr4uLn+8/TwREfxVFb/2Xf/zkH1k5SEhIQYGBj/9Nn0iIn83+DvLzLzcnT3rK3/5KLyZGbwS07ze33+ug3vOj36nhXzYSDwOyT0cB70bR74jhj/yzD/12//3Yj/0lb8rxDxSyPyVSH3qar3gxr/1GH2fiX/+u7/67k5xqAaAAALNElEQVR4nO2de1ujPBqHS0vP1WmLQg+sth7qeTxN1VHHed3jjO7urK/z/T/LEkhCAgkk0JZcXvn9VQOE3Dk8z5MAsVTS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tL6qNr/+fbSy3Tl59290zOo073d+oILtiDdtICqxsu7xEX1vbPr+2fTk40F/ri4fdhTjfNTtWX4alXfboSu+Hx2e26btus65Zgc1ztyMblbcqGldAUBfcbX1M56Nzn34BhsFKb5dfJ5FYUX0XvVIOQxfko6++HcTKFDkLZ5r0hDbrQMg2a85J47cW0hvECu+V2JdowSeoytfeaZD64tjgcZJ6uFYSpO6DFexbvq6bMsH5D99bQAJlosQq+rPtJnff5mSvRPQo75reiuyiT0GF9J7/jLdDPxAbn2WWFwvjiE3mjEjuPuPEsHDWXeFwnIJTQM1FMnGTtoKPtJTUKj+tM7vpuzAQPE72oSeja19JC7AcvAa9wqSmgYf9h/yYvn2Pb9XoGAKYTD6t/zITrmc8GmNIXQGLbKORAd81xRj0+1YnZEFSKadEJj+EdGQkeJqLRU6qURGsN/Z0K0n3aLZguUTmhs/jUDonldNBmSAKGx+acsomMX6iAoiRAam/+RQ3Sfi55QEBIiNKpShPZF0VSkxAiHMkPRLnYuEZUYobH5N2FExQBFCQ1DlND+VjRSRKKEw3+JIbpKjUEg4TbcFIrBna9FA8UkTCgW2piKBDKEhAmNzf+mI5oqhNoRiRMKNKJd5FyeJ3HC9EZUcBCWpAhTG9FUJxglJEHomdNEQLfIFTW+ZAhTfKKr2sPfQDKEyYGN/VA0C1ufqulcYTdNiE7VNDMlScKkKUbRD2C4kiI0hgndtGgSnuQI+d3U/lU0CU+Sbci1praahrQkS2jwFk8V9YVAkoSbnCZUM5zxJUvIHojKuoqSNCFnILrKrP/GJUvIjr4V7qSyhBxTYxeNkSBZQoM5DJVbfiIkS8icQak8DOUJWY8wlI1JgaQJWe5CZUMjT/hPFqF6a4ihZAmH/2ARKhuUljIQsp6WmkVTJEkTxgiZvbRoiiQtxtIUTZGkxXiLj2RpmB7/Q3kLZtT2oTz+kAH4saI25nsn7qRojAQtZAbsqPZ2AqnFrGIouxxckl+JYrlDtY2pLCH7EaKqz52AJAlb7E6q8jJG6jvClLgPn0yFXkaMaF+OkBV3K95NL6UIuU/ynUI//EnUDxnChLfaTUW+iY3pXe7h2v+4hMo+fHpcTCdV2NYYUp006ZUhV8U3vjxfIfeMm2dJFW7EK6lOmvyesKvY28++5Jow7TVhFefBUg2Y9lpb2Xkumieml8VEbFj2pGiiiHoLmVZQ/VStSdS7nJUR+ahEsX4qaUeH6U1Y8EfbUb1JAvIDNqqfqjPHkJtTiH/bpYzLkAUU/+7JVsPa/JQElPh2zXFVQHyVBGS/Y6Iu4rvUrBdI4tu8sgKfyPZa0oCC361hFfud+ovsS1BZPle3zwvrqe+Sfh6oJctXBtsNFPSa1GNVHnAo+SE3asZyAd+x9aRNjCFrZUiZFytef/v0lqEBU1YukuWY9yscjhuvWfhyAZb9ffdWxLhvZOIzhn/m3UTJXcn+iY8taRcYqPq7/pR7IyzXPF9yBLCfEQ9u2fbdzIvojcdlNuP7lbSHR4A/gm33fuXf7muZ72rIh2hI/m5tvvbK2fcUhITLWyy+ydyArd9ENve5eqqzzFlxFhcP+MIGDHTqZG9G+3mJDkPu2VnI9xrfMvk242h0l7yImiHMblXfmNsl32XZwtS2r5f82uKGpKNvVX88crf1Pn2SY3Rsd7L81zJ7Eg8Iwa7lyTt6730T3qrVcc2LFb3P9ygUsHmdcyiy8/zutSsA6W/ovcKgu3fpQSZt4unp6lFsP3ZPp9+dpE29wa7sT6vflP3m8araYkSn/o76P142ZLbU97Q3uTDBzuxOhM2DM59uT4t6GNzbv7wyqj5ooGrVuLrcz/ZPETzKs+tvz64Zqnx+f31W/Ir3+03v9z7Q741e4v7ygqrv7vm6U/U/P2hpaWlpaWlpaWlpaWlpaWmtXHXL1/KWS7Zq08bs5GS23ezmv0lQWEvqmmnFVyP3zZmq9w8qhNrdnNkF2exIXdQMLlrPd+vkzAkd5GKEhO0shVgG4WAeA8zZWxQjHLH4PM23MmepFmGfA+jpOGueShHyWtCXnDEMpRLhgOD5UjscDLqjdphykDFXlQgxzFonLF9oWvvZclWIcIrbj0reOsoFuDDCw86o1s1u8DxZvLaqr3mJ2/Qw3OrURp0BO6OtrnfsEMdCJKHlHRpzLqMUJeyi4bIGSrfdAAIHa+1GVG2LmVoPPX28Z2xVTqhS1ZuwWSsNL73jZ9c+jB7b6UYJOyfoFqlNQRNaO4SFqHRLgc8+KrGNv8UIWiqVOh6Fc8b96FqnLO46+jMYujXy2MyiCMlijmQISQvo3yqIKtckCVE2tbTqbdBXzkjCaNaDkLBBtUNlKk4YBfRaIRMhOjcNcIdxNSLsxFK3MGFUY1HCLc4NpQlh02ynAH7h3Q8QxmPaOZcwuSoJwhPO9T4hg2XKS4UZpQyQLu92gPAQ/W58QS09rdOERB0k3ikkxCP7pLZVqh/3j3AGgLCzvQ61jU7zUsfMVHhlykQJl3A6sDzbv41vBwj7YW4lC9SY72JCwikwoQMEfyJGuAZPxz4M96I16gpUFIuZCu4MfyY7K1ShByif0Ap0cMgAA6LKThCsY0JUechUJa0eYELUL4gWR5EJRYhKRg/vEZkqRDiL5Y3tQEiInBg8AxE28TUwIWm2ggnhD2qF4CBOiIpBmxE6dU5XNFN1RtnGISGqsZ1D1kVh94FdhzqLRzhjVPw4TgihI86cToV/JbrDLqNC0QDuUHZ9fYyBICExNWmmVyYmhPlRB60YIeq4dA+MpMLAj466I4KNRFtBYvQRhgeEAnWSkKiWvjThjD4aJUSDlY6oo6kwz6MkQmblkzENtUgH847PLeQJIwt1RzQhGgd0PcRSkacTGB3MMDWwoJGIZ3tBhBHPEmlDdFPaUcRTK8zMKPVJGHZil2Zs5iWE7pA6eEwTIgPH7Fpk6jpMYsx0UZw8xsUm1IhgWyMS0hInPCYcJLYK8Afl5poUIeSNWBBWKnbesboFd6kRp9DBRIXRsF08AekLE06J0qDZ+AidS5oHdBAWA7ZyZBmJmYpXnSIeI0j3J8GME1AQBQktOLm1oCNrCBKOguqAghd7RgF5IOJyFIqvUfenJ9XsVLyMUWkQB7o41O2H7iC0NTgw7sDCoyUC2E12hAi70AjPOhZ4LIRiUTD8UJefwTIN8EGfEBlI2oN1mKnU5L1dO/buZQ3IhzQW0ZPRpXj1yiccg8AIDhkcHQgQ8iZYoNuGke9Ov1brE3OpNapVQk2ZqYHxWGccwfILhI3IfDqqjcjzO6UBvPv2Yd1rB1xIkTbkrLX7hp47IwWEM0b6lJkKzWObcQhqFOnJUXXoNZowWWgcMlcOYG/gTYEBISt9ykxFDqDBOgiEjEt8oQKjsIo5F/WHB/Frm7xjB8FQzEbI6y+haYk924BV3GEOp0Nhjx/rPoQLpKtuXs9FWDpmdGLKmUZasT/GhCUrWtsgUdQfdo7IK+kVaHIArCOuuRQhubgXCbwqXyLrtxZZ3V06LqXWgHb8C8XnFofToD8ctUexB121tl8BJ02Q57gGBBq5xtCAk0ox1NaD1pifTFnR43HTL8pRA2AdBzmgaqg1gj40a6KUkX+cyGdAX0Gp7omVHhziHMmohHul3W/hZdHS0tLS0tLS0tLS0tLS0tLS0tLS0tLSKl7/B++qGc1gpvARAAAAAElFTkSuQmCC" alt="JazzCash">
                                <h5 class="fw-bold">JazzCash</h5>
                                <small class="text-muted">Pay via JazzCash Mobile Account</small>
                            </div>
                        </button>
                    </form>
                </div>

                <div class="col-md-6">
                    <form action="../backend/payments/easypaisa-mock.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <input type="hidden" name="total_price" value="<?= $total_amount ?>">
                        <input type="hidden" name="start_date" value="<?= $start_date ?>">
                        <input type="hidden" name="end_date" value="<?= $end_date ?>">
                        
                        <button type="submit" class="method-btn">
                            <div class="card pay-card p-5 bg-white">
                                <img src="data:image/png;base64,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" alt="Easypaisa">
                                <h5 class="fw-bold text-success">Easypaisa</h5>
                                <small class="text-muted">Pay via Easypaisa Wallet</small>
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-5">
                <a href="checkout.php" class="text-decoration-none text-muted">
                    <i class="fa fa-arrow-left me-1"></i> Back to Summary
                </a>
            </div>
            
        </div>
    </div>
</div>

</body>
</html>