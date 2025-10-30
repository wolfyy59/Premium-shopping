<?php
// orders.php - Password Protected Order Viewer

// Configuration
$PASSWORD = '*200#';
$ORDERS_FILE = 'orders.json';
$LOG_FILE = 'access.log';

// Helper functions
function logAccess($ip, $status) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] IP: $ip - $status\n";
    file_put_contents($LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

function loadOrders() {
    global $ORDERS_FILE;
    if (!file_exists($ORDERS_FILE)) {
        return [];
    }
    $data = file_get_contents($ORDERS_FILE);
    return json_decode($data, true) ?: [];
}

function saveOrder($orderData) {
    global $ORDERS_FILE;
    $orders = loadOrders();
    $orders[] = $orderData;
    file_put_contents($ORDERS_FILE, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' DA';
}

function formatDate($timestamp) {
    return date('d/m/Y H:i:s', strtotime($timestamp));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_order':
                $orderData = [
                    'id' => time() . rand(1000, 9999),
                    'timestamp' => date('c'),
                    'customer' => [
                        'firstName' => $_POST['firstName'],
                        'lastName' => $_POST['lastName'],
                        'phone' => $_POST['phone'],
                        'wilaya' => $_POST['wilaya'],
                        'delivery' => $_POST['delivery']
                    ],
                    'products' => json_decode($_POST['products'], true),
                    'total' => floatval($_POST['total']),
                    'totalItems' => intval($_POST['totalItems']),
                    'ip' => $clientIP
                ];
                saveOrder($orderData);
                logAccess($clientIP, "New order saved - ID: {$orderData['id']}");
                echo json_encode(['success' => true, 'orderId' => $orderData['id']]);
                exit;
                
            case 'verify_password':
                $enteredPassword = $_POST['password'] ?? '';
                if ($enteredPassword === $PASSWORD) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['auth_time'] = time();
                    logAccess($clientIP, "Password verified successfully");
                    echo json_encode(['success' => true]);
                } else {
                    logAccess($clientIP, "Failed password attempt");
                    echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
                }
                exit;
        }
    }
}

// Start session and check authentication
session_start();
$clientIP = $_SERVER['REMOTE_ADDR'];

// Check if user is authenticated
$authenticated = isset($_SESSION['authenticated']) && 
                $_SESSION['authenticated'] && 
                (time() - $_SESSION['auth_time']) < 3600; // 1 hour session

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: orders.php');
    exit;
}

// If not authenticated, show password prompt
if (!$authenticated) {
    logAccess($clientIP, "Access attempt - not authenticated");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Administrateur - Premium Shopping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .password-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .password-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #3498db;
            border-radius: 10px;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 3px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .password-input:focus {
            outline: none;
            border-color: #2980b9;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1c6ea4);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            display: none;
        }
        
        .info {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="logo">🔐</div>
        <h1>Accès Administrateur</h1>
        <p class="subtitle">Veuillez entrer le mot de passe pour accéder aux commandes</p>
        
        <form id="passwordForm" method="POST">
            <input type="hidden" name="action" value="verify_password">
            <input type="password" class="password-input" id="password" name="password" 
                   placeholder="*****" maxlength="5" required autofocus>
            <button type="submit" class="submit-btn">Valider</button>
        </form>
        
        <div class="error-message" id="errorMessage"></div>
        
        <div class="info">
            Accès réservé au personnel autorisé
        </div>
    </div>

    <script>
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorDiv = document.getElementById('errorMessage');
            
            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.message || 'Mot de passe incorrect';
                    errorDiv.style.display = 'block';
                    document.getElementById('password').value = '';
                    document.getElementById('password').focus();
                }
            })
            .catch(error => {
                errorDiv.textContent = 'Erreur de connexion';
                errorDiv.style.display = 'block';
            });
        });
        
        // Auto-focus on password input
        document.getElementById('password').focus();
    </script>
</body>
</html>
<?php
    exit;
}

// User is authenticated - show orders
logAccess($clientIP, "Access granted - viewing orders");
$orders = loadOrders();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Premium Shopping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #1a2530);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .admin-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .admin-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1c6ea4);
            transform: translateY(-2px);
        }
        
        .admin-btn.logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .admin-btn.logout:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
        }
        
        .orders-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .orders-list {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
        }
        
        .order-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-id {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .order-customer {
            margin-bottom: 15px;
        }
        
        .customer-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .customer-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .order-products {
            margin: 15px 0;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f2f6;
        }
        
        .product-name {
            font-weight: 500;
        }
        
        .product-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .order-total {
            text-align: right;
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #3498db;
        }
        
        .location-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 10px;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .no-orders .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .admin-actions {
                flex-direction: column;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Gestion des Commandes</h1>
            <p class="subtitle">Premium Shopping - Toutes les commandes clients</p>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($orders); ?></div>
                    <div class="stat-label">Commandes Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($orders, 'totalItems')); ?></div>
                    <div class="stat-label">Articles Vendus</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatPrice(array_sum(array_column($orders, 'total'))); ?></div>
                    <div class="stat-label">Chiffre d'Affaires</div>
                </div>
            </div>
            
            <div class="admin-actions">
                <a href="orders.php?export=txt" class="admin-btn">📥 Exporter en TXT</a>
                <a href="orders.php?export=json" class="admin-btn">📊 Exporter en JSON</a>
                <a href="orders.php?logout=true" class="admin-btn logout">🚪 Déconnexion</a>
            </div>
        </div>
        
        <div class="orders-section">
            <h2 class="section-title">📦 Liste des Commandes</h2>
            
            <div class="orders-list">
                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <div class="icon">📭</div>
                        <p>Aucune commande trouvée</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($orders) as $order): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <div class="order-id">
                                    Commande #<?php echo $order['id']; ?>
                                    <?php if (isset($order['ip'])): ?>
                                        <span class="location-badge">IP: <?php echo $order['ip']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="order-date">
                                    <?php echo formatDate($order['timestamp']); ?>
                                </div>
                            </div>
                            
                            <div class="order-customer">
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($order['customer']['firstName'] . ' ' . $order['customer']['lastName']); ?>
                                </div>
                                <div class="customer-details">
                                    📞 <?php echo htmlspecialchars($order['customer']['phone']); ?> | 
                                    🏠 <?php echo htmlspecialchars($order['customer']['wilaya']); ?> | 
                                    🚚 <?php echo $order['customer']['delivery'] === 'domicile' ? 'Livraison à Domicile' : 'Point Relais'; ?>
                                </div>
                            </div>
                            
                            <div class="order-products">
                                <?php foreach ($order['products'] as $product): ?>
                                    <div class="product-item">
                                        <div class="product-name">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                        </div>
                                        <div class="product-details">
                                            <?php echo $product['quantity']; ?>x - <?php echo formatPrice($product['price']); ?> 
                                            <span style="color: #3498db;">(<?php echo htmlspecialchars($product['color']); ?>)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-total">
                                Total: <?php echo formatPrice($order['total']); ?>
                                <div style="font-size: 0.9rem; color: #6c757d; margin-top: 5px;">
                                    <?php echo $order['totalItems']; ?> article(s)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // Handle exports
    if (isset($_GET['export'])) {
        $exportType = $_GET['export'];
        
        if ($exportType === 'txt') {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.txt"');
            
            echo "PREMIUM SHOPPING - RAPPORT DES COMMANDES\n";
            echo "========================================\n\n";
            echo "Généré le: " . date('d/m/Y H:i:s') . "\n";
            echo "Total des commandes: " . count($orders) . "\n";
            echo "Total des articles: " . array_sum(array_column($orders, 'totalItems')) . "\n";
            echo "Chiffre d'affaires: " . formatPrice(array_sum(array_column($orders, 'total'))) . "\n\n";
            
            foreach (array_reverse($orders) as $index => $order) {
                echo "COMMANDE " . ($index + 1) . ":\n";
                echo "ID: " . $order['id'] . "\n";
                echo "Client: " . $order['customer']['firstName'] . " " . $order['customer']['lastName'] . "\n";
                echo "Téléphone: " . $order['customer']['phone'] . "\n";
                echo "Wilaya: " . $order['customer']['wilaya'] . "\n";
                echo "Livraison: " . ($order['customer']['delivery'] === 'domicile' ? 'Livraison à Domicile' : 'Point Relais') . "\n";
                echo "Date: " . formatDate($order['timestamp']) . "\n";
                if (isset($order['ip'])) {
                    echo "IP: " . $order['ip'] . "\n";
                }
                echo "Produits:\n";
                
                foreach ($order['products'] as $product) {
                    echo "  - " . $product['quantity'] . "x " . $product['title'] . " - " . 
                         formatPrice($product['price']) . " (" . $product['color'] . ")\n";
                }
                
                echo "Total: " . formatPrice($order['total']) . "\n";
                echo "Articles: " . $order['totalItems'] . " pièce(s)\n";
                echo "\n" . str_repeat("-", 50) . "\n\n";
            }
            exit;
            
        } elseif ($exportType === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.json"');
            echo json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    ?>
</body>
</html>