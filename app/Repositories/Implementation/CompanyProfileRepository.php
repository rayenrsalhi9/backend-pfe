<?php

namespace App\Repositories\Implementation;

use App\Models\CompanyProfiles;
use App\Models\Languages;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\CompanyProfileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CompanyProfileRepository extends BaseRepository implements CompanyProfileRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return CompanyProfiles::class;
    }

    public function getCompanyProfile()
    {
        try {
            $languages = Languages::select(['languages.code', 'languages.name', 'languages.imageUrl', 'languages.order'])->orderBy('languages.order')->get();

            $model = $this->model->first();
            if ($model == null) {
                $user = Auth::user();
                if ($user) {
                    $model = CompanyProfiles::create([
                        'logoUrl' =>  '',
                        'title' => 'Document Management System',
                    ]);
                    $model->save();
                    $result = $this->parseResult($model);
                    $result->languages = $languages;
                    return $result;
                }
                return [
                    'logoUrl' =>  '',
                    'title' => 'Document Management System',
                    'languages' => $languages
                ];
            }

            $model->languages = $languages;
            return $model;
        } catch (\Throwable $th) {
            return [
                'logoUrl' =>  '',
                'title' => 'Document Management System',
                'languages' => $languages
            ];
        }
    }


    public function updateCompanyProfile($request)
    {
        $languages = Languages::select(['languages.code', 'languages.name', 'languages.imageUrl', 'languages.order'])->get();
        $model = $this->model->first();
        $logo = '';
        $banner = '';
        if ($request['imageData']) {
            $logo = $this->saveCompanyProfileImage($request['imageData']);
        } else {
            $logo = $model->logoUrl;
        }

        if ($request['bannerData']) {
            $banner = $this->saveCompanyProfileImage($request['bannerData']);
        } else {
            $banner = $model->bannerUrl;
        }

        if ($model == null) {
            $model = $this->model->newInstance($request);
            $model->logoUrl = $logo;
            $model->bannerUrl = $banner;
            $model->address = $request['address'];
            $model->phoneNumber = $request['phoneNumber'];
            $model->email = $request['email'];
            $model->linkedin = $request['linkedin'];
            $model->facebook = $request['facebook'];
            $model->twitter = $request['twitter'];
            $model->save();
            $result = $this->parseResult($model);
            $result->languages = $languages;
            return $result;
        } else {
            $request['logoUrl'] = $logo;
            $request['bannerUrl'] = $banner;
            $model->fill($request)->save();
            $result = $this->parseResult($model);
            $result->languages = $languages;
            return $result;
        }
    }

    private function saveCompanyProfileImage($image_64)
    {
        try {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $image = str_replace($replace, '', $image_64);

            $image = str_replace(' ', '+', $image);

            $destinationPath = public_path() . '/images//';

            $imageName = Uuid::uuid4() . '.' . $extension;

            file_put_contents($destinationPath . $imageName,  base64_decode($image));
            return 'images/' . $imageName;
        } catch (\Exception $e) {
            return '';
        }
    }
}
