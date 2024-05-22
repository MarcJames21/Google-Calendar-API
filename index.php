<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

// Adjust the scopes to include the necessary scopes for calendar events and calendar metadata
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);

if (isset($_GET['code'])) {
    $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    header('Location: ' . filter_var(GOOGLE_REDIRECT_URI, FILTER_SANITIZE_URL));
}

if (!isset($_SESSION['access_token'])) {
    $authUrl = $client->createAuthUrl();
    echo "<a href='$authUrl'>Connect to Google Calendar</a>";
    exit;
}

$client->setAccessToken($_SESSION['access_token']);

// Function to format date and time to RFC3339 format (required by Google Calendar API)
function formatDateTime($date, $time, $timezone) {
    $dateTime = new DateTime($date . ' ' . $time, new DateTimeZone($timezone));
    return $dateTime->format(\DateTime::RFC3339);
}

// Logout functionality
if (isset($_POST['logout'])) {
    unset($_SESSION['access_token']);
    session_destroy();
    $authUrl = $client->createAuthUrl();
    echo "<script>window.location.href='$authUrl';</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment System</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Optional: Custom styles for form */
        body {
            padding: 20px;
        }
        .container {
            width: 50%;
        }
        .form-group {
            margin-bottom: 20px;
            margin-right: 10px;
            margin-left: 10px;
        }
        .container2 {
            display: flex;
            flex-direction: row;
            justify-content: space-evenly;
            align-items: center;
        }
        .form-group {
            width: 100%;
        }
        .btn {
            float: right;
            margin: 0 0 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4 text-center">Appointment System</h1>
        <form method="POST" action="">
            <div class="container2">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" class="form-control" id="date" name="date">
                </div>
                <div class="form-group">
                    <label for="start_time">Start Time:</label>
                    <input type="time" class="form-control" id="start_time" name="start_time">
                </div>
                <div class="form-group">
                    <label for="end_time">End Time:</label>
                    <input type="time" class="form-control" id="end_time" name="end_time">
                </div>
            </div>
            <div class="form-group">
                <label for="summary">Appointment Details:</label>
                <textarea class="form-control" id="summary" name="summary" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create Appointment</button>
            <button type="submit" class="btn btn-danger" name="logout">Logout</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['logout'])) {
                unset($_SESSION['access_token']);
                session_destroy();
                $authUrl = $client->createAuthUrl();
                echo "<script>window.location.href='$authUrl';</script>";
                exit;
            }
        
            $service = new Google_Service_Calendar($client);
        
            // Format start and end dateTime in RFC3339 format
            $startDateTime = formatDateTime($_POST['date'], $_POST['start_time'], 'GMT+8');
            $endDateTime = formatDateTime($_POST['date'], $_POST['end_time'], 'GMT+8');
        
            $event = new Google_Service_Calendar_Event([
                'summary' => $_POST['summary'],
                'description' => $_POST['summary'], // Using summary as description for simplicity
                'start' => [
                    'dateTime' => $startDateTime,
                    'timeZone' => 'GMT+8',
                ],
                'end' => [
                    'dateTime' => $endDateTime,
                    'timeZone' => 'GMT+8',
                ],
            ]);
        
            try {
                $event = $service->events->insert('primary', $event);
                printf('Event created: <a href="%s" target="_blank">%s</a>', $event->htmlLink, $event->summary);
            } catch (Exception $e) {
                echo 'Error adding event: ' . $e->getMessage();
            }
        }
        ?>
    </div>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
