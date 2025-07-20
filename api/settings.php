<?php
// Include database connection file (adjust path if needed)
include_once '../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => 'Invalid request'
);

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the provider ID (should be stored in session after login)
    session_start();
    $provider_id = isset($_SESSION['provider_id']) ? $_SESSION['provider_id'] : 1; // Default to 1 for testing
    
    // Handle GET request (fetch settings)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Prepare query to fetch all settings for the provider
        $query = "SELECT category, setting_key, setting_value 
                  FROM provider_settings 
                  WHERE provider_id = :provider_id 
                  ORDER BY category, setting_key";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->execute();
        
        // Group settings by category
        $settings = array(
            'account' => array(),
            'notifications' => array(),
            'privacy' => array(),
            'appearance' => array(),
            'preferences' => array()
        );
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = $row['category'];
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            // Convert boolean strings to actual booleans
            if ($value === 'true') {
                $value = true;
            } else if ($value === 'false') {
                $value = false;
            }
            
            // Add to the appropriate category
            if (isset($settings[$category])) {
                $settings[$category][$key] = $value;
            } else {
                // Handle any unexpected categories
                $settings[$category] = array($key => $value);
            }
        }
        
        $response = array(
            'success' => true,
            'settings' => $settings
        );
    }
    
    // Handle POST request (save settings)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Get the section/category and form data
        $section = isset($_POST['section']) ? $_POST['section'] : '';
        
        if (empty($section)) {
            throw new Exception('Missing section parameter');
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Process each setting from the form
        foreach ($_POST as $key => $value) {
            // Skip the section field
            if ($key === 'section') continue;
            
            // Check if setting already exists
            $checkQuery = "SELECT id FROM provider_settings 
                          WHERE provider_id = :provider_id 
                          AND category = :category 
                          AND setting_key = :key";
            
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':provider_id', $provider_id);
            $checkStmt->bindParam(':category', $section);
            $checkStmt->bindParam(':key', $key);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing setting
                $updateQuery = "UPDATE provider_settings 
                               SET setting_value = :value 
                               WHERE provider_id = :provider_id 
                               AND category = :category 
                               AND setting_key = :key";
                
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':provider_id', $provider_id);
                $updateStmt->bindParam(':category', $section);
                $updateStmt->bindParam(':key', $key);
                $updateStmt->bindParam(':value', $value);
                $updateStmt->execute();
            } else {
                // Insert new setting
                $insertQuery = "INSERT INTO provider_settings 
                               (provider_id, category, setting_key, setting_value) 
                               VALUES (:provider_id, :category, :key, :value)";
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':provider_id', $provider_id);
                $insertStmt->bindParam(':category', $section);
                $insertStmt->bindParam(':key', $key);
                $insertStmt->bindParam(':value', $value);
                $insertStmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $response = array(
            'success' => true,
            'message' => 'Settings saved successfully'
        );
    }
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $response = array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    );
}

// Return the response as JSON
echo json_encode($response);
?> 