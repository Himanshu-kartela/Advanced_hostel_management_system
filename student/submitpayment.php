<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, PATCH, DELETE');
header("Content-Type: application/json");
header("Accept: application/json");
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Access-Control-Allow-Methods, Content-Type');

// Database connection
$host = "localhost"; // Replace with your host
$username = "root";  // Replace with your database username
$password = "";      // Replace with your database password
$dbname = "hostel";  // Replace with your database name

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['res' => 'error', 'message' => "Connection failed: " . $conn->connect_error]));
}

if (isset($_POST['action']) && $_POST['action'] == 'payOrder') {
    // Razorpay API keys
    $razorpay_mode = 'test'; // 'test' or 'live'
    $razorpay_test_key = 'rzp_test_R3LMpv8JDHOKYe'; // Your Test Key
    $razorpay_test_secret_key = 'NdkjD2kvU5oUmqdcks7R7C7s'; // Your Test Secret Key

    $razorpay_live_key = 'Your_Live_Key';
    $razorpay_live_secret_key = 'Your_Live_Secret_Key';

    // Use test keys if in test mode
    if ($razorpay_mode == 'test') {
        $razorpay_key = $razorpay_test_key;
        $authAPIkey = "Basic " . base64_encode($razorpay_test_key . ":" . $razorpay_test_secret_key);
    } else {
        $razorpay_key = $razorpay_live_key;
        $authAPIkey = "Basic " . base64_encode($razorpay_live_key . ":" . $razorpay_live_secret_key);
    }

    // Get form data
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $student_email = $_POST['student_email'];
    $year = $_POST['year'];
    $amount = $_POST['amount'];
    $remarks = $_POST['remarks'];

    // Insert into database (without Razorpay order_id)
    $stmt = $conn->prepare("INSERT INTO payments (student_id, student_name, student_email, year, amount, remarks, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("issdss", $student_id, $student_name, $student_email, $year, $amount, $remarks);

    if ($stmt->execute()) {
        // Fetch the payment ID of the newly inserted record
        $payment_id = $conn->insert_id;
        $stmt->close();

        // Initiate Razorpay order creation
        $url = "https://api.razorpay.com/v1/orders";
        $postData = json_encode([
            "amount" => $amount * 100, // Amount in paise
            "currency" => "INR",
            "payment_capture" => 1
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $authAPIkey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        // Check if the Razorpay order creation was successful
        if (isset($data['id'])) {
            // Razorpay order ID
            $razorpay_order_id = $data['id'];

            // Update the payment record in the database with Razorpay order ID
            $update_stmt = $conn->prepare("UPDATE payments SET razorpay_order_id = ? WHERE id = ?");
            $update_stmt->bind_param("si", $razorpay_order_id, $payment_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Return response with Razorpay order details
            echo json_encode([
                'res' => 'success',
                'razorpay_key' => $razorpay_key,
                'userData' => [
                    'name' => $student_name,
                    'email' => $student_email,
                    'amount' => $amount,
                    'rpay_order_id' => $razorpay_order_id
                ]
            ]);
        } else {
            // If Razorpay order creation failed, return error
            echo json_encode(['res' => 'error', 'message' => 'Razorpay order creation failed.']);
        }
    } else {
        echo json_encode(['res' => 'error', 'message' => 'Payment could not be processed.']);
    }

    $conn->close();
} else {
    echo json_encode(['res' => 'error', 'message' => 'Invalid request.']);
}
?>
