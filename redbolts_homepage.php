<?php
session_start();
error_reporting(0); 

// Check if the user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: voters_login.php");
    exit();
}

// Check if the user's name is set in the session
if (!isset($_SESSION["user_name"])) {
    // Retrieve the name from the database
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "admin";

    // Create a database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Retrieve the name from the database using the user's ID
    $userId = $_SESSION["user_id"];
    $query = "SELECT name FROM registration WHERE id = $userId";
    $result = mysqli_query($conn, $query);

    // Check if the query was successful and retrieve the name
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'];
        $_SESSION["user_name"] = $name; // Store the name in the session for future use
    } else {
        $name = "Unknown";
    }

    mysqli_close($conn);
} else {
    // Retrieve the user's name from the session
    $name = $_SESSION["user_name"];
}

// Establish database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admin";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve the list of year levels
$query = "SELECT DISTINCT year_level FROM redbolts_candidates";
$result = $conn->query($query);

if (!$result) {
    die("Error retrieving year levels: " . $conn->error);
}

// Retrieve candidates based on the selected year level
if (isset($_GET['yearLevel'])) {
    $selectedYearLevel = $_GET['yearLevel'];
    $query = "SELECT * FROM redbolts_candidates WHERE year_level = '$selectedYearLevel'";
    $candidatesResult = $conn->query($query);

    if (!$candidatesResult) {
        die("Error retrieving candidates: " . $conn->error);
    }

    $candidates = $candidatesResult->fetch_all(MYSQLI_ASSOC);
}

// Retrieve the settings data
$query = "SELECT * FROM redbolts_settings";
$settingsResult = $conn->query($query);

if (!$settingsResult) {
    die("Error retrieving settings: " . $conn->error);
}

$settings = $settingsResult->fetch_assoc();

// Retrieve the distinct positions for the selected year level
if (isset($_GET['yearLevel'])) {
    $selectedYearLevel = $_GET['yearLevel'];
    $query = "SELECT DISTINCT position FROM redbolts_candidates WHERE year_level = '$selectedYearLevel'";
    $positionsResult = $conn->query($query);

    if (!$positionsResult) {
        die("Error retrieving positions: " . $conn->error);
    }

    $positionsResult->data_seek(0); // Reset the cursor position
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote'])) {
    $selectedCandidateId = $_POST['candidate_id'];

    // Check if the user has already voted for the selected position
    $userId = $_SESSION["user_id"];
    $selectedPosition = isset($_GET['position']) ? $_GET['position'] : "";
    $query = "SELECT * FROM votes WHERE user_id = $userId AND position = '$selectedPosition'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // User has already voted for this position
        echo "<script>alert('You have already voted for this position.');</script>";
    } else {
        // Update the candidate's score and record the vote
        $query = "UPDATE redbolts_candidates SET score = score + 1 WHERE id = $selectedCandidateId";
        $conn->query($query);

        $query = "INSERT INTO votes (user_id, position) VALUES ($userId, '$selectedPosition')";
        $conn->query($query);

        // Redirect to the same page to prevent resubmission
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Redbolts Candidates</title>
    <link href="https://fonts.googleapis.com/css?family=Exo+2&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Exo&display=swap" rel="stylesheet">
    <link href="globalcss.css" rel="stylesheet" />
    <link rel="stylesheet" href="redbolts_homepage.css">
</head>
<body>
    <div class="header">
		<p id="uni">University of the Philippines Visayas</p>
		<a href="HomePage.html"><img src="images/logo.png" id="iv"></a>
	</div>
    <div class="vote_body">
        <div class="sidebar">
            <div class="user">
                <img id="uimg" src="user.png">
                <h1>Welcome, <?php echo isset($name) ? htmlspecialchars($name) : "Unknown"; ?>!</h1>
            </div>
            <button id="CD" onclick="Candidates()">Candidates</button>
            <button id="VW" onclick="ViewScores()">View Scores</button>
            <script>
                function Candidates() {
                    location.replace("redbolts_homepage.php");
                }
                function ViewScores() {
                    location.replace("redbolts_voting_results.php");
                }
            </script>
        </div>
        <div class="vote_candidates">
            <div class="settings_display">
                <?php if (isset($settings)) { ?>
                    <p id="highlight">Election Details</p>
                    <p>Description: <?php echo isset($settings['description']) ? $settings['description'] : ""; ?></p>
                    <p>Duration: From <?php echo isset($settings['duration_from']) ? $settings['duration_from'] : ""; ?> to <?php echo isset($settings['duration_to']) ? $settings['duration_to'] : ""; ?></p>
                <?php } else { ?>
                    <p>No settings available</p>
                <?php } ?>
            </div>
            <div class="cand_yr">
                <form method="GET" action="">
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $yearLevel = $row['year_level'];
                        echo "<button type='submit' name='yearLevel' value='$yearLevel'>$yearLevel</button>";
                    }
                    ?>
                </form>
            </div>
            <div class="cand_pos">
                <?php if (isset($_GET['yearLevel']) && isset($positionsResult) && $positionsResult->num_rows > 0) { ?>
                    <h3>Select Position:</h3>
                    <form method="GET" action="">
                        <input type="hidden" name="yearLevel" value="<?php echo $selectedYearLevel; ?>">
                        <?php while ($row = $positionsResult->fetch_assoc()) { ?>
                            <?php $position = $row['position']; ?>
                            <button type="submit" name="position" value="<?php echo $position; ?>"><?php echo $position; ?></button>
                        <?php } ?>
                    </form>
                <?php } ?>
            </div>
            <div class="cand_name">
                <?php if (isset($_GET['position']) && isset($candidates)) { ?>
                    <?php $selectedPosition = $_GET['position']; ?>
                    <h3>Candidates for Position: <?php echo $selectedPosition; ?></h3>
                    <span>
                        <?php foreach ($candidates as $candidate) { ?>
                            <?php if ($candidate['position'] === $selectedPosition) { ?>
                                <p>
                                    <?php echo $candidate['name']; ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                        <button type="submit" name="vote">Vote</button>
                                    </form>
                                </p>
                            <?php } ?>
                    <?php } ?>
                    </span>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="footer">
		<p id="copy">Copyright © 2023. All Rights Reserved.</p>
	</div>
</body>
</html>
