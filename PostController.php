<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Storage;
use App\Post;
use DB;
use App\PostAnswers;
use Redirect,Response;
use Illuminate\Support\Facades\Input;
use Auth;
use Validator,File;
use Illuminate\Support\Facades\Hash;

class PostController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     * 
     */
public function __construct()
{
    $this->middleware('auth');
}

/**
 * Show the application dashboard.
 *
 * @return \Illuminate\Contracts\Support\Renderable
 */
public function createPostQuestion(Request $request)
{
    $this->validate($request, [
    'posttext' => 'required',
    'category' => 'required',
    'add_tags' => 'required'
   ],['category.required' => 'Please Select a Category.'],['add_tags.required' => 'Please Select Related Tags.'],['posttext.required' => 'Text field is required.']);
   
    $catId = $request->category;
    $tags = $request->add_tags;

    $post = new Post;
    $user = Auth::user();
    $post->post_user_id = $user->id;
    $post->post_title = Input::get('posttext');
    $post->category_id = $catId;
    $post->tags_id = $tags;
    $post->post_status = "1";
    $post->save();
    return redirect()->route('myposts');
    
}

//Create Question Answers Function.
public function createPostQuestionAns(Request $request)
{
    $this->validate($request, [
    'posttextans' => 'required',
    'file'  => 'file|mimes:jpeg,png,jpg,gif,webm,mp4,mov,ogg,mpeg',
    'file' => 'max:5000',
    ],['posttextans.required' => 'Text field is required']);
   
    $user = Auth::user();
    $ufolder = 'uploads/'.$user->id;
    $path = public_path($ufolder);
   
    if(!is_dir($path)) {
    $response = Storage::makeDirectory($ufolder);
    }
    
    if ($request->hasFile('file')) {
    $newname = time().'.'.$request->file->getClientOriginalName();
    $filename = $request->file->storeAs($ufolder, $newname);
    }else{$filename = "";}
    
    $filetype="";
    $qid = decrypt(Input::get('questionid'));
    $post = new PostAnswers;
    $user = Auth::user();
    $post->post_id = $qid;
    $post->post_answers_user_id = $user->id; 
    $post->post_answers = Input::get('posttextans');
    $post->file = $filename;
    $post->filetype = $filetype;
    $post->post_ans_status = "1";
    $post->save();
   return redirect()->route('myposts');
}
    
// Vote for Ans Function.
public function voteForAns(Request $request)
{
    $user = Auth::user();
    $input = $request->all();
    
    $user = DB::table('vote_ans')->where('ans_id', $input['id'])->where('user_id', $user->id)->get();
    if(!empty($user[0])){
  
        if($user[0]->ansvote == "1")  //total_vote_to_ans
        {
            DB::table('vote_ans')->where('id', $user[0]->id)->update(['ansvote' => 0]);
            $allVote =  DB::table('vote_ans')->where('ans_id', $input['id'])->where('ansvote', 1)->count();
            DB::table('post_answers')->where('id', $input['id'])->update(['total_vote_to_ans' => $allVote]);
            return $allVote;
            
        }else{
            DB::table('vote_ans')->where('id', $user[0]->id)->update(['ansvote' => 1]);
            $allVote =  DB::table('vote_ans')->where('ans_id', $input['id'])->where('ansvote', 1)->count();
            DB::table('post_answers')->where('id', $input['id'])->update(['total_vote_to_ans' => $allVote]);
            return $allVote;
        }
       
    }else{
        $user = Auth::user();
        $input = $request->all();
        DB::table('vote_ans')->insert(['ans_id' => $input['id'], 'user_id' => $user->id, 'ansvote' => 1]);
        $allVote =  DB::table('vote_ans')->where('ans_id', $input['id'])->where('ansvote', 1)->count();
        DB::table('post_answers')->where('id', $input['id'])->update(['total_vote_to_ans' => $allVote]);
        return $allVote;
    }
}// End Vote Function
    
//Edit Questions Function.
public function editQuestions($_id)
{
    $id = decrypt($_id);
    $question = DB::table('posts')->where('id', $id)->pluck('id','post_title');
    $category =  DB::select('SELECT id, cat_name FROM categories');
    $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
    return view('editquestions', compact('question'))->with(compact('category'))->with(compact('tags'));;
}
    
//Update Question updateQuestion
public function updateQuestion(Request $request){
    $this->validate($request, [
    'question' => 'required',
    ],['question.required' => 'Text field is required']); 
     
    $input = $request->all();
    $qid = decrypt($input['postid']);
    $updateQuestion =  $input['question'];
    DB::table('posts')->where('id', $qid)->update(['post_title' => $updateQuestion]);
    
    return redirect()->route('myposts');
}

 //Delete Question
public function deleteQuestion($id){
    $qid = decrypt($id);
    DB::table('posts')->where('id', $qid)->delete();
    DB::table('post_answers')->where('post_id', $qid)->delete();
    return redirect()->route('myposts');
}

//Edit Ans Function.
public function editAns($_id)
{
    $qid = decrypt($_id);
    $ansdata = DB::table('post_answers')->select('id','file','post_answers')->where('id', $qid)->get();
    $category =  DB::select('SELECT * FROM categories'); 
    $tags =  DB::select('SELECT * FROM tags LIMIT 10');
    return view('editans', compact('ansdata'))->with(compact('category'))->with(compact('tags'));
}

public function updateAns(Request $request){
    $this->validate($request, [
    'ans' => 'required',
    'file'  => 'file|mimes:jpeg,png,jpg,gif,webm,mp4,mov,ogg,mpeg',
    'file' => 'max:5000',
    ],['ans.required' => 'Text field is required']);
    
    $ansid = decrypt(Input::get('ansid'));
    $ansdata = DB::table('post_answers')->select('id','file')->where('id', $ansid)->get();
    
    
    //Save file to server//
    $user = Auth::user();
    $ufolder = 'uploads/'.$user->id;
    $path = public_path($ufolder);
    if(!is_dir($path)) {
    $response = Storage::makeDirectory($ufolder);
    }
    
    $filename ="";
    $send_ansid = Input::get('ansid');
    $updateAns =Input::get('ans');
    
    //Check file//
    if($request->hasFile('file')) {
        
    //Unlink old file from server.
    @unlink(storage_path('app/'.$ansdata[0]->file));
    
    $newname = time().'-'.$request->file->getClientOriginalName();
    $filename = $request->file->storeAs($ufolder, $newname);
    
    //Save Data in Database.
    DB::table('post_answers')->where('id', $ansid)->update(['post_answers' => $updateAns,'file'=>$filename]);
    }
    
    //Save Data in Database.
    DB::table('post_answers')->where('id', $ansid)->update(['post_answers' => $updateAns]);
    return redirect()->route('editans',$send_ansid);
}

//Delete Ans Function.
public function deleteAns($id){
    $ansid = decrypt($id);
    DB::table('post_answers')->where('id', $ansid)->delete();
    return redirect()->route('myposts');
}


//Settings Function.
public function settings(){
    $user = Auth::user();
    $id=  $user->id;
    $udetails =  DB::select('SELECT * FROM users WHERE id='.$id);
    $category =  DB::select('SELECT id, cat_name FROM categories');
    $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
    return view('settings')->with(compact('udetails'))->with(compact('category'))->with(compact('tags'));
    
}//End function.

public function updateUserInfo(Request $request){
    
    $this->validate($request, [
            'pic' => 'mimes:jpeg,jpg,png,gif',
            'pic' => 'max:2000',
    ]);
    if(request()->file('pic') !=""){
        
        $pic = request()->file('pic');
        $newname = time().'.'.$pic->getClientOriginalName();
     
        $ufolder = 'uploads/images';
        $path = public_path($ufolder);
        
        if(!is_dir($path)) {
             $response = Storage::makeDirectory($ufolder);
        }
        
        $pic->move($path, $newname);
        
        $upic='uploads/images/'.$newname;
        $user = Auth::user();
        $id=  $user->id;
        $res= DB::table('users')->where('id', $id)->update(array('pic' => $upic)); 
        if($res){
        return Redirect::to('usersettings')->with('message', 'User profile pic updated successfully.');
        }
        
    }else{
        $user = Auth::user();
        $id=  $user->id;
        $uname=  Input::get('name');  
        $res= DB::table('users')->where('id', $id)->update(array('name' => $uname)); 
        if($res){
        return Redirect::to('usersettings')->with('message', 'User Name Info updated successfully.');
        }
    }
    
}
//Update Password Function.
public function updatePassword(Request $request){
    $this->validate($request, [
         'new_password' => 'string|min:6',
    ]);
    $user = Auth::user();
    $id=  $user->id;
    $newpas=  Input::get('new_password'); 
    $res= DB::table('users')->where('id', $id)->update(array('password' => bcrypt($newpas))); 
    if($res){
        return Redirect::to('usersettings')->with('message', 'Password updated successfully.');
    }

}//End function.
  

}//End Class
