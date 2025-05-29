# Laravel Nova component for direct uploading to S3-compatible storage

## Capabilities

Direct chunked upload to S3, without your back-end interaction, changing target field value.

## Setup

+ Ensure that you have prepared S3 storage.

You need to set essential rules for particular S3 bucket, in order to upload files directly from browser to S3 through presigned URLs.
The next example is suitable for Backblaze storage. Modify it for your preferred S3 Storage.

```
[
  {
    "corsRuleName": "anyOrigin",
    "allowedOrigins": [
      "*"
    ],
    "allowedHeaders": [
      "*"
    ],
    "allowedOperations": [
      "b2_download_file_by_id",
      "b2_download_file_by_name",
      "b2_upload_file",
      "b2_upload_part",
      "s3_put",
      "s3_get",
      "s3_head"      
    ],
    "exposeHeaders": ["ETag"],
    "maxAgeSeconds": 3600
  }
]
```
<sub> Note: I faced problems with setting up Openstack S3 storage, because of it's "peculiar" implementation. </sub>

+ Install package:
 ` composer require ` 

+ After installation:
run
` php artisan vendor:publish --tag=s3-uploader-config --force `

New configuration file `s3_direct_upload.php` will appear in your `config` folder. 
Set credentials for S3 storage there.

+ Configuration
```
 [
    'key_id'     => env('S3_ACCESS_KEY_ID', ''),
    'key_secret' => env('S3_ACCESS_KEY_SECRET', ''),
    'region'     => env('S3_DEFAULT_REGION', ''),
    'bucket'     => env('S3_BUCKET', ''),
    'endpoint'   => env('S3_ENDPOINT', ''),
    'path_style' => env('S3_USE_PATH_STYLE_ENDPOINT', false),
    'url_prefix' => env('S3_URL', ''), 
    'version'    =>  '2006-03-01',
    'debug'      => true,
]
```

