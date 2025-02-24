<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TrashBinController extends Controller
{
    public function index()
    {
        $models = $this->getSoftDeletableModels();
        $deletedData = [];

        foreach ($models as $model) {
            $modelClass = "App\\Models\\$model";
            if (class_exists($modelClass)) {
                $deletedData[$model] = $modelClass::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
            }
        }

        return view('admin.trash_bin.index', compact('deletedData'));
    }

    private function getSoftDeletableModels()
    {
        $modelsPath = app_path('Models');
        $modelFiles = File::allFiles($modelsPath);
        $softDeletableModels = [];

        foreach ($modelFiles as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $modelClass = "App\\Models\\$modelName";

            if (class_exists($modelClass) && method_exists($modelClass, 'bootSoftDeletes')) {
                $softDeletableModels[] = $modelName;
            }
        }

        return $softDeletableModels;
    }

    public function restore(Request $request)
    {
        $model = "App\\Models\\" . $request->model;
        $id = $request->id;

        if (!class_exists($model)) {
            return back()->with('error', 'Model not found.');
        }

        $record = $model::onlyTrashed()->find($id);

        if (!$record) {
            return back()->with('error', 'Record not found.');
        }

        $record->restore();
        return back()->with('success', 'Record restored successfully.');
    }

    public function forceDelete(Request $request)
    {
        $model = "App\\Models\\" . $request->model;
        $id = $request->id;

        if (!class_exists($model)) {
            return back()->with('error', 'Model not found.');
        }

        $record = $model::onlyTrashed()->find($id);

        if (!$record) {
            return back()->with('error', 'Record not found.');
        }

        $record->forceDelete();
        return back()->with('success', 'Record deleted permanently.');
    }

}
