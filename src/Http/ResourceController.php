<?php


namespace Alexrsk\S3DirectUpload\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Alexrsk\S3DirectUpload\Http\Requests\ResourceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Aws\S3\S3Client;

class ResourceController extends Controller
{

    protected function guessModel(string $resourceName)
    {
        $resourceName = Str::singular($resourceName);
        $studlyName = str_replace('-', '', ucwords($resourceName, '-'));
        $resourceClass = '\\App\\Nova\\' . $studlyName;
        return (class_exists($resourceClass)) ? $resourceClass::$model : null;
    }

    protected function getModel(string $resourceName, int $resourceId) : Model
    {
        $modelClass = $this->guessModel($resourceName);
        if (is_null($modelClass)) {
            throw new \Exception("Model not found, consider to set class explicitly with 'withModel' method");
        }
        return $modelClass::findOrFail(intval($resourceId));
    }

    public function setResource(ResourceRequest $request)
    {
        try {
            $model = $this->getModel($request->resourceName, $request->resourceId);
            $model->{$request->fieldName} = config('s3_direct_upload.url_prefix').'/'.config('s3_direct_upload.bucket').'/'.$request->fieldValue;
            $model->save();
            return response()->json(['success' => true], 200);
        }
        catch (\Exception $ex) {         
            return response()->json(['error' => $ex->getMessage()], 422);
        }
    }

    public function getResource(string $resourceName, int $resourceId, string $fieldName)
    {
        try {
            $model = $this->getModel($resourceName, $resourceId);
            return response()->json(['success' => true, 'data' => $model?->toArray() ?? []], 200);
        }
        catch (\Exception $ex) {         
            return response()->json(['error' => $ex->getMessage()], 422);
        }
    }
}
