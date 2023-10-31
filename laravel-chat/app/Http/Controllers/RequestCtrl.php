<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FriendRequest;
use Illuminate\Support\Facades\Auth;
use App\Events\RequestSend;
use App\Events\RequestAccept;
use App\Models\Friend;
use App\Models\Attack;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class RequestCtrl extends Controller
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
     * Send a friend request to the a user.
     * 
     * First, the data of the request are check. If the data pass the validator then
     * we check if the user has not already send a friend request to this user. This is 
     * to avoid spam of friend request by a user. If the user try to spam an error message will be return 
     * and his action will be register in the data base.
     * 
     * Then we check if the user that he send the friend request exist, if the user don't exist
     * then an error message will be send and his suspicious action will be register in the data base.
     * 
     * If everything is ok then the friend request is send to the user.
     */
    public function sendRequest(Request $request) {

        $validator = Validator::make($request->all(), [
            'user' => 'required|numeric'
        ]);

        if($validator->fails()) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'Potential attack by the send request route.'
            ]);
            Session::flush();
            Auth::logout();
            return redirect('login');
        }

        $result = FriendRequest::where(['fromUser'=>Auth::id(), 'toUser'=>$request->input('user')])
                                ->get();

        $result2 = User::where('id', $request->input('user'))
                        ->get();
        
        if(count($result) == 0 && count($result2) != 0) {
            FriendRequest::insert([
                'fromUser'=> Auth::id(),
                'toUser'=> $request->input('user')
            ]);

            $user = User::where('id', Auth::id())
                        ->select('name', 'id')
                        ->get();

            broadcast(new RequestSend($user, $request->input('user')))->toOthers();
            return ['status'=>100, 'timestamp'=>time()];
        }elseif (count($result2) == 0) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User try to send a friend request to a user that doesn\'t exist'
            ]);
            return ['status'=>200, 'timestamp'=>time()];
        }else {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User maybe try to spam friend request to a user.'
            ]);
            return ['status'=>200, 'timestamp'=>time()];
        }
    }

    /**
     * Accept the request that has been send by a user.
     * 
     * First, the data of the request is check. If the data pass the validator then we try to
     * delete the friend request. If no request has been delete then a error message will be send 
     * and the suspicious action well be registered in the data base.
     * 
     * If everything is ok then the server notify the other user that his friend request has been accepted.
     */
    public function acceptRequest(Request $request) {

        $validator = Validator::make($request->all(), [
            'user' => 'required|numeric'
        ]);

        if($validator->fails()) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'Potential attack by the accept request route.'
            ]);
            Session::flush();
            Auth::logout();
            return redirect('login');
        }

        $delete = FriendRequest::where('fromUser', '=', $request->input('user'))
                            ->where('toUser', '=', Auth::id())
                            ->delete();

        if($delete == 0) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User try to accept a friend request that he doesn\'t have.'
            ]);
            return ['status' => 200, 'timestamp'=>time()];
        }else {
            FriendRequest::where('fromUser', '=', Auth::id())
                            ->where('toUser', '=', $request->input('user'))
                            ->delete();
            Friend::insert([
                ['listOfUser'=>$request->input('user'), 'friend'=>Auth::id()],
                ['listOfUser'=>Auth::id(), 'friend'=>$request->input('user')]
            ]);
            
            $other = $this->fakeFriendId($request->input('user'), Auth::id());
            $current = $this->fakeFriendId(Auth::id(), $request->input('user'));

            broadcast(new RequestAccept($other->id, $other->name, $other->fakeId, $request->input('user')))->toOthers();
            return ['status'=>100, 'friend'=>$current, 'timestamp'=>time()];
        }
    }

    /**
     * Simple function that fake the id of a friend.
     */
    public function fakeFriendId($listOf, $target) {
        $result = Friend::select('users.id', 'name')
                        ->join('users', 'friend', '=', 'users.id')
                        ->where('listOfUser', $listOf)
                        ->orderBy('friends.id')
                        ->get();

        $i = 1;
        $user = Auth::user();
        $nb = strlen($user->name) + 37;

        foreach($result as $row) {
            if($row->id == $target) {
                $tmp = $nb * $row->id * $i;
                $row->fakeId = $tmp;
                return $row;
            }
            $i++;
        }
    }

    /**
     * Refuse a friend request.
     * 
     * First the data of the request will be check. If the data pass the validator 
     * then we delete the request from the data base.
     * 
     * If no request has been delete, then an error message will be return and the suspicious
     * action will be registered in the data base.
     */
    public function refuseRequest(Request $request) {

        $validator = Validator::make($request->all(), [
            'user' => 'required|numeric'
        ]);

        if($validator->fails()) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'Potential attack by the refuse request route.'
            ]);
            Session::flush();
            Auth::logout();
            return redirect('login');
        }

        $delete = FriendRequest::where('fromUser', '=', $request->input('user'))
                            ->where('toUser', '=', Auth::id())
                            ->delete();

        if($delete == 0) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'User try to delete a friend request that he doesn\'t have.'
            ]);
            return ['status'=>200, 'timestamp'=>time()];
        }

        return ['status'=>100, 'timestamp'=>time()];
    }
}
