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
use Illuminate\Support\Facades\DB;
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

            file_put_contents($destinationPath . $imageName, base64_decode($image));
            return 'images/' . $imageName;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function conversationsByUser(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $perPage = (int) $request->input('per_page', 20);
            $page = max((int) $request->input('page', 1), 1);

            if ($perPage < 1) {
                $perPage = 1;
            } elseif ($perPage > 100) {
                $perPage = 100;
            }

            $conversationIds = DB::table('conversation_users')
                ->where('user_id', $user->id)
                ->pluck('conversation_id')
                ->toArray();

            if (empty($conversationIds)) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1
                    ]
                ], 200);
            }

            $conversations = Conversation::whereIn('id', $conversationIds)
                ->with(['lastMessage', 'lastMessage.sender', 'lastMessage.document', 'users'])
                ->withMax('messages', 'created_at')
                ->orderByDesc('messages_max_created_at')
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $conversations->items(),
                'meta' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage()
                ]
            ], 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['error' => 'Failed to load conversations'], 500);
        }
    }

    function conversationMessages($id)
    {
        try {
            $isParticipant = ConversationUser::where('conversation_id', $id)
                ->where('user_id', auth()->id())->exists();
            if (!$isParticipant) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $conversations = Conversation::where('id', $id)->with(['messages', 'messages.sender', 'messages.document', 'users', 'messages.reactions'])->first();

            return response()->json($conversations, 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['error' => 'Failed to retrieve messages'], 500);
        }
    }

    function conversationUsers($id)
    {
        try {
            $isParticipant = ConversationUser::where('conversation_id', $id)
                ->where('user_id', auth()->id())->exists();
            if (!$isParticipant) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $conversations = Conversation::where('id', $id)->with('users')->get();

            return response()->json($conversations, 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['error' => 'Failed to retrieve conversation users'], 500);
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

            $isUserInConversation = ConversationUser::where([
                'conversation_id' => $request->conversation['id'],
                'user_id' => $user->id
            ])->exists();

            if (!$isUserInConversation) {
                return response()->json(['message' => 'unauthorized - not a participant in this conversation'], 403);
            }

            $conversationMessage = new ConversationMessage();
            $conversationMessage->conversation_id = $conversation->id;
            $conversationMessage->sender_id = $user->id;
            if ($request->type != "msg") {
                $filePath = $this->saveFile($request->input('content'));
                if (empty($filePath)) {
                    return response()->json(['error' => 'Failed to save file'], 400);
                }
                $conversationMessage->content = $filePath;
            } else {
                $conversationMessage->content = $request->input('content');
            }
            $conversationMessage->document_id = null;
            $conversationMessage->is_read = null;
            $conversationMessage->type = $request->type;
            $conversationMessage->save();

            $message = $conversationMessage->load('sender', 'conversation', 'document', 'conversation.users', 'reactions');

            $data = MessageResource::make($message);

            broadcast(new ConversationEvent(json_decode(json_encode($data), true)))->toOthers();

            foreach ($data->conversation['users'] as $key => $usr) {

                if ($user->id != $usr->id) {

                    broadcast(new UserChatEvent(json_decode(json_encode($data), true), $usr->id))->toOthers();

                    $notification = new UserNotifications();
                    $notification->userId = $usr->id;
                    $notification->type = 'message';
                    if ($conversationMessage->type == 'msg') {
                        $notification->message = /* $user->firstName . ' ' . $user->lastName . ' send a message : ' .  */ $conversationMessage->content;
                    } else {
                        $notification->message = /* $user->firstName . ' ' . $user->lastName . ' send a file : ' .  */ $conversationMessage->content;
                    }
                    $notification->save();

                    broadcast(new UserNotification(json_decode(json_encode($notification), true), $usr->id, 'message'))->toOthers();
                }
            }

            return response()->json($message, 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    function messageSeen($id)
    {
        $conversationMessage = ConversationMessage::where('id', $id)->first();
        $conversationMessage->is_read = new DateTime();
        $conversationMessage->save();

        $message = $conversationMessage->load('sender', 'conversation', 'document', 'conversation.users', 'reactions');

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
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['error' => 'Failed to add reaction'], 500);
        }
    }

    function conversationAddUser(Request $request)
    {

        $user = Auth::user();

        $conversation = Conversation::where('id', $request->conversationId)->first();
        if (!$conversation) {
            return response()->json(['message' => 'conversation not found'], 404);
        }

        $isUserInConversation = ConversationUser::where([
            'conversation_id' => $request->conversationId,
            'user_id' => $user->id
        ])->exists();

        if (!$isUserInConversation) {
            return response()->json(['message' => 'unauthorized - not a participant in this conversation'], 403);
        }

        $conversationUser = ConversationUser::where([
            'conversation_id' => $request->conversationId,
            'user_id' => $request->selectedUser['id']
        ])->first();

        if ($conversationUser)
            return response()->json(['message' => 'user already exist'], 409);

        $cuser = DB::transaction(function () use ($conversation, $request) {
            if ($request->has('title') && $request->title !== null && $request->title !== '') {
                $conversation->title = $request->title;
                $conversation->save();
            }

            $cuser = ConversationUser::firstOrCreate([
                'conversation_id' => $request->conversationId,
                'user_id' => $request->selectedUser['id']
            ]);

            $conversation->touch();

            return $cuser;
        });

        $cuser->load('conversation', 'conversation.users', 'conversation.lastMessage', 'conversation.lastMessage.sender', 'conversation.lastMessage.document');

        return response()->json(['conversation' => $conversation->fresh(['users', 'lastMessage']), 'conversationUser' => $cuser], 200);
    }

    function conversationRemoveUser(Request $request)
    {
        $user = Auth::user();

        $isUserInConversation = ConversationUser::where([
            'conversation_id' => $request->conversationId,
            'user_id' => $user->id
        ])->exists();

        if (!$isUserInConversation) {
            return response()->json(['message' => 'unauthorized - not a participant in this conversation'], 403);
        }

        $conversationUser = ConversationUser::where([
            'conversation_id' => $request->conversationId,
            'user_id' => $request->userId
        ])->first();

        if (!$conversationUser) {
            return response()->json(['message' => 'user not found in conversation'], 404);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $conversationUser) {
            $currentCount = ConversationUser::where('conversation_id', $request->conversationId)
                ->lockForUpdate()
                ->count();

            if ($currentCount <= 1) {
                return response()->json(['message' => 'Cannot remove the last participant'], 400);
            }

            $conversationUser->delete();

            return response()->json(['message' => 'user removed successfully'], 200);
        });
    }

    function conversationGroup(Request $request)
    {
        return response()->json($request->all());
    }

    function conversationUpdate($id, Request $request)
    {
        try {
            $user = Auth::user();

            $conversation = Conversation::where('id', $id)->first();

            if (!$conversation) {
                return response()->json(['message' => 'conversation not found'], 404);
            }

            $isUserInConversation = ConversationUser::where([
                'conversation_id' => $id,
                'user_id' => $user->id
            ])->exists();

            if (!$isUserInConversation) {
                return response()->json(['message' => 'unauthorized - not a participant in this conversation'], 403);
            }

            if ($request->has('title')) {
                $conversation->title = $request->title;
                $conversation->save();
            }

            return response()->json($conversation->fresh(['users', 'lastMessage']), 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['message' => 'Failed to update conversation'], 500);
        }
    }

    function conversationCreate(Request $request)
    {
        try {
            $rawUsers = (array) $request->users;
            $users = array_unique(array_filter($rawUsers, function ($v) {
                return !empty($v) && (is_numeric($v) || (is_string($v) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v)));
            }));

            if (empty($users)) {
                return response()->json(['error' => 'Invalid or empty users list'], 400);
            }

            $forceNew = $request->boolean('new');

            // For groups (new=true flag set), always create new conversation with title
            if ($forceNew) {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $users) {
                    $newConversation = new Conversation();
                    $newConversation->title = $request->title && trim($request->title) ? $request->title : null;
                    $newConversation->save();

                    foreach ($users as $userId) {
                        $cuser = new ConversationUser();
                        $cuser->user_id = $userId;
                        $cuser->conversation_id = $newConversation->id;
                        $cuser->save();
                    }
                    $conversation = $newConversation->load('users', 'lastMessage');

                    return response()->json($conversation, 200);
                });
            }

            // For 1-on-1 conversations (no new flag), check if conversation already exists
            $countUsers = count($users);
            $placeholders = implode(',', array_fill(0, $countUsers, '?'));

            $conversationExist = ConversationUser::select('conversation_id')
                ->groupBy('conversation_id')
                ->havingRaw("COUNT(DISTINCT CASE WHEN user_id IN ({$placeholders}) THEN user_id END) = ?", array_merge($users, [$countUsers]))
                ->havingRaw("COUNT(DISTINCT user_id) = ?", [$countUsers])
                ->first();

            if ($conversationExist) {
                $conversationId = $conversationExist->conversation_id;
                $actualParticipantCount = ConversationUser::where('conversation_id', $conversationId)->count();
                if ($actualParticipantCount != $countUsers) {
                    $conversationExist = null;
                }
            }

            if (!empty($conversationExist)) {
                $conver = Conversation::where('id', $conversationExist->conversation_id)->with('users', 'lastMessage', 'lastMessage.sender')->first();
                return response()->json($conver, 200);
            }

            // Create new conversation
            return \Illuminate\Support\Facades\DB::transaction(function () use ($users) {
                $newConversation = new Conversation();
                $newConversation->title = null;
                $newConversation->save();

                foreach ($users as $userId) {
                    $cuser = new ConversationUser();
                    $cuser->user_id = $userId;
                    $cuser->conversation_id = $newConversation->id;
                    $cuser->save();
                }
                $conversation = $newConversation->load('users', 'lastMessage', 'lastMessage.sender');

                return response()->json($conversation, 200);
            });
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['message' => 'Failed to create conversation'], 500);
        }
    }

    function conversationDelete($id)
    {
        try {

            $user = Auth::user();

            $conversation = Conversation::where('id', $id)->first();
            if (!$conversation) {
                return response()->json(['message' => 'conversation not found'], 404);
            }

            $isUserInConversation = ConversationUser::where([
                'conversation_id' => $id,
                'user_id' => $user->id
            ])->exists();

            if (!$isUserInConversation) {
                return response()->json(['message' => 'unauthorized - not a participant in this conversation'], 403);
            }

            $conversation->delete();

            return response()->json('conversation deleted', 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            return response()->json(['message' => 'Failed to delete conversation'], 500);
        }
    }
}
