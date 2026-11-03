<?php
require 'auth.php';
require_role('admin');


if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_role'])) {
    $allowed = ['user', 'moderator', 'admin'];
    $targetId = (int) $_POST['target_id'];
    $newRole = $_POST['role'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = 'Invalid request token.';
    } elseif (!in_array($newRole, $allowed, true)) {
        $flash = 'Invalid role.';
    } elseif ($targetId === (int) $me['userID']) {
        $flash = 'You cannot change your own role.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET Role = ? WHERE userID = ?");
        $stmt->execute([$newRole, $targetId]);
        $flash = 'Role updated.';
    }
}

try {
    $stmt = $pdo->query("SELECT userID, FirstName, LastName, Email, Role, Status, EmailVerified, EmailVerificationToken, TwoFactorPin, RegDatetime, LoginDatetime, LogoutDatetime FROM users ORDER BY RegDatetime DESC");
    $users = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("<p style='color:red;'>Error fetching metrics: " . $e->getMessage() . "</p>");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Master Container Admin Console</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 30px auto;
            max-width: 1300px;
            padding: 0 20px;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .refresh-btn {
            background: #0076ff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            color: #555;
        }

        .status-badge {
            padding: 3px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-online {
            background-color: #e3fbe3;
            color: #1e7e34;
        }

        .status-offline {
            background-color: #f8d7da;
            color: #bd2130;
        }

        .token-box {
            background: #fff3cd;
            color: #856404;
            padding: 4px;
            font-family: monospace;
            font-size: 11px;
            border-radius: 4px;
            max-width: 180px;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pin-box {
            background: #e2e3e5;
            color: #383d41;
            padding: 4px 8px;
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header">
        <div>
            <h1>Master Infrastructure Analytics Panel</h1>
            <p>Database Layer Status: <span style="color: #0076ff; font-weight: bold;">MFA / 2FA Monitoring Core Matrix
                    Active</span></p>
        </div>
        <a href="admin.php" class="refresh-btn">🔄 Refresh Metrics Engine</a>
    </div>

    <?php if ($flash): ?>
        <p style="background:#fff3cd; padding:8px; border-radius:4px;"><?php echo htmlspecialchars($flash); ?></p>
    <?php endif; ?>

    <h3>Active Database Account Registry (Security Lifecycle Diagnostics)</h3>

    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Role</th>
                <th>Verification State</th>
                <th>Live Security Token</th>
                <th>Active 2FA PIN</th>
                <th>Session State</th>
                <th>Last Login</th>
                <th>Last Logout</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><strong>#<?php echo $row['userID']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                        <td><code><?php echo htmlspecialchars($row['Email']); ?></code></td>
                        <td>
                            <form method="POST" style="display:flex; gap:4px;">
                                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                                <input type="hidden" name="target_id" value="<?php echo (int)$row['userID']; ?>">
                                <select name="role">
                                    <?php foreach (['user', 'moderator', 'admin'] as $r): ?>
                                        <option value="<?php echo $r; ?>" <?php echo $row['Role'] === $r ? 'selected' : ''; ?>>
                                            <?php echo $r; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="set_role">Save</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($row['EmailVerified'] == 1): ?>
                                <span class="status-badge status-online">✔ Verified (1)</span>
                            <?php else: ?>
                                <span class="status-badge status-offline">⏳ Pending (0)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['EmailVerificationToken'])): ?>
                                <span class="token-box"><?php echo htmlspecialchars($row['EmailVerificationToken']); ?></span>
                                <a href="verify_email.php?token=<?php echo urlencode($row['EmailVerificationToken']); ?>"
                                    target="_blank" style="font-size:10px; color:#0076ff;">[Auto Activate]</a>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">None/Cleared</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['TwoFactorPin'])): ?>
                                <span class="pin-box"><?php echo htmlspecialchars($row['TwoFactorPin']); ?></span>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['Status'] == 1): ?>
                                <span class="status-badge status-online">● Online</span>
                            <?php else: ?>
                                <span class="status-badge status-offline">○ Offline</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['LoginDatetime'] ?? 'Never'); ?></td>
                        <td><?php echo htmlspecialchars($row['LogoutDatetime'] ?? 'Never'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align:center; padding:20px;">No container metrics available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="margin-top:20px;">
        <strong>Operational Testing Navigation:</strong>
        <a href="register.php">Register Suite</a> |
        <a href="login.php">Login Terminal</a> |
        <a href="dashboard.php">User Profile Area</a>
    </p>

</body>

</html>