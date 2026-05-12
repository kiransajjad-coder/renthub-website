<?php
/**
 * RentHub OS | Secure User Directory Terminal
 * Logic: Multi-Field Identity & Contact Resolver
 */
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db = $client->renthub_db;

    $search = $_GET['search'] ?? '';
    $filter = [];
    if (!empty($search)) {
        $filter = [
            '$or' => [
                ['full_name' => new MongoDB\BSON\Regex($search, 'i')],
                ['name' => new MongoDB\BSON\Regex($search, 'i')],
                ['email' => new MongoDB\BSON\Regex($search, 'i')],
                ['phone' => new MongoDB\BSON\Regex($search, 'i')],
                ['contact' => new MongoDB\BSON\Regex($search, 'i')]
            ]
        ];
    }

    $users = $db->users->find($filter, ['sort' => ['created_at' => -1]])->toArray();

} catch (Exception $e) {
    die("<div style='color:red; background:#000; padding:20px;'>Terminal Error: " . $e->getMessage() . "</div>");
}

/**
 * Smart Identity Resolver
 */
function resolveName($u) {
    if (!empty($u['full_name'])) return $u['full_name'];
    if (!empty($u['name'])) return $u['name'];
    if (!empty($u['email'])) return explode('@', $u['email'])[0];
    return 'User-' . substr((string)$u['_id'], -4);
}

/**
 * Multi-Field Phone Resolver
 * Check karega phone, contact, ya mobile fields ko
 */
function resolvePhone($u) {
    if (!empty($u['phone'])) return $u['phone'];
    if (!empty($u['contact'])) return $u['contact'];
    if (!empty($u['mobile'])) return $u['mobile'];
    if (!empty($u['phone_number'])) return $u['phone_number'];
    
    return '---'; // Agar koi bhi field na mile
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | RentHub Terminal</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --gold: #D4AF37; --bg: #050505; --surface: #0f0f0f; --border: rgba(212,175,55,0.15); }
        body { background: var(--bg); color: #e4e4e7; font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { width: 85px; position: fixed; height: 100vh; background: #000; border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 30px 0; }
        .nav-link { color: #444; font-size: 1.4rem; margin-bottom: 30px; text-decoration:none; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--gold); }
        .main-content { margin-left: 85px; padding: 40px 60px; }
        .user-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { color: var(--gold); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; padding: 20px; border-bottom: 1px solid var(--border); }
        .custom-table td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .avatar-circle { width: 42px; height: 42px; background: rgba(212,175,55,0.05); border: 1px solid var(--gold); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--gold); font-weight: 800; }
        .search-input { background: #111; border: 1px solid #333; border-radius: 12px; color: white; padding: 12px 20px; width: 300px; }
        .search-input:focus { border-color: var(--gold); box-shadow: none; color: white; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="mb-5" style="color: var(--gold);"><i class="fa-solid fa-crown fs-2"></i></div>
    <a href="view-payments.php" class="nav-link"><i class="fa-solid fa-house-chimney"></i></a>
    <a href="manage-users.php" class="nav-link active"><i class="fa-solid fa-users-gear"></i></a>
    <a href="properties.php" class="nav-link"><i class="fa-solid fa-building-shield"></i></a>
    <a href="logout.php" class="nav-link" style="margin-top: auto;"><i class="fa-solid fa-power-off text-danger"></i></a>
</aside>

<main class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-5 text-gold">
        <div>
            <h6 class="fw-bold mb-1" style="letter-spacing: 4px;">TERMINAL CONTROL</h6>
            <h1 class="fw-bold m-0" style="font-size: 2.5rem; color: #fff;">User <span class="text-gold">Directory</span></h1>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control search-input" placeholder="Search identity..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-warning px-4"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </header>

    <div class="user-card shadow-lg">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>IDENTIFIER</th>
                    <th>CONTACT INFO</th>
                    <th>REGISTRATION</th>
                    <th class="text-center">ACCESS</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $uid = (string)$u['_id']; 
                    $name = resolveName($u);
                    $phone = resolvePhone($u);
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3"><?= strtoupper(substr($name, 0, 1)) ?></div>
                            <div>
                                <div class="fw-bold text-white"><?= htmlspecialchars($name) ?></div>
                                <div class="text-secondary small" style="font-size: 10px;">ID: ...<?= substr($uid, -6) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-white-50 small"><i class="fa-regular fa-envelope me-2"></i><?= htmlspecialchars($u['email']) ?></div>
                        <div class="small <?= ($phone == '---') ? 'text-muted' : 'text-gold' ?>">
                            <i class="fa-solid fa-phone me-2"></i><?= htmlspecialchars($phone) ?>
                        </div>
                    </td>
                    <td class="text-secondary small">
                        <?= ($u['created_at'] instanceof MongoDB\BSON\UTCDateTime) ? $u['created_at']->toDateTime()->format('d M, Y') : 'Legacy' ?>
                    </td>
                    <td class="text-center"><span class="badge border border-warning text-warning" style="font-size: 9px;">USER</span></td>
                    <td class="text-center">
                        <a href="edit-user.php?id=<?= $uid ?>" class="text-secondary mx-2"><i class="fa-solid fa-pen-nib"></i></a>
                        <a href="delete-user.php?id=<?= $uid ?>" class="text-danger mx-2" onclick="return confirm('Purge user?')"><i class="fa-solid fa-trash-can"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>