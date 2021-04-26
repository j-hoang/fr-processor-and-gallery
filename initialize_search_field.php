<?php
require_once __DIR__ . '/database.php';

if (isset($_POST['collection'])) {

    $stmt = $connection->prepare("SELECT DISTINCT SUBSTRING(key_image, 1, CHAR_LENGTH(key_image) - 4) AS key_image_trimmed FROM face_search_breakdown WHERE collection_id = ? ORDER BY key_image_trimmed ASC");
    $stmt->bind_param('s', $_POST['collection']);
    $stmt->execute();
    $rc = $stmt->affected_rows;
    $result = $stmt->get_result();

    if (!$result) {
        echo $rc;
    } else {
        $json = array();
        while ($row = mysqli_fetch_array($result)) {
            $json[] = array(
                'name' => $row['key_image_trimmed']
            );
        }
        $jsonstring = json_encode($json);
        echo $jsonstring;
    }
}
