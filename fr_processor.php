<?php

require_once __DIR__ . '/aws/aws-autoloader.php';
require_once __DIR__ . '/database.php';

function awsResponseLog($log)
{
    // Write to txt log
    $log  = "[" . date("F j, Y, g:i a") . "]" . $log . PHP_EOL;
    // Save string to log, use FILE_APPEND to append.
    file_put_contents('./aws_response_logs/log_' . date("Ymd") . '.log', $log, FILE_APPEND);
}


$client = new Aws\Rekognition\RekognitionClient([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        // Testing key   
        'key'    => '',
        'secret' => '',
    ]
    // For testing only
    //,     'http'    => ['verify' => false]
]);


// BEGIN - Collection
$collectionId = 'celebrities';
$errorMessage = '';
$stopAutoSubmit = 'yes';

$awsResponseMsg = "<h3>Facial Recognition Processing Status</h3><br /><h4>1) Creating Collection for \"$collectionId\"</h4>";
awsResponseLog($awsResponseMsg);
$status = $awsResponseMsg;


// Check collections table for collection
$query = "SELECT collection_id FROM collections WHERE collection_id = \"$collectionId\"";
if (!$result = mysqli_query($connection, $query))
    die('Error: unable to search for collection in DB');


// Collection does not exist in collections table
if (!mysqli_fetch_array($result)) {

    try {
        // Delete Collection - Enable this if you want to remove the collection and rerun the 
        // FR process again. Make sure to remove all database records related to the collection,
        // across all tables, otherwise this will not be executed.
        // $result = $client->deleteCollection([
        //     'CollectionId' => $collectionId,
        // ]);

        // Create Collection
        $result = $client->createCollection([
            'CollectionId' => $collectionId,
        ]);
    } catch (Exception $ex) {
        $errorMessage =  $ex->getMessage();
    }


    // Error generated when calling createCollection
    if (!empty($errorMessage)) {
        awsResponseLog("[ERROR]" . $errorMessage);

        if (!(strpos($errorMessage, "ResourceAlreadyExistsException") === FALSE)) {
            // Collection already exists
            $awsResponseMsg = "<p><i>The collection ID \"$collectionId\" already exists on AWS</i></p>";
            awsResponseLog($awsResponseMsg);
            $status .= $awsResponseMsg;
        } else {
            // Other Collection creation error        
            $awsResponseMsg =  "<p>AWS error: " .  $ex->getMessage() . "</p>";
            awsResponseLog($awsResponseMsg);
            $status .= $awsResponseMsg;
        }

        goto aws_error;
    } else {
        $resultArray = $result->toArray();
        $resultJSON = json_encode($resultArray);
        awsResponseLog($resultJSON);

        // Collection created successfully
        if ($resultArray['StatusCode'] == "200") {
            $awsResponseMsg = "<p>\"" . $collectionId . "\" collection created successfully on AWS</p>";
            awsResponseLog($awsResponseMsg);
            $status .= $awsResponseMsg;
        }

        // Add collectionID to fr_collections table
        $stmt = $connection->prepare("INSERT INTO collections (collection_id) VALUES ( ? )");
        if (false === $stmt) {
            die('Error: unable to add collection to DB');
        } else {
            $stmt->bind_param('s', $collectionId);
            $stmt->execute();

            $awsResponseMsg = "<p>\"" . $collectionId . "\" collection added to DB</p>";
            awsResponseLog($awsResponseMsg);
            $status .= $awsResponseMsg;
        }
    }
} else {
    $awsResponseMsg = "<p>The collection ID \"$collectionId\" already exists in DB</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
}
// END - Collection


// BEGIN - Get key image file count
$keyFolderPath = "./fr_images/$collectionId/key/";
$files = glob($keyFolderPath . '*.{jpg,JPG}', GLOB_BRACE);
$awsResponseMsg = "<h4>2) Checking Key Image count for \"$collectionId\" collection</h4>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;
$filecount = 0;

if ($files) {
    $filecount = $totalKeyCount = count($files);
    if ($filecount > 0) {
        $awsResponseMsg = "<p>Number of Key Images found: $filecount</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    }
}

if ($filecount == 0) {
    $awsResponseMsg = "<p><i>There are no key images. Facial Recognition cannot continue. Please check: $keyFolderPath</i></p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
}
// END - Get key image file count


// BEGIN - Check Event Photo file count
$photoFolderPath = "./fr_images/$collectionId/";
$awsResponseMsg = "<h4>3) Checking Event Photo file count for \"$collectionId\" collection</h4>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;
$filecount = 0;
$files = glob($photoFolderPath . '*.{jpg,JPG}', GLOB_BRACE);
if ($files) {
    $filecount = count($files);
    if ($filecount > 0) {
        $awsResponseMsg = "<p>Number of Event Photo files found: $filecount</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    }
}

if ($filecount == 0) {
    $awsResponseMsg = "<p><i>There are no Event Photo files. Facial Recognition cannot continue. Please check: $photoFolderPath</i></p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
}
// END - Check Event Photo file count


// BEGIN - Index Event Photos
$awsResponseMsg = "<h4>4) Index Event Photos for \"$collectionId\" collection</h4>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;

// Store the micro time so that we know when our script started to run.
$executionStartTime = microtime(true);
$timezone = date_default_timezone_get();
$awsResponseMsg = "<p>Indexing start time: " . date('m/d/Y h:i:s a', time()) . " ($timezone timezone)</p>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;

$errorMessage = "";


// Get total event photos already indexed. Also setup stopIndex, a slight increment of 
// totalEventPhotosIndexed that determines when event photo indexing should stop. This is to prevent PHP fatal memory issue.
$query = "SELECT count(*) FROM event_photos WHERE collection_id = \"$collectionId\"";
if (!($result = mysqli_query($connection, $query)))
    die('Error: unable to check DB for total count of event photos indexed');
$queryData = mysqli_fetch_row($result);
$totalEventPhotosIndexed = $queryData[0];
$indexKeyLimit = 7;
$stopIndex = $totalEventPhotosIndexed + $indexKeyLimit; // stopIndex set to 7+ $totalEventPhotosIndexed. It can be increased, but no more than 30 or risk of PHP fatal memory error


$photoFolderPath = "./fr_images/$collectionId/";
$handle = opendir($photoFolderPath);
$photoCount = 0;
$photoIndexedCount = 0;


// loop through each event photo files in event_photos folder
while ($file = readdir($handle)) {

    $fileExtPosition = stripos($file, ".JPG");
    if ($file !== '.' && $file !== '..' && !($fileExtPosition === FALSE)) {

        // Check event photos table to see if event photo file was indexed
        $query = "
                SELECT ID FROM event_photos WHERE 
                        collection_id = \"$collectionId\"
                    AND event_image = \"$file\"
                    ";

        if (!$result = mysqli_query($connection, $query))
            die('Error: unable to check DB for event photos searched');



        // no record in database that event photo file was indexed, so proceed with indexing
        if (!mysqli_fetch_array($result)) {

            try {
                //Index event photos
                $result = $client->indexFaces([
                    'CollectionId' => $collectionId,
                    'ExternalImageId' => $file,
                    'Image' => [
                        'Bytes' => file_get_contents($photoFolderPath . $file),
                    ],
                ]);
            } catch (Exception $ex) {
                $errorMessage =  $ex->getMessage();
            }

            // Error generated when calling indexFaces
            if (!empty($errorMessage)) {
                $awsResponseMsg = "<p>[ERROR] $errorMessage</p>";
                awsResponseLog($awsResponseMsg);
                $status .= $awsResponseMsg;
                goto aws_error;
            } else { // indexFaces generated no error
                $photoIndexedCount++;
                $resultArray = $result->toArray();
                $resultJSON = json_encode($resultArray);

                // indexFaces processed successfully
                if ($resultArray["@metadata"]["statusCode"] == "200") {
                    $awsResponseMsg = "<p>$file successfully indexed to collection \"$collectionId\"</p>";
                    awsResponseLog($awsResponseMsg);
                    $status .= $awsResponseMsg;
                }

                // Number of faces detected
                $faceRecords = count($resultArray["FaceRecords"]);
                // Number of unindexed faces
                $unindexedFaces = count($resultArray["UnindexedFaces"]);

                $faceId = "";
                // If photo is of one detected face and possibly a portrait, initialize face ID
                if ($faceRecords == 1)
                    $faceId = $resultArray["FaceRecords"][0]["Face"]["FaceId"];

                // Add Index photo image info to event_photos table
                $query = "
                                INSERT INTO event_photos 
                                    (collection_id
                                    , event_image
                                    , face_records
                                    , unindexed_faces
                                    , face_id)
                                VALUES 
                                    (\"$collectionId\"
                                    , \"$file\"
                                    , \"$faceRecords\"
                                    , \"$unindexedFaces\"                    
                                    , \"$faceId\")
                                ";


                if (!mysqli_query($connection, $query))
                    die('Error: unable to add indexed event photo to DB');
            }
        }

        $errorMessage = "";
        $photoCount++;
    }


    // LIMIT INDEXING PROCESSING COUNT
    if ($photoIndexedCount == $indexKeyLimit) {
        $awsResponseMsg = "INDEXING PROCESSING LIMITED UP TO $indexKeyLimit IMAGES ONLY.<br />";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;

        //Auto resubmit form to complete indexing process. This is a work around for PHP fatal memory issue
        $stopAutoSubmit = "no";
        //$stage = "Indexing Photos";

        break;
    }
}

// At the end of your code, compare the current microtime to the microtime that we stored at the beginning of the script.
$executionEndTime = microtime(true);

//The result will be in seconds and milliseconds.
$seconds = $executionEndTime - $executionStartTime;
$minutes = $seconds / 60;

//Print it out
$timezone = date_default_timezone_get();
$awsResponseMsg = "<p>Indexing stop time: " . date('m/d/Y h:i:s a', time()) . " ($timezone timezone)</p>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;
$awsResponseMsg = "<p>This script took $seconds seconds ($minutes minutes) to execute</p>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;

$awsResponseMsg = "<p>Number of Event Photos last indexed: $photoIndexedCount</p>";
awsResponseLog($awsResponseMsg);
$status .= $awsResponseMsg;

if ($photoCount == 0) {
    $awsResponseMsg = "<p>No event photos to index. Please check folder $photoFolderPath</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
} else {
    $awsResponseMsg = "<p>$photoCount event photos indexed from $photoFolderPath</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
}


// Purposely interrupting process to prevent fatal memory issues
if ($photoCount == $filecount) {
    //Auto resubmit form to complete indexing process. This is a work around for PHP fatal memory issue
    $stopAutoSubmit = "no";
    // Proceed to Face Searching by Key Images only when all Event Photos have been indexed
    $stage = "Face Searching by Key Images";
}
// END - Index Event Photos


// BEGIN - searchFacesByImage across collection
if (!empty($_POST['stage'])) { // Once Event Photos have all been indexed will $_POST['stage'] have a value

    $awsResponseMsg = "<h4>5) Search across indexed photos with each key image for \"$collectionId\" collection</h4>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;

    // Store the micro time so that we know when our script started to run.
    $executionStartTime = microtime(true);

    $timezone = date_default_timezone_get();
    $awsResponseMsg = "<p>Search Faces start time: " . date('m/d/Y h:i:s a', time()) . " ($timezone timezone)</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;

    $errorMessage = "";


    // Get total faces already searched. Also setup stopSearchFace, a slight increment of 
    // totalFacesSearched that determines when face searching should stop. This is to prevent PHP fatal memory issue
    $query = "SELECT count(*) FROM faces_searched WHERE collection_id = \"$collectionId\"";
    if (!($result = mysqli_query($connection, $query)))
        die('Error: unable to find total face search count for collection in DB');
    $queryData = mysqli_fetch_row($result);
    $totalFacesSearched = $queryData[0];
    $stopFaceSearch = $totalFacesSearched + $indexKeyLimit; // stopFaceSearch set to 7+ totalFacesSearched. It can be increased, but no more than 30 or risk of PHP fatal memory error

    $keyFolderPath = "./fr_images/$collectionId/key/";
    $handle = opendir($keyFolderPath);
    $keyImageCount = 0;
    $keyImageSearchedCount = 0;
    while ($file = readdir($handle)) {
        $fileExtPosition = stripos($file, ".JPG");
        if ($file !== '.' && $file !== '..' && !($fileExtPosition === FALSE)) {

            // Check faces_searched table for specific face search
            $query = "
                SELECT ID FROM faces_searched WHERE 
                        collection_id = \"$collectionId\"
                    AND key_image = \"$file\"
                    ";

            if (!$result = mysqli_query($connection, $query)) {
                die('ERROR: unable to find specific face search in DB');
            }

            // face search does not exist in faces_searched table
            if (!mysqli_fetch_array($result)) {

                try {
                    // Use searchFacesByImage and search across collection for face matches                        
                    $result = $client->searchFacesByImage([
                        'CollectionId' => $collectionId,
                        'FaceMatchThreshold' => 90,
                        'Image' => [
                            'Bytes' => file_get_contents($keyFolderPath . $file),
                        ],
                    ]);
                } catch (Exception $ex) {
                    $errorMessage =  $ex->getMessage();
                }

                // Error generated when calling searchFacesByImage
                if (!empty($errorMessage)) {
                    $awsResponseMsg = "<p>[ERROR] $errorMessage</p>";
                    awsResponseLog($awsResponseMsg);
                    $status .= $awsResponseMsg;
                    goto aws_error;
                } else { // searchFacesByImage generated no error
                    $keyImageSearchedCount++;
                    $resultArray = $result->toArray();
                    $resultJSON = json_encode($resultArray);

                    // searchFacesByImage processed successfully
                    if ($resultArray["@metadata"]['statusCode'] == "200") {
                        $awsResponseMsg = "<p>$keyFolderPath$file searched across Collection \"$collectionId\"</p>";
                        awsResponseLog($awsResponseMsg);
                        $status .= $awsResponseMsg;
                    }

                    $faceMatchesArray = $resultArray['FaceMatches'];
                    $faceMatchesCount = count($faceMatchesArray);

                    // Record face search into faces_searched table
                    $query = "
                            INSERT INTO faces_searched
                                (collection_id
                                , key_image
                                , face_matches)
                            VALUES 
                                (\"$collectionId\"
                                , \"$file\"
                                , \"$faceMatchesCount\")
                            ";

                    if (!mysqli_query($connection, $query)) {
                        die('ERROR: unable to record of face search in DB');
                    }

                    foreach ($faceMatchesArray as $faceMatch) {
                        $photoId = $faceMatch['Face']['ExternalImageId'];

                        // Add searchFaces info to face_search_breakdown table
                        $query = "
                                INSERT INTO face_search_breakdown 
                                    (collection_id
                                    , event_image
                                    , key_image)
                                VALUES 
                                    (\"$collectionId\"
                                    , \"$photoId\"
                                    , \"$file\")
                                ";

                        if (!mysqli_query($connection, $query)) {
                            die("Script Error: $query");
                        }
                    }
                }


                $errorMessage = "";
            }

            $keyImageCount++;

            if ($keyImageCount == $stopFaceSearch) {

                $awsResponseMsg = "<p>Key images used for search limited up to $keyImageCount images only.</p>";
                awsResponseLog($awsResponseMsg);
                $status .= $awsResponseMsg;

                //Auto resubmit form to complete face search process. This is a work around for PHP fatal memory issue
                $stopAutoSubmit = "no";
                $stage = "Face Searching by Key Images";

                break;
            }
        }
    }

    //At the end of your code, compare the current microtime to the microtime that we stored at the beginning of the script.
    $executionEndTime = microtime(true);

    //The result will be in seconds and milliseconds.
    $seconds = $executionEndTime - $executionStartTime;

    //Print it out
    $timezone = date_default_timezone_get();
    $awsResponseMsg = "<p>Search Faces stop time: " . date('m/d/Y h:i:s a', time()) . " ($timezone timezone)</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
    $awsResponseMsg = "<p>This script took $seconds seconds ($minutes minutes) to execute</p>";
    awsResponseLog($awsResponseMsg);
    $status .= $awsResponseMsg;
    if ($keyImageSearchedCount == 0) {
        $awsResponseMsg = "<p>All key images have previously been used for search.</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    } else {
        $awsResponseMsg = "<p>New key images used for FR search: $keyImageSearchedCount</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    }

    if ($keyImageCount == 0) {
        $awsResponseMsg = "<p>No key images to check. Please check folder $keyFolderPath</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    } else {
        $awsResponseMsg = "<p>Key images used for FR searching: $keyImageCount</p>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    }

    if ($keyImageCount == $totalKeyCount) {
        // FR process complete. Stop auto-resubmitting form
        $stopAutoSubmit = "yes";

        $awsResponseMsg = "<h4>Facial Recognition Processing Completed</h4>";
        awsResponseLog($awsResponseMsg);
        $status .= $awsResponseMsg;
    }
}
// END - searchFacesByImage across collection

aws_error:

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>Facial Recognition</title>
    <!-- BOOTSTRAP 4  -->
    <link rel="stylesheet" href="https://bootswatch.com/4/lux/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- toastr notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
</head>

<body>
    <div class="container mt-4">
        <?php echo $status; ?>

        <p><span id="seconds" class="text-danger"></span></p>

        <form method="POST" name="myForm">
            <input type="HIDDEN" name="stopAutoSubmit" value="<?php echo $stopAutoSubmit; ?>">
            <input type="HIDDEN" name="stage" value="<?php echo $stage; ?>">
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <!-- jQuery Validate Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
    <!-- toastr notifications -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        window.onload = function() {
            if ($('input[name=stopAutoSubmit]').val() == "no") {
                var counter = 5;
                var interval = setInterval(function() {
                    counter--;
                    $("#seconds").text("CONTINUING PROCESSING IN: " + counter + " SECONDS");
                    if (counter == 0) {
                        $("#seconds").text("CONTINUING PROCESSING IN: PLEASE WAIT. PROCESSING.");
                        redirect();
                        clearInterval(interval);
                    }
                }, 1000);
            }

        };

        window.scrollTo(0, document.body.scrollHeight);

        function redirect() {
            document.myForm.submit();
        }
    </script>

</body>

</html>