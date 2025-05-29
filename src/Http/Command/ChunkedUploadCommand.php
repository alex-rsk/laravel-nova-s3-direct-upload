<?php


namespace Alexrsk\S3DirectUpload\Http\Command;

use Alexrsk\S3DirectUpload\Http\Command\CommandInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChunkedUploadCommand implements CommandInterface {

    protected string $command = 'UploadPart';

    public function getS3Command() : string {
        return $this->command;
    }

    public function getValidator(Request $request) : \Illuminate\Validation\Validator{
        return Validator::make($request->all(), [
            'filename' => ['required', 'string'],
            'size'     => ['required', 'integer'],
            'type'     => ['required', 'string'],           
            'part_number'     => ['required', 'integer'],
            'upload_id'       => ['required', 'string'],
            //'body'            => ['', 'string'],
        ], [            
            'part_number.required'  => 'Part number is required',
            'upload_id.required'    => 'Upload id is required',
        ]);
    }
}