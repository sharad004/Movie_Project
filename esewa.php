<?php
// Function to generate the eSewa signature
function generateEsewaSignature($secretKey, $amount, $transactionUuid, $merchantCode) {
    // Prepare the signature string as required by eSewa
    $signatureString = "total_amount=$amount,transaction_uuid=$transactionUuid,product_code=$merchantCode";
    
    // Generate the HMAC SHA256 hash
    $hash = hash_hmac('sha256', $signatureString, $secretKey, true);
    
    // Convert the hash to Base64
    return base64_encode($hash);
}

// Payment initiation logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $amount = $_POST['amount'] ?? '';
    $productName = $_POST['productName'] ?? '';
    $transactionId = $_POST['transactionId'] ?? '';

    // Validate input
    if (empty($amount) || empty($productName) || empty($transactionId)) {
        die('Missing required parameters.');
    }

    // eSewa Configuration
    $merchantCode = "EPAYTEST"; // Replace with your actual merchant code
    $secretKey = "8gBm/:&EnhH.1/q"; // Replace with your actual secret key
    $successUrl = "http://yourdomain.com/payment-success"; // Replace with your success URL
    $failureUrl = "http://yourdomain.com/payment-failure"; // Replace with your failure URL
    $transactionUuid = uniqid("txn-", true); // Unique identifier for the transaction

    // Generate the signature
    $signature = generateEsewaSignature($secretKey, $amount, $transactionUuid, $merchantCode);

    // Prepare eSewa payment parameters
    $esewaConfig = [
        "amount" => $amount,
        "tax_amount" => "0",
        "total_amount" => $amount,
        "transaction_uuid" => $transactionUuid,
        "product_code" => $merchantCode,
        "product_service_charge" => "0",
        "product_delivery_charge" => "0",
        "success_url" => $successUrl,
        "failure_url" => $failureUrl,
        "signed_field_names" => "total_amount,transaction_uuid,product_code",
        "signature" => $signature,
    ];

    // eSewa Payment URL
    $paymentUrl = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";
    // $paymentUrl = "https://epay.esewa.com.np/api/epay/main/v2/form";

    // Redirect to eSewa payment gateway
    echo "<form id='esewaForm' method='POST' action='$paymentUrl'>";
    foreach ($esewaConfig as $key => $value) {
        echo "<input type='hidden' name='$key' value='$value'>";
    }
    echo "</form>";
    echo "<script>document.getElementById('esewaForm').submit();</script>";
} else {
    // Show a sample form to initiate payment
    ?>
    <div class="container">
        <h2 class="heading">Payment Details</h2>
        <form method="POST" action="" class="payment-form">
            <div class="form-group">
                <label for="amount1">Total:</label>
                <input type="text" id="amount1" name="amount" required readonly class="input-field"><br>
            </div>
            <div class="form-group">
                <label for="amount2">Sub-Total:</label>
                <input type="text" id="amount2" name="amount" required readonly class="input-field"><br>
            </div>
            <div style="display:none;">
                <div class="form-group">
                    <label for="productName">Product Name:</label>
                    <input type="text" value="prod" id="productName" name="productName" required class="input-field"><br>
                </div>
                <div class="form-group">
                    <label for="transactionId">Transaction ID:</label>
                    <input type="text" value="shard" id="transactionId" name="transactionId" required class="input-field"><br>
                </div>
            </div>
            <button type="submit" class="submit-btn">Pay with eSewa</button>
        </form>
        <div id="booked-seat" class="price-display">
            <p id="show"></p>
        </div>
    </div>
    <?php
}
?>

<script>
    let amnt1 = document.getElementById('amount1');
    let amnt2 = document.getElementById('amount2');
    let show = document.getElementById('show');
    
    window.onload = function() {
        // Get stored details from localStorage
        let finalDetailsFromLocalStorage = localStorage.getItem('details');
        
        if (finalDetailsFromLocalStorage) {
            // Parse the details data if it exists
            let parseData = JSON.parse(finalDetailsFromLocalStorage);
            console.log(parseData);

            // Display the seat information
            allSeatInfo(parseData.seatDetail);

            if (parseData && parseData.totalPrice) {
                // Update the amounts displayed on the page
                amnt1.value = parseData.totalPrice;
                amnt2.value = parseData.totalPrice;
            } else {
                // If totalPrice doesn't exist in the parsed data, show a default message
                amnt1.value = "No total price available.";
            }
        } else {
            // If no data found in localStorage, display a default message
            amnt1.value = "No booking details found.";
        }

        // Function to display all seat details
        function allSeatInfo(seats) {
            if (seats && Array.isArray(seats)) {
                let seatList = "<ul>";
                seats.forEach(seat => {
                    seatList += `<li>${seat}</li>`;
                });
                seatList += "</ul>";
                show.innerHTML = seatList;
            } else {
                show.innerHTML = "<p>No seats booked.</p>";
            }
        }
    };
</script>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f7f7f7;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 100%;
        max-width: 600px;
        margin: 50px auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .heading {
        text-align: center;
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        font-size: 16px;
        color: #555;
    }

    .input-field {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 5px;
        background-color: #f9f9f9;
    }

    .submit-btn {
        width: 100%;
        padding: 15px;
        background-color: #007bff;
        color: white;
        font-size: 18px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #0056b3;
    }

    .price-display {
        text-align: center;
        margin-top: 20px;
        font-size: 18px;
        color: #333;
        font-weight: bold;
    }

    .form-group input[readonly] {
        background-color: #e9ecef;
    }
</style>
