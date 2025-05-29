<?php


namespace Alexrsk\S3DirectUpload\Http\Command;

use Illuminate\Http\Request;

interface CommandInterface {

    public function getValidator(Request $request) : \Illuminate\Validation\Validator;

    public function getS3Command(): string;

}