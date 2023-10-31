<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Friend;
use App\Models\Attack;
use App\Models\User;
use App\Models\FriendRequest;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\SendMessageRequest;

class ChatsController extends Controller
{
    /**
     * Check if the user that try to access this route is authenticated.
     * If he is not authenticate, then he will be redirect to the login page.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the friend of the current user.
     * At the same time the real id of the friends are replace by a fake
     * one so the current user can't guess the real id in the data base.
     */
    public function getFriends() {
        $result = Friend::select('users.id', 'name')
                        ->join('users', 'friend', '=', 'users.id')
                        ->where('listOfUser', Auth::id())
                        ->orderBy('friends.id')
                        ->get();

        $i = 1;
        $user = Auth::user();
        $nb = strlen($user->name) + 37;

        foreach($result as $row) {
            $tmp = $nb * $row->id * $i;
            $row->fakeId = $tmp;
            $i++;
        }

        return $result;
    }

    /**
     * Get the users that are not the friend of the current user.
     */
    public function getUsers() {
        return User::select('id', 'name')
                    ->whereNotIn('id', function ($query) {
                        $query->select('friend')
                                ->from('friends')
                                ->where('listOfUser', Auth::id());
                    })
                    ->where('id', '!=', Auth::id())
                    ->get();
    }

    /**
     * Get the friend request of the current user.
     */
    public function getRequest() {
        return FriendRequest::join('users', 'fromUser', '=', 'users.id')
                    ->where('toUser', Auth::id())
                    ->select('name', 'users.id')
                    ->get();
    }

    /**
     * Get the chat page.
     */
    public function index()
    {
        $result1 = $this->getFriends();
        $result2 = $this->getUsers();
        $result3 = $this->getRequest();
        return view('chat', ['friends'=>$result1, 'users'=>$result2, 'requests'=>$result3]);
    }

    /**
     * Get the messages of the current friend that the user select.
     * The friendId receive is the fake one so we need to get the real of id
     * of the friend with the same way that we did when we get the friends of the user.
     */
    public function fetchMessages($friendId)
    {
        $result = Friend::select('users.id', 'name')
                        ->join('users', 'friend', '=', 'users.id')
                        ->where('listOfUser', Auth::id())
                        ->orderBy('friends.id')
                        ->get();

        $i = 1;
        $user = Auth::user();
        $nb = strlen($user->name) + 37;
        $trueId = null;

        foreach($result as $row) {
            $tmp = $nb * $row->id * $i;
            if($tmp == $friendId) {
                $trueId = $row->id;
            }
            $i++;
        }

        if($trueId == null) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User try to get the messages of a discussion that he doens\'t have'
            ]);
            return redirect('chat');
        }

        $msg = Message::getMessage($trueId);

        return ['messages'=>$msg, 'timestamp'=>time()];
    }

    /**
     * Send a message to a friend.
     * First we check the content of the request.
     * If the message is not a string or is longer than 255 character or if the friendId is not a number, the message 
     * will not be send and a record will be create that indicate what the current user try to do.
     * If the two parameter pass the validator, the friendId receive is the fake one so we need to get the real of id
     * of the friend with the same way that we did when we get the friends of the user.
     * 
     * If everything is ok then the message is sent to the friend.
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend' => 'required|numeric',
            'message' => 'required|string|max:255'
        ]);
        
        if(strlen($request->input('message')) != 0 && $validator->fails()) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'Potential attack by the send message friend - message longer than 255 or friend id is wrong.'
            ]);
            return ['status' => 200, 'timestamp'=>time()];
        }

        $result = Friend::select('users.id', 'name')
                        ->join('users', 'friend', '=', 'users.id')
                        ->where('listOfUser', Auth::id())
                        ->orderBy('friends.id')
                        ->get();

        $i = 1;
        $user = Auth::user();
        $nb = strlen($user->name) + 37;
        $trueId = null;
        $friendId = $request->input('friend');

        foreach($result as $row) {
            $tmp = $nb * $row->id * $i;
            if($tmp == $friendId) {
                $trueId = $row->id;
            }
            $i++;
        }

        if($trueId == null) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User try to send a message to a user that is not his friend.'
            ]);
            return ['status' => 200, 'timestamp'=>time()];
        }

        $message = Message::newMessage($request->input('message'), $trueId);
        broadcast(new MessageSent(Auth::id(), $trueId, $message[0]))->toOthers();
        return ['status' => 100, 'message'=>$message[0], 'timestamp'=>time()];
    }
}
