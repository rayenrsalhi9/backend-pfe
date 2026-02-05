<?php

namespace Database\Seeders;

use App\Models\Languages;
use App\Models\Users;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $language = Languages::first();

        if ($language == null) {

            $user = Users::first();

            $languages = [
                ['id' => 'df8a9fe2-15af-11ee-83f2-d85ed3312c1f', 'order' => 1, 'code' => 'en', 'name' => 'English', 'imageUrl' => 'images/flags/united-states.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => 'ef46fe64-15af-11ee-83f2-d85ed3312c1f', 'order' => 2, 'code' => 'cn', 'name' => 'Chinese', 'imageUrl' => 'images/flags/china.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => 'f8041d27-15af-11ee-83f2-d85ed3312c1f', 'order' => 3, 'code' => 'es', 'name' => 'Spanish', 'imageUrl' => 'images/flags/france.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => 'fe78a067-15af-11ee-83f2-d85ed3312c1f', 'order' => 4, 'code' => 'ar', 'name' => 'Arabic', 'imageUrl' => 'images/flags/saudi-arabia.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => '04906ab8-15b0-11ee-83f2-d85ed3312c1f', 'order' => 5, 'code' => 'ru', 'name' => 'Russian', 'imageUrl' => 'images/flags/russia.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => '10ac83d1-15b0-11ee-83f2-d85ed3312c1f', 'order' => 6, 'code' => 'ja', 'name' => 'Japanese', 'imageUrl' => 'images/flags/japan.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => '1d9a6233-15b0-11ee-83f2-d85ed3312c1f', 'order' => 7, 'code' => 'fr', 'name' => 'French', 'imageUrl' => 'images/flags/france.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af'],
                ['id' => '9ed7278c-c7e7-4c91-9a83-83833603eb47', 'order' => 8, 'code' => 'ko', 'name' => 'Korean ', 'imageUrl' => 'images/flags/south-korea.svg', 'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af']
            ];
            Languages::insert($languages);
        }
    }
}
