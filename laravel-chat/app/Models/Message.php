<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'message', 'receiver_id']; 

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the message of of the friend.
     */
    public static function getMessage($friendId)
    {
        $result = DB::table('messages')
                    ->join('users', 'messages.user_id', '=', 'users.id')
                    ->where(['messages.user_id'=>Auth::id(), 'messages.receiver_id'=>$friendId])
                    ->orWhere(function($query) use($friendId){
                        $query->where(['messages.user_id'=>$friendId, 'messages.receiver_id'=>Auth::id()]);
                    })
                    ->select('messages.message', 'users.name')
                    ->get();

        foreach($result as $row) {
            $tmpMsg = $row->message;
            $row->message = Crypt::decryptString($tmpMsg);
        }

        return $result;
    }

    /**
     * Create and register a new message in the data base. 
     * 
     * The message that are stored are encrypted.
     */
    public static function newMessage($message, $friendId)
    {
        $msgId = DB::table('messages')
                    ->insertGetId([
                        'user_id' => Auth::id(),
                        'message' => Crypt::encryptString($message),
                        'receiver_id' => $friendId
                    ]);

        $message = DB::table('messages')
                    ->join('users', 'messages.user_id', '=', 'users.id')
                    ->where('messages.id', '=', $msgId)
                    ->select('messages.message', 'users.name')
                    ->get();
        
        $tmpMsg = $message[0]->message;

        $message[0]->message = Crypt::decryptString($tmpMsg);

        return $message;
    }
}
