<?php


namespace Alexrsk\S3DirectUpload\Http;

use Alexrsk\S3DirectUpload\Http\Command\CommandInterface;
use Alexrsk\S3DirectUpload\Http\Command\DownloadCommand;
use Alexrsk\S3DirectUpload\Http\Command\UploadCommand;
use Alexrsk\S3DirectUpload\Http\Command\ChunkedUploadCommand;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Result;

class UploadController extends Controller
{
    const EXPIRE_TIME_MINUTES = 30;

    protected $debug = true;
    protected $client = null;

    public function __construct() {

        $this->debug = config('s3_direct_upload.debug');

        $params = [
            'version' => config('s3_direct_upload.version'),
            'bucket' => config('s3_direct_upload.bucket'),
            'region' => config('s3_direct_upload.region'),
            'use_path_style_endpoint' => config('s3_direct_upload.path_style'),
            'endpoint' => config('s3_direct_upload.endpoint'),
            //'debug' => config('s3_direct_upload.debug'),
            'credentials' => [
                'key' => config('s3_direct_upload.key_id'),
                'secret' => config('s3_direct_upload.key_secret'),
            ]
        ];
        if ($this->debug) {
            Log::channel('daily')->debug(json_encode($params, JSON_PRETTY_PRINT));
        }
        $this->client = new S3Client($params);

    }

    public function getChunkedUpload(Request $request) {

        $chunkedUploadParams = [
            'Bucket'         => config('s3_direct_upload.bucket'),
            'Key'            => $request->get('filename'),
            'ContentType'    => $request->get('type'),
            'ContentLength'  => $request->get('size')
        ];
        if ($this->debug) {
            Log::channel('daily')->debug(json_encode($chunkedUploadParams, JSON_PRETTY_PRINT));
        }
        $cmuResult = $this->client->createMultipartUpload($chunkedUploadParams);
        return response()->json(["uploadId" => $cmuResult->get('UploadId')], 201);
    }

    protected function getPresignedUrl(Request $request, CommandInterface $command, $multipartParams = [])
    {
        $validator = $command->getValidator($request);
        if (empty($validator)) {
            throw new \Exception("Can't instantiate validator");
        }

        if  ($validator->fails()) {
            $errorsStr  = implode(PHP_EOL, $validator->errors()->getMessages());
            throw new \Exception($errorsStr);
        }


        $commandName = $command->getS3Command();

        $key = $request->filename;

        $putObjParams = [
            'Bucket'         => config('s3_direct_upload.bucket'),
            'Key'            => $key,
            'ContentType'    => $request->get('type'),
            'ContentLength'  => $request->size
        ];

        if (!empty($request->get('upload_id'))) {
            $putObjParams['UploadId'] = $request->get('upload_id');
        }

        if (!empty($request->get('part_number'))) {
            $putObjParams['PartNumber'] = $request->get('part_number');
            $putObjParams['Body'] = '';
        }

        if ($this->debug) {
            Log::channel('daily')->debug(print_r($putObjParams, true));
        }

        $cmd = $this->client->getCommand($commandName, $putObjParams);
        
        $expiry = "+" . self::EXPIRE_TIME_MINUTES . " minutes";

        $psRequest = $this->client->createPresignedRequest($cmd, $expiry);

        $headers = array_filter(array_merge($psRequest->getHeaders(),
            [
                'Content-Type' => $request->get('type'),
                'Cache-Control'=> null,
                'Host'         => null
            ]
        ));

        $presignedUrl = (string)$psRequest->getUri();

        if ($this->debug) {
            Log::channel('daily')->debug($presignedUrl);
        }

        return response()->json([
            'status'  => 'success',
            'url'     => $presignedUrl,
            'headers' => $headers,
        ], 201);
    }

    public function presign(Request $request)
    {
        $command = new UploadCommand();
        try {
            return $this->getPresignedUrl($request, $command);
        }
        catch (\Exception $ex) {
            return response()->json(['status' => 'fail', 'error' => $ex->getMessage()], 422);
        }
    }

    public function presignChunked(Request $request)
    {
        $command = new ChunkedUploadCommand();
        try {
            return $this->getPresignedUrl($request, $command);
        }
        catch (\Exception $ex) {
            return response()->json(['status' => 'fail', 'error' => $ex->getMessage()], 422);
        }
    }

    public function download(Request $request)
    {
        $command = new DownloadCommand();
        try {
            return $this->getPresignedUrl($request, $command);
        }
        catch (\Exception $ex) {
            return response()->json(['status' => 'fail', 'error' => $ex->getMessage()], 422);
        }
    }

    public function completeMultipartUpload(Request $request) {
        $uploadId = $request->get('uploadId');
        $key      = $request->get('key');
        $parts    = array_filter($request->get('parts'), fn($el) => !empty($el));        
        foreach ($parts as &$part) {
            $part['PartNumber'] = (int) $part['PartNumber'];
        }
        usort($parts, fn($a, $b) => $a['PartNumber'] <=> $b['PartNumber']);
        $parts    = array_values($parts);

        if ($this->debug) {
            Log::channel('daily')->debug(json_encode($parts, JSON_PRETTY_PRINT));
        }

        try {
            $params = [
                'Bucket'    => config('s3_direct_upload.bucket'),
                'Key'       => $key,
                'UploadId'  => $uploadId,
                'MultipartUpload' => [
                    'Parts' => array_filter($parts)
                ]
            ];

            if ($this->debug) {
                Log::channel('daily')->debug(json_encode($params, JSON_PRETTY_PRINT));
            }

            $result = $this->client->completeMultipartUpload($params);
            return response()->json(['status' => 'success', 's3_data' =>$result], 200);
        }
        catch (\Exception $ex) {
            return response()->json(['status' => 'fail', 'error' => $ex->getMessage()], 422);
        }
    }
}
