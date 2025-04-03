<?php

$db_host = 'localhost';
$db_name = 'hotel_reservation_db';
$db_user = 'root';
$db_pass = '';

$redirect_status = 'delete_error'; 
$redirect_message = 'An unknown error occurred.';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {

    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    
    if ($reservation_id === false || $reservation_id <= 0) {
        $redirect_message = "Invalid Reservation ID provided for deletion.";
    } else {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]; 

        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $sql = "DELETE FROM reservations WHERE id = :id";
            $stmt = $pdo->prepare($sql);
        
            $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT); // Bind the ID for 

            if ($stmt->execute()) {
                
                if ($stmt->rowCount() > 0) {
                    $redirect_status = 'delete_success';
                    $redirect_message = 'Reservation ID ' . htmlspecialchars($reservation_id) . ' successfully deleted.';
                } else {
                    
                    $redirect_status = 'delete_notice'; // Use notice for this case
                    $redirect_message = 'No reservation found with ID ' . htmlspecialchars($reservation_id) . ' to delete.';
                }
            } else {
                
                $redirect_message = 'Database execute failed: Could not delete reservation.';
            }

        } catch (PDOException $e) {
            error_log("Database Delete Error: " . $e->getMessage());
            $redirect_message = "Database error occurred during deletion.";

        } catch (Exception $e) {
            error_log("General Delete Error: " . $e->getMessage());
            $redirect_message = "An error occurred during deletion processing.";
        }
    } 

} else {  
    $redirect_message = 'Invalid request for deletion.';
}

header('Location: admin_dashboard.php?view=reservations&status=' . $redirect_status . '&msg=' . urlencode($redirect_message));
exit; 
?>