<?php
// dbconnection.php
$servername = "localhost"; // Your database server
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "ticket"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize the booking_id variable
$booking_id = null;

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $showtime = $_POST['showtime']; // Showtime selected by the user
    $seats = $_POST['seats']; // List of selected seats
    $user_id = 1; // Assuming user_id is 1 (replace with actual user data)

    // Insert the booking details into the database if no booking_id exists
    if (!$booking_id) {
        $sql = "INSERT INTO bookings (showtime, seats, user_id) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            // Bind the parameters and execute
            $stmt->bind_param("ssi", $showtime, $seats, $user_id);
            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id; // Store the generated booking ID
                echo "Booking successful!";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // Update the booking if booking_id exists
        $sql = "UPDATE bookings SET showtime = ?, seats = ? WHERE booking_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $showtime, $seats, $booking_id);
            if ($stmt->execute()) {
                echo "Booking updated!";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle cancel request
if (isset($_GET['cancel_booking'])) {
    $booking_id = $_GET['cancel_booking'];

    // Delete the booking from the database
    $sql = "DELETE FROM bookings WHERE booking_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            echo "Booking canceled!";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Seat Booking</title>
    <style>
        /* Add some basic styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
        .seats-container {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .seat {
            width: 40px;
            height: 40px;
            background-color: #dcdcdc;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
            user-select: none;
        }
        .seat.selected {
            background-color: #4caf50;
        }
        .seat.booked {
            background-color: #f44336;
            cursor: not-allowed;
        }
        #cancel-booking-container {
            text-align: center;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
        }
        .total-div{
            display:flex;
            align-items:center;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Book Your Movie Seats</h1>

    <form id="booking-form">
        <div class="form-group">
            <label for="showtime">Choose Showtime:</label>
            <select id="showtime" name="showtime">
                <option value="1pm">1:00 PM</option>
                <option value="4pm">4:00 PM</option>
                <option value="7pm">7:00 PM</option>
            </select>
        </div>
        <div class="form-group">
            <label for="seats">Number of Seats:</label>
            <input type="number" id="seats" name="seats" min="1" max="5" value="1" required>
        </div>
        <div class="form-group">
            <input type="submit" value="Book Seats">
        </div>
    </form>

    <div class="seats-container" id="seats-container">
        <!-- Seats will be dynamically created here -->
    </div>

    <div id="cancel-booking-container" style="display: none;">
        <button id="cancel-booking-btn">Cancel Booking</button>
    </div>

    <div class="total-div">
        <h2>Total:</h2> 
        <h2 id="amount">0</h2>
    </div>
   

    <p class="footer">* Only available seats can be selected.</p>
</div>

<script>
    // Define the price per seat
    const pricePerSeat = 200;

    // Generate seats dynamically
    const seatsContainer = document.getElementById('seats-container');
    const totalSeats = 56; // Total number of seats in the cinema

    // Generate seat elements
    for (let i = 1; i <= totalSeats; i++) {
        const seat = document.createElement('div');
        seat.classList.add('seat');
        seat.id = 'seat-' + i;
        seat.setAttribute('data-seat', i);
        seat.textContent = i;
        seat.addEventListener('click', toggleSeatSelection);
        seatsContainer.appendChild(seat);
    }

    // Seat selection logic
    let selectedSeats = [];
    let bookedSeats = [];

    function toggleSeatSelection(event) {
        const seat = event.target;
        const seatNumber = seat.getAttribute('data-seat');
        if (seat.classList.contains('booked')) {
            alert('This seat is already booked!');
            return;
        }
        if (seat.classList.contains('selected')) {
            seat.classList.remove('selected');
            selectedSeats = selectedSeats.filter(num => num !== seatNumber);
        } else {
            seat.classList.add('selected');
            selectedSeats.push(seatNumber);
        }
        updateSeatsInfo();
    }

    function updateSeatsInfo() {
        const seatCountInput = document.getElementById('seats');
        seatCountInput.value = selectedSeats.length;

        // Update the total amount
        const totalAmount = selectedSeats.length * pricePerSeat;
        document.getElementById('amount').textContent = totalAmount;
    }

    // Booking form submission logic
    const bookingForm = document.getElementById('booking-form');
bookingForm.addEventListener('submit', function(event) {
    event.preventDefault();

    if (selectedSeats.length === 0) {
        alert('Please select at least one seat.');
        return;
    }

    const showtime = document.getElementById('showtime').value;
    const seatNumbers = selectedSeats.join(', ');

    // Create a new FormData object to send the form data
    const formData = new FormData();
    formData.append('showtime', showtime);
    formData.append('seats', seatNumbers);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert("Seat Booked"); // Display success or error message from the server
        location.assign('esewa.php')
        // Mark selected seats as booked
        selectedSeats.forEach(seatNumber => {
            const seat = document.querySelector(`.seat[data-seat='${seatNumber}']`);
            seat.classList.add('booked');
        });

        // Update the bookedSeats array
        bookedSeats = [...selectedSeats];

        // Store the booked seats and total price in localStorage
        const totalAmount = selectedSeats.length * pricePerSeat;
        localStorage.setItem('bookedSeats', JSON.stringify(selectedSeats)); // Store the booked seats
        localStorage.setItem('totalPrice', totalAmount); // Store the total price

        // Show the Cancel Booking button
        document.getElementById('cancel-booking-container').style.display = 'block';

        // Disable booking form after submission
        bookingForm.querySelector('input[type="submit"]').disabled = true;

        // Clear selected seats
        selectedSeats = [];
        updateSeatsInfo();
    })
    .catch(error => {
        console.error('Error booking seats:', error);
    });
});

    // Cancel booking logic
    const cancelBookingBtn = document.getElementById('cancel-booking-btn');
    cancelBookingBtn.addEventListener('click', function() {
        // Remove "booked" class from all booked seats
        bookedSeats.forEach(seatNumber => {
            const seat = document.querySelector(`.seat[data-seat='${seatNumber}']`);
            seat.classList.remove('booked');
        });

        // Keep the seats in the selectedSeats array and allow reselection
        selectedSeats = [...bookedSeats];

        // Update the number of seats input field to reflect remaining selected seats
        updateSeatsInfo();

        // Show the Cancel Booking button
        document.getElementById('cancel-booking-container').style.display = 'none';

        // Enable the booking form again
        bookingForm.querySelector('input[type="submit"]').disabled = false;

        alert('Booking canceled. You can now reselect your seats.');
    });
</script>

</body>
</html>
