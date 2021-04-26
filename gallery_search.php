<?php

require_once __DIR__ . '/database.php';

$search = $_POST['search'];

if (!empty($search)) {
    // prepare and bind
    $stmt = $connection->prepare("SELECT event_image FROM face_search_breakdown WHERE key_image LIKE CONCAT(\"%\", ?, \"%\")");
    $stmt->bind_param('s', $search);

    // execute
    $rc = $stmt->execute();
    $result = $stmt->get_result();

    if (false === $rc) {
        die('Query Failed.');
    }

    $json = array();
    while ($row = mysqli_fetch_array($result)) {
        $json[] = array(
            'event_image' => $row['event_image']
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
