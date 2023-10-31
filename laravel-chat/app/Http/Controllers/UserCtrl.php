<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\FriendDelete;
use App\Http\Requests\DeleteFriendRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Attack;
use Illuminate\Support\Facades\Session;

class UserCtrl extends Controller
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
     * Delete a friend from the friend list.
     * 
     * First the data of the request is check. if the data pass the validator then
     * we will search the true id of the friend because the one that has been sent is the fake one.
     * 
     * If the real id of the friend can't be found then an error message will be return
     * and the suspicious action will be registered in the data base.
     */
    public function deleteFriend(Request $request) {

        $validator = Validator::make($request->all(), [
            'user' => 'required|numeric'
        ]);

        if($validator->fails()) {
            Attack::insert([
                'user_id' => Auth::id(),
                'description' => 'Potential attack by the route delete friend.'
            ]);
            Session::flush();
            Auth::logout();
            return redirect('login');
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
        $friendId = $request->input('user');

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

        Friend::where('listOfUser', '=', $trueId)
                ->where('friend', '=', Auth::id())
                ->delete();

        Friend::where('listOfUser', '=', Auth::id())
                ->where('friend', '=', $trueId)
                ->delete();
                
        broadcast(new FriendDelete(Auth::user(), $trueId))->toOthers();
        return ['status'=>100, 'timestamp'=>time()];
    }
}
