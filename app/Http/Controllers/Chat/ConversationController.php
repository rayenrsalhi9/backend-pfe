<?php

namespace App\Http\Controllers\Chat;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\ConversationUser;
use App\Events\ConversationEvent;
use App\Events\MessageReaction as EventsMessageReaction;
use App\Events\MessageSeen;
use App\Events\UserChatEvent;
use App\Events\UserNotification;
use App\Models\ConversationMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\MessageReaction;
use App\Models\UserNotifications;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConversationController extends Controller
{

    private function saveFile($image_64)
    {

        $output = new ConsoleOutput();

        try {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $image = str_replace($replace, '', $image_64);

            $image = str_replace(' ', '+', $image);

            $destinationPath = public_path() . '/images//';

            $imageName = Uuid::uuid4() . '.' . $extension;

            $output->writeln($extension);

            file_put_contents($destinationPath . $imageName,  base64_decode($image));
            return 'images/' . $imageName;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function conversationsByUser()
    {
        try {
            $user = Auth::user();

            $conversations = Conversation::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })->with('lastMessage', 'lastMessage.sender', 'lastMessage.document', 'users')
                ->whereHas('lastMessage', function ($query) {
                    $query->whereNotNull('id');
                })
                ->get();

            return response()->json($conversations, 200);
        } catch (\Throwable $th) {

            return response()->json($th->getMessage(), 500);
        }
    }

    function conversationMessages($id)
    {
        try {

            $conversations = Conversation::where('id', $id)->with(['messages', 'messages.sender', 'messages.document', 'users', 'messages.reactions'])->first();

            return response()->json($conversations, 200);
        } catch (\Throwable $th) {

            return response()->json($th->getMessage(), 500);
        }
    }

    function conversationUsers($id)
    {
        try {

            $conversations = Conversation::where('id', $id)->with('users')->get();

            return response()->json($conversations, 200);
        } catch (\Throwable $th) {

            return response()->json($th->getMessage(), 500);
        }
    }

    function messageSend(Request $request)
    {
        try {

            $user = Auth::user();
            $conversation = Conversation::where('id', $request->conversation['id'])->with('users')->first();
            if (!$conversation) {
                return response()->json('conversation not found', 404);
            }

            $conversationMessage = new ConversationMessage();
            $conversationMessage->conversation_id = $conversation->id;
            $conversationMessage->sender_id = $user->id;
            $conversationMessage->content = $request->type != "msg" ? $this->saveFile($request['content']) : $request->content;
            $conversationMessage->document_id = null;
            $conversationMessage->is_read = null;
            $conversationMessage->type = $request->type;
            $conversationMessage->save();

            $message =  $conversationMessage->load('sender', 'conversation', 'document', 'conversation.users', 'reactions');

            $data = MessageResource::make($message);

            broadcast(new ConversationEvent(json_decode(json_encode($data), true)))->toOthers();

            foreach ($data->conversation['users'] as $key => $usr) {

                if ($user->id != $usr->id) {

                    broadcast(new UserChatEvent(json_decode(json_encode($data), true), $usr->id))->toOthers();

                    $notification = new UserNotifications();
                    $notification->userId = $usr->id;
                    $notification->type = 'message';
                    if ($conversationMessage->type == 'msg') {
                        $notification->message = /* $user->firstName . ' ' . $user->lastName . ' send a message : ' .  */$conversationMessage->content;
                    } else {
                        $notification->message = /* $user->firstName . ' ' . $user->lastName . ' send a file : ' .  */$conversationMessage->content;
                    }
                    $notification->save();

                    broadcast(new UserNotification(json_decode(json_encode($notification), true), $usr->id, 'message'))->toOthers();
                }
            }

            return response()->json($message, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    function messageSeen($id)
    {
        $conversationMessage = ConversationMessage::where('id', $id)->first();
        $conversationMessage->is_read = new DateTime();
        $conversationMessage->save();

        $message =  $conversationMessage->load('sender', 'conversation', 'document', 'conversation.users', 'reactions');

        $data = MessageResource::make($message);

        broadcast(new MessageSeen(json_decode(json_encode($data), true)))->toOthers();

        return response()->json($message, 200);
    }

    function messageReaction($id, Request $request)
    {

        try {
            $messageReaction = MessageReaction::where([
                'conversation_message_id' => $request->mid,
                'sender_id' => $request->uid
            ])->first();

            if ($messageReaction) {

                if ($messageReaction->type == $request->type) {
                    $messageReaction->delete();
                } else {
                    $messageReaction->conversation_message_id = $request->mid;
                    $messageReaction->type = $request->type;
                    $messageReaction->sender_id = $request->uid;
                    $messageReaction->save();
                }
            } else {
                $messageReaction = new MessageReaction();
                $messageReaction->conversation_message_id = $request->mid;
                $messageReaction->type = $request->type;
                $messageReaction->sender_id = $request->uid;
                $messageReaction->save();
            }

            $conversationMessage = ConversationMessage::where('id', $request->mid)->first();
            $message = $conversationMessage->load('sender', 'conversation', 'document', 'conversation.users', 'reactions');
            $data = MessageResource::make($message);

            broadcast(new EventsMessageReaction(json_decode(json_encode($data), true)))->toOthers();

            return response($data, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 200);
        }
    }

    function conversationAddUser(Request $request)
    {

        $conversation = Conversation::where('id', $request->conversationId)->first();
        $conversation->title = $request->title;
        $conversation->save();

        $conversationUser = ConversationUser::where([
            'conversation_id' => $request->conversationId,
            'user_id' => $request->selectedUser['id']
        ])->first();

        if ($conversationUser) return response()->json(['message' => 'user already exist'], 409);

        if (!$conversationUser) {
            $cuser = new ConversationUser();
            $cuser->user_id = $request->selectedUser['id'];
            $cuser->conversation_id = $request->conversationId;
            $cuser->save();
        }

        $cuser->load('conversation', 'conversation.users', 'conversation.lastMessage', 'conversation.lastMessage.sender', 'conversation.lastMessage.document');

        return response()->json($cuser, 200);
    }

    function conversationGroup(Request $request)
    {
        return response()->json($request->all());
    }

    function conversationUpdate($id, Request $request)
    {
        return $id;

        try {
            $conversation = Conversation::where('id',$id)->first();
            $conversation->title = $request->tile;

            $converationUsers = ConversationUser::where('conversation_id',$id)->get();

            /* foreach ($converationUsers as $key => $value) {
                # code...
            } */

            return response()->json('updated', 200);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    function conversationCreate(Request $request)
    {


        try {

            $users = (array) $request->users;

            $conversationExist = ConversationUser::whereIn('user_id', $users)
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(DISTINCT user_id) = ' . count($users))
                ->with('conversation', 'conversation.users')
                ->first();

            //return response()->json(empty($conversationExist), 200);

            if (isset($request->new) && $request->new == true && count($users) > 2 ) {
                $newConversation = new Conversation();
                $newConversation->title = $request->title ? $request->title : null;
                $newConversation->save();

                foreach ($users as $user) {
                    $cuser = new ConversationUser();
                    $cuser->user_id = $user;
                    $cuser->conversation_id = $newConversation->id;
                    $cuser->save();
                }
                $conversation =  $newConversation->load('users', 'lastMessage');

                return response()->json($conversation, 200);
            }

            if (!empty($conversationExist) && count($conversationExist['conversation']['users']) == count($users)) {
                $conver = Conversation::where('id', $conversationExist['conversation_id'])->with('users', 'lastMessage','lastMessage.sender')->first();
                return response()->json($conver, 200);
            }

            if (empty($conversationExist) || count($conversationExist['conversation']['users']) != count($users)) {
                $newConversation = new Conversation();
                $newConversation->title = $request->title ? $request->title : null;
                $newConversation->save();

                foreach ($users as $user) {
                    $cuser = new ConversationUser();
                    $cuser->user_id = $user;
                    $cuser->conversation_id = $newConversation->id;
                    $cuser->save();
                }
                $conversation =  $newConversation->load('users', 'lastMessage','lastMessage.sender');

                return response()->json($conversation, 200);
            }


            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    function conversationDelete($id)
    {
        try {

            $conversation = Conversation::where('id', $id)->first();
            $conversation->delete();

            return response()->json('conversation deleted', 200);
        } catch (\Throwable $th) {

            return response()->json($th->getMessage(), 500);
        }
    }
}
