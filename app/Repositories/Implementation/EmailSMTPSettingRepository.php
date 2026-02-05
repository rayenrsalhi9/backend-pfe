<?php

namespace App\Repositories\Implementation;


use App\Models\EmailSMTPSettings;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\EmailSMTPSettingRepositoryInterface;
use PHPMailer\PHPMailer\PHPMailer;



class EmailSMTPSettingRepository extends BaseRepository implements EmailSMTPSettingRepositoryInterface
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return EmailSMTPSettings::class;
    }

    public function createEmailSMTP($attribute)
    {
        $isTested = $this->testEmail($attribute);

        if (!$isTested) {
            return response()->json(['Message' => 'Not able to send the Test email please review your SMTP Settings.'], 409);
        }

        if ($attribute['isDefault'] == 1) {

            $defaultEmailSMTPSettings = EmailSMTPSettings::where('isDefault', '=', 1)->get();

            foreach ($defaultEmailSMTPSettings as $defaultEmailSMTPSetting) {
                $defaultEmailSMTPSetting->isDefault = false;
                $defaultEmailSMTPSetting->save();
            }
        }

        $model = $this->model->newInstance($attribute);

        $model->save();
        $this->resetModel();
        $result = $this->parseResult($model);
        return response($result, 201);
    }

    public function updateEmailSMTP($request, $id)
    {
        $entityExist = $this->model->findOrFail($id);

        $isTested = $this->testEmail($request);

        if (!$isTested) {
            return response()->json(['Message' => 'Not able to send the Test email please review your SMTP Settings.'], 409);
        }

        // remove other as default
        if ($request->isDefault == 1) {
            $defaultEmailSMTPSettings = EmailSMTPSettings::where('isDefault', '=', 1)->get();

            foreach ($defaultEmailSMTPSettings as $defaultEmailSMTPSetting) {
                if ($defaultEmailSMTPSetting->id != $id) {
                    $defaultEmailSMTPSetting->isDefault = false;
                    $defaultEmailSMTPSetting->save();
                }
            }
        }


        $entityExist->isDefault = $request->isDefault  == 1 ? true : false;;
        $entityExist->encryption = $request->encryption;
        $entityExist->fromName = $request->fromName;
        $entityExist->host = $request->host;
        $entityExist->port = $request->port;
        $entityExist->userName = $request->userName;
        $entityExist->password = $request->password;

        $entityExist->save();
        $result = $this->parseResult($entityExist);

        return response()->json($result, 200);
    }

    private function testEmail($attribute)
    {
        try {
            $mail = new  PHPMailer();
            $mail->isSMTP();
            $mail->Host       = $attribute['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $attribute['userName'];
            $mail->Password   = $attribute['password'];
            $mail->SMTPSecure = $attribute['encryption'];
            $mail->Port       = $attribute['port'];
            $mail->addAddress($attribute['userName']);
            $mail->setFrom($attribute['userName'], $attribute['fromName']);
            $mail->isHTML(true);
            $mail->Subject = 'Account Configuration Test';
            $mail->Body    = 'Account Configuration Test';
            $mail->AltBody = 'Account Configuration Test';
            $mail->Sendmail   = '/usr/sbin/sendmail -bs';
            $mail->send();
        } catch (\Throwable $th) {
            return false;
        }
        return true;
    }
}
