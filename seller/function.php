<?php
// functions.php

function logActivity($db, $user_id, $activity) {
    // Prepare the SQL query
    $query = "INSERT INTO activity_logs (user_id, action) VALUES (?, ?)";
    $stmt = $db->prepare($query);

    if ($stmt) {
        // Bind parameters and execute the query
        $stmt->bind_param("is", $user_id, $activity);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare activity log query: " . $db->error);
    }
}
?>