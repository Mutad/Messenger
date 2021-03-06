<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Channel;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Pagination\Paginator;
use App\UserChannels;
use App\Message;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // TODO make hidden channels
        return Channel::all();
    }

    public function getById($id)
    {
        return response()->json(['success'=>Channel::find($id)], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 400);
        }
        $input = $request->all();
        $input['members'] = 0;
        $channel = Channel::create($input);

        return response()->json(['success'=>$channel], 201);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 400);
        }
        $input = $request->all();
        $channels = Channel::where('name', 'LIKE', "%{$input['name']}%")->get();
        return \response()->json(['success'=>$channels], 200);
    }

    /**
     * Get user channels.
     *
     * @return \Illuminate\Http\Response
     */
    public function getMessages(Request $request,$channelId)
    {
        $user = Auth::user();
        if (Channel::find($channelId)->private) {
            if (UserChannels::where([
                ['userId',$user['id']],
                ['channelId',$channelId]
                ])->doesntExist()) {
                return response()->json(['error'=>'User not a member of this channel'], 400);
            }
        }

        $itemsPerPage = 100;

        $page = $request->get('page');
        
        if (!$request->get('page') || $request->get('page')==0){
            $msgs = Message::where('channelId',$channelId)->paginate($itemsPerPage);
            $page = $msgs->lastPage();
        }
        $msgs = Message::where('channelId',$channelId)
        ->paginate($itemsPerPage,['*'], 'page',$page);
        
        $msgs->getCollection()->map(function($item){
            $item['user'] = $item->user;
            return $item;
        });


        // $messages = Message::where('channelId', $channelId)
        // ->orderBy('created_at', 'DESC')
        // ->orderBy('id', 'DESC')
        // ->paginate(50);

        return response()->json(['success'=>$msgs], 200);
    }

    public function joined($channelId)
    {
        $user = Auth::user();
        if (UserChannels::where([
            ['userId',$user['id']],
            ['channelId',$channelId]
        ])->first()!==null) {
            return \response()->json(['success'=>"User is a member of this channel"], 200);
        } else {
            return response()->json(['error'=>'User not a member of this channel'], 203);
        }
    }

    /**
     * Get user channels.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserChannels()
    {
        $user = Auth::user();
        $channelIds = array();
        UserChannels::where('userId', $user['id'])->get()->map(function ($userChannel) use (&$channelIds) {
            array_push($channelIds, $userChannel['channelId']);
        });
        
        $channels = Channel::whereIn('id', $channelIds)->get();
        return response()->json(['success'=>$channels], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Channel  $channel
     * @return \Illuminate\Http\Response
     */
    public function show(Channel $channel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Channel  $channel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Channel $channel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Channel  $channel
     * @return \Illuminate\Http\Response
     */
    public function destroy(Channel $channel)
    {
        //
    }
}
