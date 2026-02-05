<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use Illuminate\Http\Request;
use App\Models\EmailSMTPSettings;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Contracts\SendEmailRepositoryInterface;

class EmailController extends Controller
{
    private $sendEmailRepository;

    public function __construct(SendEmailRepositoryInterface $sendEmailRepository)
    {
        $this->sendEmailRepository = $sendEmailRepository;
    }

    public function sendEmail(Request $request)
    {
        $defaultSMTP = EmailSMTPSettings::where('isDefault', 1)->first();
        if ($defaultSMTP == null) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Default SMTP configuration does not exist.',
            ], 422);
        }

        //return $defaultSMTP;

        $email = Auth::parseToken()->getPayload()->get('email');

        if ($email == null) {
            throw new RepositoryException('Email does not exist.');
        }

        if ($defaultSMTP) {
            $mail = new  PHPMailer();
           $mail->isSMTP();
            $mail->Host       = $defaultSMTP['host']; // smtp-relay.sendinblue.com
            $mail->SMTPAuth   = true;
            $mail->Username   = $defaultSMTP['userName'];
            $mail->Password   = $defaultSMTP['password'];
           
            // ✅ ICI EXACTEMENT
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $defaultSMTP['port']; // 587
            $mail->addAddress($request['email'] ? $request['email'] : $request['to_address']);
            $mail->setFrom('wassilaelsaghir@gmail.com', $defaultSMTP['fromName']);

            $mail->isHTML(true);
            $mail->Subject = $request['subject'];
            $mail->Body    = $request['message'];
            $mail->AltBody = $request['message'];
            //$mail->SMTPDebug = 2;
            //$mail->Sendmail   = '/usr/sbin/sendmail -bs';

            if ($request['documentId'] != null) {
                $document = Documents::where('id', $request->documentId)->first();

                $fileupload = $document->url;
                if (Storage::disk('local')->exists($fileupload)) {
                    $ext = pathinfo($document->url, PATHINFO_EXTENSION);
                    $sendEmailObject['path'] = Storage::path($fileupload);
                    $sendEmailObject['mime_type'] = Storage::mimeType($fileupload);
                    $sendEmailObject['file_name'] = $document->name . '.' . $ext;
                }

                //return $document;
                $mail->addAttachment($sendEmailObject['path'], $sendEmailObject['file_name']);
            }
            /* $mail->send(); */
            if ($mail->send()) {
                $request['fromEmail'] = $defaultSMTP->userName;
                $request['isSend'] = true;
                return  response($this->sendEmailRepository->create($request->all()), 201);
            } else {
                return 'Mail could not be sent. Error: ' . $mail->ErrorInfo;
            }
        }

        $request['fromEmail'] = $defaultSMTP->userName;
        $request['isSend'] = false;
        return  response($this->sendEmailRepository->create($request->all()), 201);
    }
}
