<?php


namespace Alexrsk\S3DirectUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'resourceName'   => ['required', 'string'],
            'resourceId'     => ['required', 'integer'],
            'fieldName'      => ['required', 'string'],
            'fieldValue'     => ['filled', 'string'],
        ];
    }
}
