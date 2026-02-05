<?php

namespace App\Http\Controllers;

use App\Models\Languages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class LanguageController extends Controller
{

    public function __construct()
    {
    }

    public function getLanguages()
    {
        $languages = Languages::orderBy('languages.order')->get();
        return $languages;
    }

    public function downloadFile($fileName)
    {
        if (Storage::disk('i18n')->exists($fileName)) {
            $file_contents = Storage::disk('i18n')->get($fileName);
            $fileType = Storage::mimeType($fileName);

            $fileExtension = explode('.', $fileName);
            return response($file_contents)
                ->header('Cache-Control', 'no-cache private')
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', $fileType)
                ->header('Content-length', strlen($file_contents))
                ->header('Content-Disposition', 'attachment; filename=' . $fileName . '.' . $fileExtension[1])
                ->header('Content-Transfer-Encoding', 'binary');
        } else {
            return [];
        }
    }

    public function defaultlanguage()
    {
        $fileName = 'default.json';
        if (Storage::disk('i18n')->exists($fileName)) {
            $file_contents = Storage::disk('i18n')->get($fileName);
            return $file_contents;
        } else {
            return [];
        }
    }

    public function getFileContentById($id)
    {
        $language = Languages::findOrFail($id);;
        $code = $language->code;
        $fileName =  $code . '.json';
        if (Storage::disk('i18n')->exists($fileName)) {
            $file_contents = Storage::disk('i18n')->get($fileName);
            $language->codes = $file_contents;
            return $language;
        } else {
            return [];
        }
    }

    function saveLanguage(Request $request)
    {
        $id = $request['id'];
        $validator = Validator::make($request->all(), [
            'name' => "required|unique:languages,name,$id,id,deleted_at,NULL",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 409);
        }

        $validator = Validator::make($request->all(), [
            'code' => "required|unique:languages,code,$id,id,deleted_at,NULL",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 409);
        }

        $data = $request['codes'];
        Storage::disk('i18n')->put($request['code'] . '.json', json_encode($data));
        if ($id) {
            $attributes = $request->all();
            $language = Languages::findOrFail($id);
            if ($request['isLanguageImageUpload'] == 'true' && $request['languageImgSrc']) {
                $url = $this->saveImage($request['languageImgSrc']);
                $attributes['imageUrl'] = $url;
            }
            $language->fill($attributes)->save();
        } else {
            $url = $this->saveImage($request['languageImgSrc']);
            Languages::create([
                'imageUrl' => $url,
                'code' => $request['code'],
                'name' => $request['name'],
                'isrtl' => $request['isrtl'],
                'order' => $request['order']
            ]);
        }
        response()->json([], 201);
    }

    public function deleteLanguage($id)
    {
        $language = Languages::findOrFail($id);
        $language->delete();;
        return response()->json([], 200);
    }

    private function saveImage($image_64)
    {
        try {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];


            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $image = str_replace($replace, '', $image_64);

            $image = str_replace(' ', '+', $image);

            $destinationPath = public_path() . '/images/flags/';

            $imageName = Uuid::uuid4() . '.' . $extension ?? 'png';

            file_put_contents($destinationPath . $imageName,  base64_decode($image));
            return 'images/flags/' . $imageName;
        } catch (\Exception $e) {
            return '';
        }
    }
}
