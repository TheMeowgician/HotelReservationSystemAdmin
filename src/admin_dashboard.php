<?php

$db_host = 'localhost';
$db_name = 'hotel_reservation_db';
$db_user = 'root';
$db_pass = '';

$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard'; 

$page_title = "Admin Dashboard";
$reservations = []; 
$reservation_to_edit = null; 
$db_error = null;   

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    if ($view === 'reservations' || (isset($_GET['status']) && $view !== 'edit_reservation')) { 
        $page_title = "Reservation List";
        $sql = "SELECT * FROM reservations ORDER BY reservation_timestamp DESC";
        $stmt = $pdo->query($sql);
        $reservations = $stmt->fetchAll();
        
        $view = 'reservations';

    } elseif ($view === 'edit_reservation' && isset($_GET['id'])) {
        $page_title = "Edit Reservation";
        $edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($edit_id) {
            $sql = "SELECT * FROM reservations WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
            $stmt->execute();
            $reservation_to_edit = $stmt->fetch();

            if (!$reservation_to_edit) {
                $db_error = "Reservation with ID " . htmlspecialchars($edit_id) . " not found.";
                
                header('Location: admin_dashboard.php?view=reservations&status=edit_error&msg=' . urlencode($db_error));
                exit;
            }
        } else {
            $db_error = "Invalid ID provided for editing.";
            
            header('Location: admin_dashboard.php?view=reservations&status=edit_error&msg=' . urlencode($db_error));
            exit;
        }
    }
    

} catch (PDOException $e) {
    $db_error = "Database Error: Could not connect or fetch data. " . $e->getMessage();
    error_log($db_error); 
    $db_error = "Could not retrieve data from the database."; 
    $view = 'dashboard'; 
    $page_title = "Admin Dashboard";

} catch (Exception $e) {
     $db_error = "An unexpected error occurred.";
     error_log("General Error in Admin Dashboard: " . $e->getMessage());
     $view = 'dashboard'; 
     $page_title = "Admin Dashboard";
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link href="./output.css" rel="stylesheet">
    <link href="./global.css" rel="stylesheet">
    <style>
        aside nav a.active { background-color: #4a5568; color: white; }
        select {
            background-image: url('data:image/svg+xml;utf8,<svg fill="%236B7280" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5H7z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.25em;
            -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 2.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-gray-800 text-gray-100 p-4 flex flex-col flex-shrink-0">
            <div class="mb-8 text-center">
                <a href="admin_dashboard.php" class="text-2xl font-[PoppinsBold]">Admin Panel</a>
            </div>
            <nav class="flex-grow">
                <ul>
                    <li class="mb-2">
                        <a href="admin_dashboard.php?view=dashboard"
                           class="flex items-center px-3 py-2 rounded hover:bg-gray-700 transition-colors <?php echo ($view === 'dashboard' ? 'active' : ''); ?>">
                            <span class="ml-2">Dashboard</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="admin_dashboard.php?view=reservations"
                           class="flex items-center px-3 py-2 rounded hover:bg-gray-700 transition-colors <?php echo ($view === 'reservations' || $view === 'edit_reservation' ? 'active' : ''); ?>">
                            <span class="ml-2">Reservations</span>
                        </a>
                    </li>
                    </ul>
            </nav>
            <div class="mt-auto">
                 <a href="admin_logout.php" class="flex items-center px-3 py-2 rounded hover:bg-red-600 bg-red-700 text-white transition-colors">
                      <span class="ml-2">Logout</span>
                  </a>
            </div>
        </aside>
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <h1 class="text-2xl md:text-3xl font-[PoppinsBold] text-gray-800 mb-6"><?php echo htmlspecialchars($page_title); ?></h1>

             <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                <?php
                    $status = $_GET['status'];
                    $message = urldecode($_GET['msg']);
                    $alert_class = 'bg-red-100 border-red-400 text-red-700'; // Default error
                    if ($status === 'update_success' || $status === 'delete_success') {
                        $alert_class = 'bg-green-100 border-green-400 text-green-700'; 
                    } elseif ($status === 'update_notice') {
                         $alert_class = 'bg-yellow-100 border-yellow-400 text-yellow-700'; 
                    }
                ?>
                <div class="<?php echo $alert_class; ?> border px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                         <svg class="fill-current h-6 w-6 <?php echo str_replace(['bg-', 'border-'], 'text-', $alert_class); ?>" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </span>
                </div>
            <?php endif; ?>
            <?php 
                if (!empty($db_error) && !isset($_GET['status'])):
             ?>
                 <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                     <strong class="font-bold">Error!</strong>
                     <span class="block sm:inline"><?php echo htmlspecialchars($db_error); ?></span>
                 </div>
             <?php endif; ?>


            <?php // --- Main Content Switching --- ?>

            <?php if ($view === 'dashboard'): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4">Welcome, Admin!</h2>
                    <p class="text-gray-600">Select an option from the sidebar.</p>
                </div>

            <?php elseif ($view === 'reservations'): ?>
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px]">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Check-in</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Check-out</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Days</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room Type</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Capacity</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Total Bill</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reserved On</th>
                                    <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (isset($reservations) && count($reservations) > 0): ?>
                                    <?php foreach ($reservations as $res): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['id']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['customer_name']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['contact_number']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['from_date']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['to_date']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800 text-center"><?php echo htmlspecialchars($res['number_of_days']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['room_type']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['room_capacity']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($res['payment_type']); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-800 text-right"><?php echo '$' . number_format($res['total_bill'] ?? 0, 2); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($res['reservation_timestamp']))); ?></td>
                                            <td class="p-3 whitespace-nowrap text-sm font-medium">
                                                <a href="admin_dashboard.php?view=edit_reservation&id=<?php echo htmlspecialchars($res['id']); ?>"
                                                   class="inline-block px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded shadow-sm hover:bg-yellow-600 transition duration-150 ease-in-out mr-2">
                                                   Update
                                                </a>
                                                <form action="delete_reservation.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete reservation ID <?php echo htmlspecialchars($res['id']); ?>? This action cannot be undone.');">
                                                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($res['id']); ?>">
                                                    <button type="submit"
                                                            class="inline-block px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded shadow-sm hover:bg-red-700 transition duration-150 ease-in-out">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" class="px-4 py-4 text-center text-gray-500">No reservations found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

             <?php elseif ($view === 'edit_reservation' && $reservation_to_edit): ?>
                 <div class="max-w-3xl mx-auto bg-white p-6 md:p-8 shadow-lg rounded-lg border border-gray-200">
                      <h2 class="text-xl font-semibold mb-6">Editing Reservation ID: <?php echo htmlspecialchars($reservation_to_edit['id']); ?></h2>
                      <form action="update_reservation.php" method="POST">
                          <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation_to_edit['id']); ?>">

                          <div class="mb-8 border-b border-gray-300 pb-6">
                              <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Information</h3>
                              <div class="mb-4 md:flex md:items-center md:gap-4">
                                  <label for="customerName" class="block md:w-1/3 text-gray-700 font-semibold mb-1 md:mb-0 pr-4">Customer Name:</label>
                                  <input type="text" id="customerName" name="customerName" required value="<?php echo htmlspecialchars($reservation_to_edit['customer_name']); ?>" class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                              </div>
                              <div class="mb-4 md:flex md:items-center md:gap-4">
                                  <label for="contactNumber" class="block md:w-1/3 text-gray-700 font-semibold mb-1 md:mb-0 pr-4">Contact Number:</label>
                                  <input type="tel" id="contactNumber" name="contactNumber" required value="<?php echo htmlspecialchars($reservation_to_edit['contact_number']); ?>" class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                              </div>
                          </div>

                          <div class="mb-8 border-b border-gray-300 pb-6">
                               <h3 class="text-lg font-semibold text-gray-800 mb-4">Reservation Details</h3>
                               <div class="mb-4 md:flex md:gap-4">
                                   <div class="flex-1 min-w-[150px] mb-4 md:mb-0">
                                       <label for="fromDate" class="block text-gray-700 font-semibold mb-1">From:</label>
                                       <input type="date" id="fromDate" name="fromDate" required value="<?php echo htmlspecialchars($reservation_to_edit['from_date']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                   </div>
                                   <div class="flex-1 min-w-[150px]">
                                       <label for="toDate" class="block text-gray-700 font-semibold mb-1">To:</label>
                                       <input type="date" id="toDate" name="toDate" required value="<?php echo htmlspecialchars($reservation_to_edit['to_date']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                   </div>
                               </div>
                               <?php
                                   function is_selected($current_value, $option_value) { return strtolower($current_value ?? '') === strtolower($option_value) ? ' selected' : ''; }
                               ?>
                               <div class="mb-4 md:flex md:items-center md:gap-4">
                                   <label for="roomType" class="block md:w-1/3 text-gray-700 font-semibold mb-1 md:mb-0 pr-4">Room Type:</label>
                                   <select id="roomType" name="roomType" class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white appearance-none">
                                       <option value="Suite"<?php echo is_selected($reservation_to_edit['room_type'], 'Suite'); ?>>Suite</option>
                                       <option value="Deluxe"<?php echo is_selected($reservation_to_edit['room_type'], 'Deluxe'); ?>>Deluxe</option>
                                       <option value="Regular"<?php echo is_selected($reservation_to_edit['room_type'], 'Regular'); ?>>Regular</option>
                                   </select>
                               </div>
                               <div class="mb-4 md:flex md:items-center md:gap-4">
                                   <label for="roomCapacity" class="block md:w-1/3 text-gray-700 font-semibold mb-1 md:mb-0 pr-4">Room Capacity:</label>
                                   <select id="roomCapacity" name="roomCapacity" class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white appearance-none">
                                       <option value="Family"<?php echo is_selected($reservation_to_edit['room_capacity'], 'Family'); ?>>Family</option>
                                       <option value="Double"<?php echo is_selected($reservation_to_edit['room_capacity'], 'Double'); ?>>Double</option>
                                       <option value="Single"<?php echo is_selected($reservation_to_edit['room_capacity'], 'Single'); ?>>Single</option>
                                   </select>
                               </div>
                               <div class="mb-4 md:flex md:items-center md:gap-4">
                                   <label for="paymentType" class="block md:w-1/3 text-gray-700 font-semibold mb-1 md:mb-0 pr-4">Payment Type:</label>
                                   <select id="paymentType" name="paymentType" class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white appearance-none">
                                       <option value="Cash"<?php echo is_selected($reservation_to_edit['payment_type'], 'Cash'); ?>>Cash</option>
                                       <option value="Cheque"<?php echo is_selected($reservation_to_edit['payment_type'], 'Cheque'); ?>>Cheque</option>
                                       <option value="Credit-Card"<?php echo is_selected($reservation_to_edit['payment_type'], 'Credit-Card'); ?>>Credit Card</option>
                                   </select>
                               </div>
                           </div>

                           <div class="flex justify-end gap-4 mt-6">
                                <a href="admin_dashboard.php?view=reservations" class="inline-block px-6 py-2 text-base font-semibold rounded-md shadow-sm transition duration-200 border border-gray-300 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Cancel</a>
                                <button type="submit" class="inline-block px-6 py-2 text-base font-semibold rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">Save Changes</button>
                           </div>
                      </form>
                 </div>

            <?php else: ?>
                 <div class="bg-white p-6 rounded-lg shadow-md">
                     <p class="text-gray-600">Please select a valid view from the sidebar or check previous errors.</p>
                 </div>
            <?php endif; ?>
             <?php // --- End Main Content Switching --- ?>

        </main>
        </div>

</body>
</html>