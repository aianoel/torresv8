<?php
/**
 * Content Helper Functions for Landing Page
 */

/**
 * Get content value by key
 * @param string $key Content key
 * @param string $default Default value if content not found
 * @return string Content value
 */
function getContent($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT content_value FROM landing_content WHERE content_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['content_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Get all content for a specific section
 * @param string $section Section name
 * @return array Array of content items
 */
function getSectionContent($section) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM landing_content WHERE section = ? ORDER BY content_key");
        $stmt->execute([$section]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get content by section as key-value pairs
 * @param string $section Section name
 * @return array Associative array with content_key as key and content_value as value
 */
function getSectionContentArray($section) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT content_key, content_value FROM landing_content WHERE section = ?");
        $stmt->execute([$section]);
        $results = $stmt->fetchAll();
        
        $content = [];
        foreach ($results as $row) {
            $content[$row['content_key']] = $row['content_value'];
        }
        
        return $content;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Generate star rating HTML
 * @param int $rating Rating from 1-5
 * @return string HTML for star rating
 */
function generateStarRating($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    return $html;
}

/**
 * Escape content for safe HTML output
 * @param string $content Content to escape
 * @param bool $allowHtml Whether to allow HTML tags
 * @return string Escaped content
 */
function escapeContent($content, $allowHtml = false) {
    if ($allowHtml) {
        // Allow basic HTML tags but escape dangerous ones
        return strip_tags($content, '<br><span><strong><em><i><b><u><p><div><h1><h2><h3><h4><h5><h6>');
    }
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}

/**
 * Get active QR codes for display on landing page
 * @param int $limit Maximum number of QR codes to return
 * @return array Array of active QR codes
 */
function getActiveQRCodes($limit = 4) {
    global $pdo;
    
    try {
        // Get active QR codes that haven't expired
        $limit = (int)$limit; // Ensure it's an integer
        $stmt = $pdo->prepare("
            SELECT id, data, created_at 
            FROM qr_codes 
            WHERE is_active = 1 
            AND display_on_landing = 1
            AND (expires_at IS NULL OR expires_at > NOW()) 
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $qrCodes = [];
        foreach ($result as $row) {
            $qrData = json_decode($row['data'], true);
            if ($qrData) {
                $qrCodes[] = [
                    'id' => $row['id'],
                    'title' => $qrData['title'] ?? 'QR Code',
                    'type' => $qrData['type'] ?? 'general',
                    'content' => $qrData['content'] ?? '',
                    'qr_image_path' => generateQRCodeDataURL($qrData['content'] ?? '')
                ];
            }
        }
        
        return $qrCodes;
    } catch (Exception $e) {
        error_log("Error fetching QR codes: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate QR Code Data URL
 * @param string $content Content to encode
 * @return string Data URL for QR code
 */
function generateQRCodeDataURL($content) {
    // Create a placeholder that will be replaced by JavaScript
    // We'll use a data attribute to store the content and generate QR codes client-side
    return 'data:qr-placeholder,' . urlencode($content);
}

// Get testimonials content
$testimonialsContent = getSectionContentArray('testimonials');

// Get footer content
$footerContent = getSectionContentArray('footer');
?>