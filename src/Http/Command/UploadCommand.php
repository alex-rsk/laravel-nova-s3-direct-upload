<?php


namespace Alexrsk\S3DirectUpload\Http\Command;

use Alexrsk\S3DirectUpload\Http\Command\CommandInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UploadCommand implements CommandInterface {

    protected string $command = 'putObject';

    public function getS3Command() : string {
        return $this->command;
    }

    public function getValidator(Request $request) : \Illuminate\Validation\Validator{
        return Validator::make($request->all(), [
            'filename' => ['required', 'string'],
            'size'     => ['required', 'integer'],
            'type'     => ['required', 'string'],
            'part_number' => ['filled', 'integer'],
            'upload_id'   => ['filled', 'string'],

        ], [
            'filename.required' => 'Filename is required',
            'size.required'     => 'Size is required',
            'type.required'     => 'Type is required',
        ]);
    }
}