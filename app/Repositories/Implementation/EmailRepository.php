<?php

namespace App\Repositories\Implementation;

use App\Models\Actions;
use App\Models\EmailSMTPSettings;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Repositories\Contracts\EmailRepositoryInterface;


//use Your Model

/**
 * Class ActionsRepository.
 */
class EmailRepository  implements EmailRepositoryInterface
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
        return Actions::class;
    }

    public function sendEmail($attribute)
    {
        $smtpSettings = EmailSMTPSettings::where('isDefault', 1)->first();

        if ($smtpSettings) {
            $mail = new  PHPMailer();
            $mail->isSMTP();
            $mail->Host       = $smtpSettings['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpSettings['userName'];
            $mail->Password   = $smtpSettings['password'];
            $mail->SMTPSecure = $smtpSettings['encryption'];
            $mail->Port       = $smtpSettings['port'];
            $mail->addAddress($attribute['email']?$attribute['email']:$attribute['to_address']);
            $mail->setFrom($smtpSettings['userName'], $smtpSettings['fromName']);
            $mail->isHTML(true);
            $mail->Subject = $attribute['subject'];
            $mail->Body    = $attribute['message'];
            $mail->AltBody = $attribute['message'];
            $mail->Sendmail   = '/usr/sbin/sendmail -bs';

            if ($attribute['path'] != null) {
                $mail->addAttachment($attribute['path'], $attribute['file_name']);
            }
            $mail->send();
        }

        // if ($mail) {
        //     $config = array(
        //         'driver'     => 'smtp',
        //         'host'       => $mail->host,
        //         'port'       => $mail->port,
        //         'from'       => array('address' => $mail->userName, 'name' => $mail->userName),
        //         'encryption' => $mail->isEnableSSL ? 'ssl' : '',
        //         'username'   => $mail->userName,
        //         'password'   => $mail->password,
        //         'sendmail'   => '/usr/sbin/sendmail -bs',
        //         'pretend'    => false,
        //     );
        //     Config::set(
        //         'mail',
        //         $config
        //     );

        //     Mail::send([], [], function ($message) use ($attribute, $mail) {
        //         $message
        //             ->from($mail->userName)
        //             ->to($attribute['to_address'])
        //             ->subject($attribute['subject'])
        //             ->html($attribute['message']);

        //         if ($attribute['path'] != null) {
        //             $message->attach(
        //                 $attribute['path'],
        //                 array(
        //                     'as' => $attribute['file_name'], // If you want you can chnage original name to custom name
        //                     'mime' => $attribute['mime_type']
        //                 )
        //             );
        //         }
        //     });
        // }
    }
}
