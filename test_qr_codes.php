<?php
require_once 'includes/config.php';
require_once 'includes/content_helper.php';

// Test if QR codes are being fetched properly
echo "<h2>Testing QR Code Functionality</h2>";

// Check if QR codes exist in database
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM qr_codes");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>Total QR codes in database: " . $result['count'] . "</p>";
    
    // Get active QR codes
    $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE is_active = 1 AND display_on_landing = 1");
    $stmt->execute();
    $qrCodes = $stmt->fetchAll();
    
    echo "<p>Active QR codes for landing page: " . count($qrCodes) . "</p>";
    
    if (count($qrCodes) > 0) {
        echo "<h3>QR Codes Details:</h3>";
        foreach ($qrCodes as $qr) {
            $data = json_decode($qr['data'], true);
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> " . $qr['id'] . "<br>";
            echo "<strong>Code:</strong> " . $qr['code'] . "<br>";
            echo "<strong>Purpose:</strong> " . $qr['purpose'] . "<br>";
            echo "<strong>Title:</strong> " . ($data['title'] ?? 'N/A') . "<br>";
            echo "<strong>Type:</strong> " . ($data['type'] ?? 'N/A') . "<br>";
            echo "<strong>Content:</strong> " . htmlspecialchars($data['content'] ?? 'N/A') . "<br>";
            echo "<strong>Display on Landing:</strong> " . ($qr['display_on_landing'] ? 'Yes' : 'No') . "<br>";
            echo "<strong>Is Active:</strong> " . ($qr['is_active'] ? 'Yes' : 'No') . "<br>";
            echo "</div>";
        }
    }
    
    // Test the getActiveQRCodes function
    echo "<h3>Testing getActiveQRCodes() function:</h3>";
    $activeQRs = getActiveQRCodes();
    echo "<p>Function returned " . count($activeQRs) . " QR codes</p>";
    
    if (count($activeQRs) > 0) {
        foreach ($activeQRs as $qr) {
            echo "<div style='border: 1px solid #green; padding: 10px; margin: 10px 0; background: #f0f8f0;'>";
            echo "<strong>ID:</strong> " . $qr['id'] . "<br>";
            echo "<strong>Title:</strong> " . $qr['title'] . "<br>";
            echo "<strong>Type:</strong> " . $qr['type'] . "<br>";
            echo "<strong>Content:</strong> " . htmlspecialchars($qr['content']) . "<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>QR Code Test</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body>
    <h3>QR Code Generation Test:</h3>
    <canvas id="testQR" width="200" height="200"></canvas>
    
    <script>
        // Test QR code generation
        try {
            new QRious({
                element: document.getElementById('testQR'),
                value: 'https://www.torresfarm.com',
                size: 200,
                background: 'white',
                foreground: 'black'
            });
            console.log('QR code generated successfully');
        } catch (error) {
            console.error('Error generating QR code:', error);
        }
    </script>
</body>
</html>