<?php


namespace Alexrsk\S3DirectUpload\Http\Command;

use Alexrsk\S3DirectUpload\Http\Command\CommandInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator ;

class DownloadCommand implements CommandInterface {
    protected string $command = 'getObject';

    public function getS3Command() : string {
        return $this->command;
    }

    public function getValidator(Request $request) : \Illuminate\Validation\Validator {
        return Validator::make($request->all(), [
            'key' => ['required', 'string']
        ],
        ['key.required' => 'Provide S3 key for downloading'],    
    );
    }
}