<?php
session_start();

if (!isset($_SESSION['id'])) {
    echo "Student ID not found in session.";
    exit;
}

$student_id = $_SESSION['id'];

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "hostel";

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the name and email from the userregistrations table based on the student_id
$sql = "SELECT firstName, email FROM userregistration WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($student_name, $student_email);
$stmt->fetch();
$stmt->close();

if (!$student_name || !$student_email) {
    echo "No student found with the given ID.";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>

    <!-- Bootstrap CSS -->
    <link href="../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for the form -->
    <style>
        body {
            background: url('payment2.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
            color: #fff;
        }

        .payment-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            backdrop-filter: blur(10px);
        }

        h2 {
            color: #005691;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        label {
            color: #005691;
            font-weight: bold;
        }

        .form-control {
            border-radius: 10px;
            background-color: #e6f2f9;
            color: #005691;
            border: none;
        }

        .form-control::placeholder {
            color: #81a8c3;
        }

        .btn-success {
            background: #0072b5;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
        }

        .btn-success:hover {
            background: #004c7f;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .text-center {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <h2>Payment Information</h2>
        <form id="paymentForm" method="POST">
            <!-- Hidden Fields for ID, Name, Email -->
            <input type="hidden" id="student_id" name="student_id" value="<?php echo $student_id; ?>">
            <input type="hidden" id="student_name" name="student_name" value="<?php echo $student_name; ?>">
            <input type="hidden" id="student_email" name="student_email" value="<?php echo $student_email; ?>">

            <div class="form-group">
                <label for="year">Year</label>
                <input type="text" class="form-control" id="year" name="year" placeholder="Enter Year" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="text" class="form-control" id="amount" name="amount" placeholder="Enter Amount" required>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Any comments..."></textarea>
            </div>
            <div class="text-center">
                <button type="button" id="PayNow" class="btn btn-success btn-lg">Proceed to Pay</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    
    <!-- Razorpay Checkout -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
    jQuery(document).ready(function($) {
        $('#PayNow').click(function(e) {
            e.preventDefault(); // Prevent form submission

            // Collect form data using IDs
            var student_id = $('#student_id').val();
            var student_name = $('#student_name').val();
            var student_email = $('#student_email').val();
            var year = $('#year').val();
            var amount = $('#amount').val();
            var remarks = $('#remarks').val();

            // Basic validation before AJAX call
            if (!year || !amount) {
                alert("Please fill in all required fields.");
                return;
            }

            // Send form data via AJAX to the backend (PHP)
            $.ajax({
                type: 'POST',
                url: 'submitpayment.php',
                data: {
                    student_id: student_id,
                    student_name: student_name,
                    student_email: student_email,
                    year: year,
                    amount: amount,
                    remarks: remarks,
                    action: 'payOrder'
                },
                dataType: 'json',
                success: function(data) {
                    if (data.res === 'success') {
                        var orderID = data.order_number;
                        var options = {
                            "key": data.razorpay_key, // Razorpay key
                            "amount": data.userData.amount * 100, // Amount in paise
                            "currency": "INR",
                            "name": data.userData.name,
                            "description": data.userData.description,
                            "order_id": data.userData.rpay_order_id,
                            "handler": function (response) {
                                window.location.replace("payment-success.php?oid=" + orderID + "&rp_payment_id=" + response.razorpay_payment_id + "&rp_signature=" + response.razorpay_signature);
                            },
                            "prefill": {
                                "name": data.userData.name,
                                "email": data.userData.email
                            },
                            "theme": {
                                "color": "#3399cc"
                            }
                        };

                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else {
                        alert("Error: " + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Payment initiation failed. Please try again.');
                }
            });
        });
    });
    </script>

</body>
</html>
