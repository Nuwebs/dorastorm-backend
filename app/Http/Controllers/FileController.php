<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    protected array $fileValidationRules;

    // This is the name of the key that should contain the file to upload.
    protected string $requestFileKeyName;

    public function __construct()
    {
        $this->fileValidationRules = [
            'disk' => Rule::in(['local', 'public'])
        ];
        $this->requestFileKeyName = 'file';
    }

    /**
     * This function takes the query param "path" as the path of the file 
     * you are trying to retrieve. If the file exists it is returned.
     * 
     * The files are retrieved from the local disk. If you need to look in
     * another disk (but the public one, you shouldn't be using this method
     * for that) you may have to modify this method.
     * 
     * WARNING: This method allows to any user to retrieve files
     * from the different directories inside the disk's root folder.
     * You may use policies and different methods, middleware and routes to protect them.
     * By default, this method is used by the /file/retrieve route, which is 
     * allowed to only auth users.
     * 
     * WARNING: You shouldn't use this method to retrieve videos.
     */
    public function retrieve(Request $request)
    {
        $path = $request->query('path');
        if (!Storage::exists($path))
            abort(404);
        return Storage::response($path, 'test');
    }

    public function uploadImage(Request $request)
    {
        $rule = 'image|max:' . config('filesystems.max_file_size.image');
        return $this->upload($request, [$this->requestFileKeyName => $rule], 'images');
    }

    public function uploadDocument(Request $request)
    {
        $ms_office_mimes = 'doc,dot,docx,dotx,docm,dotm,xls,xlt,xla,xlsx,xltx,xlsm,xltm,xlam,xlsb,ppt,pot,pps,ppa,pptx,potx,ppsx,ppam,pptm,potm,ppsm,mdb';
        $open_office = 'odt,ods,odp';
        $other_docs = 'pdf,txt,rtf,tex,wpd';
        $mimes = "$ms_office_mimes,$open_office,$other_docs";
        $rule = "file|mimes:$mimes|max:" . config('filesystems.max_file_size.document');
        return $this->upload($request, [$this->requestFileKeyName => $rule], 'documents');
    }

    protected function upload(Request $request, array $additionalRules, string $folder = 'others')
    {
        $validation = array_merge($this->fileValidationRules, $additionalRules);
        $data = $request->validate($validation);
        $path = $request->file($this->requestFileKeyName)->store($folder, $data['disk'] ?? 'local');

        if ($path === false) {
            response()->json([
                'errors' => [$this->requestFileKeyName => 'Rejected']
            ], 422);
        }

        return response()->json([
            'path' => $path,
        ], 201);
    }
}