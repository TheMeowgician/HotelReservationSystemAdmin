<?php

$db_host = 'localhost';
$db_name = 'hotel_reservation_db';
$db_user = 'root';
$db_pass = '';

$redirect_status = 'update_error';
$redirect_message = 'An unknown error occurred.';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {

    
    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    $customerName = trim(filter_input(INPUT_POST, 'customerName', FILTER_SANITIZE_STRING));
    $contactNumber = trim(filter_input(INPUT_POST, 'contactNumber', FILTER_SANITIZE_STRING));
    $fromDate = trim(filter_input(INPUT_POST, 'fromDate', FILTER_SANITIZE_STRING));
    $toDate = trim(filter_input(INPUT_POST, 'toDate', FILTER_SANITIZE_STRING));
    $roomType = trim(filter_input(INPUT_POST, 'roomType', FILTER_SANITIZE_STRING));
    $roomCapacity = trim(filter_input(INPUT_POST, 'roomCapacity', FILTER_SANITIZE_STRING));
    $paymentType = trim(filter_input(INPUT_POST, 'paymentType', FILTER_SANITIZE_STRING));

    
    if (!$reservation_id || empty($customerName) || empty($fromDate) || empty($toDate) || empty($roomType) || empty($roomCapacity) || empty($paymentType)) {
        $redirect_message = "Missing required fields.";
        
        header('Location: admin_dashboard.php?view=reservations&status=' . $redirect_status . '&msg=' . urlencode($redirect_message));
        exit;
    }

    
    $numberOfDays = 0;
    $totalBill = 0;
    $calculation_error_message = null;

    try {
        $date1 = new DateTime($fromDate);
        $date2 = new DateTime($toDate);

        if ($date2 >= $date1) {
            $diff = $date2->diff($date1);
            $numberOfDays = $diff->days == 0 ? 1 : $diff->days;
        } else {
            throw new Exception("Check-out date cannot be before check-in date.");
        }

        $ratePerDay = 0; $additionalCharge = 0; $discountPercent = 0;
        $lRoomCapacity = strtolower($roomCapacity); $lRoomType = strtolower($roomType); $lPaymentType = strtolower($paymentType);

        
        if ($lRoomCapacity == "single" && $lRoomType == "regular") $ratePerDay = 100.0;
        else if ($lRoomCapacity == "single" && $lRoomType == "deluxe") $ratePerDay = 300.0;
        else if ($lRoomCapacity == "single" && $lRoomType == "suite") $ratePerDay = 500.0;
        else if ($lRoomCapacity == "double" && $lRoomType == "regular") $ratePerDay = 200.0;
        else if ($lRoomCapacity == "double" && $lRoomType == "deluxe") $ratePerDay = 500.0;
        else if ($lRoomCapacity == "double" && $lRoomType == "suite") $ratePerDay = 800.0;
        else if ($lRoomCapacity == "family" && $lRoomType == "regular") $ratePerDay = 500.0;
        else if ($lRoomCapacity == "family" && $lRoomType == "deluxe") $ratePerDay = 750.0;
        else if ($lRoomCapacity == "family" && $lRoomType == "suite") $ratePerDay = 1000.0;
        else { $ratePerDay = 0; }


        if($lPaymentType == "cheque") $additionalCharge = 0.05;
        else if($lPaymentType == "credit-card") $additionalCharge = 0.10;


        if($lPaymentType == "cash" && $numberOfDays >= 6) $discountPercent = 0.15;
        else if($lPaymentType == "cash" && $numberOfDays >= 3) $discountPercent = 0.10;


        $subTotalBeforeCharges = $ratePerDay * $numberOfDays;
        $discountAmount = $subTotalBeforeCharges * $discountPercent;
        $discountedTotal = $subTotalBeforeCharges - $discountAmount;
        $additionalChargeAmount = $discountedTotal * $additionalCharge;
        $totalBill = $discountedTotal + $additionalChargeAmount;

    } catch (Exception $e) {
        $calculation_error_message = "Calculation Error: " . $e->getMessage();
        
        $totalBill = null;
        $redirect_message = $calculation_error_message; 
    }
     
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];

    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);

        $sql = "UPDATE reservations SET
                    customer_name = :customer_name,
                    contact_number = :contact_number,
                    from_date = :from_date,
                    to_date = :to_date,
                    room_type = :room_type,
                    room_capacity = :room_capacity,
                    payment_type = :payment_type,
                    number_of_days = :number_of_days,
                    total_bill = :total_bill
                WHERE
                    id = :id";

        $stmt = $pdo->prepare($sql);

        // Bind Parameters
        $stmt->bindParam(':customer_name', $customerName);
        $stmt->bindParam(':contact_number', $contactNumber);
        $stmt->bindParam(':from_date', $fromDate);
        $stmt->bindParam(':to_date', $toDate);
        $stmt->bindParam(':room_type', $roomType);
        $stmt->bindParam(':room_capacity', $roomCapacity);
        $stmt->bindParam(':payment_type', $paymentType);
        $dbNumberOfDays = ($numberOfDays > 0) ? $numberOfDays : 0;
        $stmt->bindParam(':number_of_days', $dbNumberOfDays, PDO::PARAM_INT);
        $dbTotalBill = ($totalBill !== null) ? $totalBill : null;
        $stmt->bindParam(':total_bill', $dbTotalBill);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT); 

        
        if ($stmt->execute()) {
             
            if ($stmt->rowCount() > 0) {
                $redirect_status = 'update_success';
                $redirect_message = 'Reservation successfully updated!';
            } else {
                 $redirect_status = 'update_notice';
                 $redirect_message = 'Reservation data was not changed or ID not found.';
            }
        } else {
            $redirect_message = 'Database execute failed: Failed to update reservation.';
        }

    } catch (PDOException $e) {
        error_log("Database Update Error: " . $e->getMessage());
        $redirect_message = "Database error occurred during update.";

    } catch (Exception $e) {
        error_log("General Update Error: " . $e->getMessage());
        $redirect_message = "An error occurred during update processing.";
    }

} else {
    $redirect_message = 'Invalid request.';
}

header('Location: admin_dashboard.php?view=reservations&status=' . $redirect_status . '&msg=' . urlencode($redirect_message));
exit;
?>