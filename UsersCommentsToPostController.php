<?php
namespace App\Http\Controllers;
use App\UsersCommentsToPost;
use Illuminate\Http\Request;
use Auth;
use App\User;
use DB;

class UsersCommentsToPostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $comment = $request->comment;
        $qid = $request->qid;
        $ansid = $request->ansid;
        
        
        $array= array();
        $comments = DB::table('users_comments_to_posts')->select('user_id','post_id','answer_id')->where('answer_id', $ansid)->get();
        if(!empty($comments[0])){
            
            $allVote =  DB::table('users_comments_to_posts')->where('answer_id', $ansid)->count();
            DB::table('post_answers')->where('id', $ansid)->update(['total_comments_to_ans' => $allVote]);
            
        }
   
        $usersCommentsToPost = new usersCommentsToPost;
        $user = Auth::user();
        $usersCommentsToPost->user_id  = $user->id;
        $usersCommentsToPost->post_id  = $qid;
        $usersCommentsToPost->answer_id   = $ansid;
        $usersCommentsToPost->comments  = $comment;
        $usersCommentsToPost->save();
        
        $all_Vote =  DB::table('users_comments_to_posts')->where('answer_id', $ansid)->count();
        DB::table('post_answers')->where('id', $ansid)->update(['total_comments_to_ans' => $all_Vote]);
   
        $commentedBy =  User::find($usersCommentsToPost->user_id);
        $array['commentedByName'] =   $commentedBy->name;
        
        if($commentedBy->pic_type == 'web'){
        $array['commentedByPic'] =   "../".$commentedBy->pic;
        }else{
            $array['commentedByPic'] =   $commentedBy->pic;
        }
        
        $array['comments']  = $usersCommentsToPost->comments;
        $array['updatedAt'] = $usersCommentsToPost->updated_at;
        
     
        echo json_encode($array); 
       
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\usersCommentsToPost  $usersCommentsToPost
     * @return \Illuminate\Http\Response
     */
    public function show(usersCommentsToPost $usersCommentsToPost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\usersCommentsToPost  $usersCommentsToPost
     * @return \Illuminate\Http\Response
     */
    public function edit(usersCommentsToPost $usersCommentsToPost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\usersCommentsToPost  $usersCommentsToPost
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, usersCommentsToPost $usersCommentsToPost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\usersCommentsToPost  $usersCommentsToPost
     * @return \Illuminate\Http\Response
     */
    public function destroy(usersCommentsToPost $usersCommentsToPost)
    {
        //
    }
}
