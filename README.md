# Event Photos Facial Recognition Processor and Gallery

A PHP script that uses AWS Facial Rekognition API to index faces found from a collection of event photos, then tag the photos (or give names to the faces) using a set of key images.

## Setup

AWS SDK for PHP is required. To get this, visit https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/getting-started_installation.html
For this project, 'Installing by Using the Zip file' was done.

In fr_processor.php, line 20-21, AWS API key and secret variables need to be defined.

The following database tables are required. Table create statements are in fr-database.sql:

- collections table keeps a record of AWS collections created
- event_photos table keeps a record of event photos where faces have been indexed
- faces_searched table keeps a record of key images that have been used to search across the collection
- face_search_breakdown keeps a record of event photos tagged with faces/names (found using the key images)

Update the database connection parameters accordingly in database.php

A collection name needs to be created. This is used to group photos for facial recognition processing and can be likened
to a collection from a photo event. The one used for this project is 'celebrities'. See AWS Developer Guide
for guidelines regarding the collection name (https://docs.aws.amazon.com/rekognition/latest/dg/API_CreateCollection.html).
For this project, alphanumeric characters, underscores and dashes are acceptable.

Once a collection name has been decided on, please create a folder with this name within fr_images folder. Afterwards,
create a folder named 'key' within the collection folder.

    .
    └── fr_images                   # Folder containing collections of photo events used for facial recognition processing
        └── collection_name         # Collection folder: This is where event photos are placed.
            └── key                 # Key folder: This is where key images are placed.

In fr_processor.php, line 29, set $collectionId value to the collection name decided on (i.e. $collectionId = 'celebrities';)

This project requires that all facial recognition images be JPEGs and end with the '.jpg' file extension. Key image files
should be a single portrait/head-shot and the key image file name should be the name of the person that corresponds (i.e.
Robert Downey Jr.jpg). The key image filename/person's name will be referenced later for the event photos gallery (i.e. gallery.php).
For this project, alphanumeric characters, spaces and dashes are acceptable for the key image filename. For this project,
file sizes are supported up to 5MB, per AWS Developer guidlines. Please
see AWS Developer Guide for guidelines regarding IndexFaces (https://docs.aws.amazon.com/rekognition/latest/dg/API_IndexFaces.html)
and SearchFacesByImage (https://docs.aws.amazon.com/rekognition/latest/dg/API_SearchFacesByImage.html).

## Usage

Open fr_processor.php in a browser. This will go through the following process:

- Check if a collection exists. If not, then create a collection on AWS servers.
- Check if key images exists for the collection.
- Check if event photos exists for the collection.
- Index event photos that have not been indexed. Store pieces of response data from IndexFaces in database.
- Search across indexed photos with each key image, if not already done. Store pieces of response data from SearchFacesByImage in database.

If running fr_processor.php without a secure HTTPS connection and an SSL certificate problem occurs, uncomment lines 24 i.e.

```php
    // For testing only
    //,     'http'    => ['verify' => false]
```

NOTE: This is for testing only and should not be done in production since it is not secure!

Due to potential PHP memory limit when running IndexFaces and SearchFacesByImage (i.e. Fatal error: Allowed memory size of
268435456 bytes exhausted (tried to allocate 4179048 bytes) in /aws/Aws/Api/Serializer/JsonBody.php on line 44), processing has been
limited to 7 images at a time for this project. Number and size of images are a factor in this memory limit error. This limit can be
modified on line 183 i.e. $indexKeyLimit = 7. Because of this, the HTML form is auto-submited via jQuery until all the images have
been processed. The entirety of the FR process is stored in a log file within the aws_response_logs folder.

After fr_processor.php has completed, face tagging can be demonstrated by visiting gallery.php i.e. http://localhost/gallery.php?collection=celebrities

Modify the collection URL query string according to collection name defined previously.

## Creative Commons Attribution for images used for this project

"Robert Downey, Jr." by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Tony Stark - Robert Downey Jr" by Justin in SD is licensed with CC BY-NC-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-nc-sa/2.0/

"Mrs H & Robert Downey Jr." by Cormac Heron is licensed with CC BY 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by/2.0/

"Chris Hemsworth" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Chris Hemsworth" by Eva Rinaldi Celebrity Photographer is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"chris hemsworth ok" by Mario A. P. is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Jeremy Renner" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Jeremy Renner" by Eva Rinaldi Celebrity Photographer is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Chris Evans & Aaron Taylor-Johnson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Chris Evans, Scarlett Johansson & Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/
2.0/

"Chris Evans, Scarlett Johansson, Samuel L. Jackson & Sebastian Stan" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/
licenses/by-sa/2.0/

"Scarlett Johansson_004" by GabboT is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Sophie Cookson, Colin Firth, Sofia Boutella, Samuel L. Jackson & Taron Egerton" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://
creativecommons.org/licenses/by-sa/2.0/

"Cobie Smulders & Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Samuel L. Jackson" by Nathan Congleton is licensed with CC BY-NC-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-nc-sa/2.0/

"Robert Downey, Jr., Jeremy Renner, Mark Ruffalo, Chris Hemsworth, Cobie Smulders, Samuel L. Jackson, Chris Evans, Aaron Taylor-Johnson, Paul Bettany & James Spader" by Gage Skidmore
is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Aaron Taylor-Johnson & Paul Bettany" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/

"Paul Bettany & James Spader" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/
