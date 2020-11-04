<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\PostAnswers;
use App\Post;
use DB;
use Redirect,Response;
use Auth;
use Illuminate\Support\Facades\Input;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MypostsController extends Controller
{
    
/**
 * Create a new controller instance.
 *
 * @return void
 */
public function __construct()
{
    $this->middleware('auth');
}

    public function paginate($items, $perPage = 30, $page = null, $options = [])
    {
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);
    return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
  
/**
 * Show the application dashboard.
 *
 * @return \Illuminate\Contracts\Support\Renderable
 */
public function welcome()
{
    $userpostslist = Post::All();
    return view('welcome', compact('userpostslist'));
}
    

public function index()
{
    /****Loged in Users Questions Tab section array start here***/
    $user = Auth::user();
    $userpostslist = DB::table('posts')->where('post_user_id', $user->id)->orderBy('id', 'desc')->pluck('id','post_title');

    $qlist=array();
    $_userpostslist=array();
    foreach($userpostslist as $q => $id){ 
    $qlist=array("$q","$id");
    $ans_list = DB::table('post_answers')->select('id','post_answers_user_id','post_id','post_answers','file','updated_at')->where('post_id',$id)->get();
    $anslist=array();
    $_ansAll=array();
    if(!empty($ans_list)){
    foreach($ans_list as $_list){
        $user =  User::find($_list->post_answers_user_id);
        $anslist['answered_user_pic'] = $user['pic'];
        $anslist['name'] = $user['name'];
        $anslist['ans_id'] = $_list->id;
        $anslist['post_id'] = $_list->post_id;
        $anslist['post_answers'] = $_list->post_answers;
        $anslist['file'] = $_list->file;
        $anslist['updated_at'] = $_list->updated_at;
        $userpost = DB::table('posts')->where('id', $_list->post_id)->pluck('post_title');
        $anslist['post'] = $userpost[0];
        $_ansAll['questionAns'][]= $anslist;
        }
        
        
    }else{
        $_ansAll['questionAns'][]= array();
    }
    $_userpostslist[] = array_merge($qlist,$_ansAll);
       
    }/*** END ARRAY***/
    
     $userpostslistAll = $this->paginate($_userpostslist);
    
    /****Loged in Users ANSWER Tab section array start here****/
    $user_Id = Auth::user();
    $_ans_list = DB::table('post_answers')->select('id','post_answers_user_id','post_id','post_answers','file','updated_at')->where('post_answers_user_id', $user_Id->id)->orderBy('id', 'desc')->get();
    //dd($_ans_list);
    $_anslist=array();
    $anALL=array();
    if(!empty($_ans_list)){
        foreach($_ans_list as $_lists){
            $user =  User::find($_lists->post_answers_user_id);
            $_anslist['answered_user_pic'] = $user->pic;       
            $_anslist['name'] = $user->name;  
            $_anslist['ans_id'] = $_lists->id;
            $_anslist['post_id'] = $_lists->post_id;
            $_anslist['post_answers'] = $_lists->post_answers;
            $_anslist['file'] = $_lists->file;
            $_anslist['updated_at'] = $_lists->updated_at;
            $userpost = DB::table('posts')->where('id', $_lists->post_id)->pluck('post_title');
            //dd($userpost);
            if(!empty($userpost)){
                $_anslist['post'] = $userpost[0];
            }else{
                $_anslist['post'] = array();
            }
           $anALL['questionAns'][]= $_anslist;
           
        }/*** END ARRAY***/
    
    }else{
        $anALL['questionAns'][]= array();
    }
    //dd($anALL);
    $ansAll = $this->paginate($anALL);
    $category =  DB::select('SELECT id, cat_name FROM categories');
    $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
    return view('myposts', compact('userpostslistAll'))->with(compact('ansAll'))->with(compact('category'))->with(compact('tags'));
}//End Function.
 
    
//Get All Users Answers Function.
public function getAllUserAnswers()
{   $user = Auth::user();
    $anslist = DB::table('post_answers')->where('post_answers_user_id', $user->id);
    return view('myposts', compact('anslist'));
    
}//End function.
  

//User Status Function.
public function userStatus($id, $status)
{
    $res = DB::table('users')->where('id', $id)->update(array('activeStatus' => $status));
    return Response::json($res);
   
}//End function.

//Ans List Function.   
public function anspage($_id){
    $id = decrypt($_id);
    $user = Auth::user();
    $postquestions_Q = DB::table('posts')->where('id', $id)->pluck('id','post_title');
    $postquestionsQ['question'] =  $postquestions_Q;
    $_postquestions = DB::table('posts')
    ->select('post_answers.*','users.pic as upic')
    ->rightJoin('post_answers', 'post_answers.post_id', '=', 'posts.id')
    ->rightJoin('users', 'users.id', '=', 'post_answers.post_answers_user_id')
    ->where('post_answers.post_id', $id)
    ->get();
    
    $editAnsArray=array();
    $narray_s = DB::select("SELECT id,file,post_answers FROM `post_answers` WHERE post_id='$id' AND post_answers_user_id=".$user->id);
    //dd($narray_s);
    if(!empty($narray_s)){
    $editAns['id'] = $narray_s[0]->id;
    $editAns['file'] = $narray_s[0]->file;
    $editAns['post_answers'] =  $narray_s[0]->post_answers;
    $editAnsArray['editAns'][] = $editAns;
    }
    
   
    $_anslist= array();
    $dataans_commt=array();
    $dataans_commt['answers']=array();
    foreach($_postquestions as $data){ //answer foreach start.
        $user =  User::find($data->post_answers_user_id);
        if($user->pic_type == 'web'){
             $_anslist['answered_user_pic'] = "../".$user->pic;
        }else{
            $_anslist['answered_user_pic'] = $user->pic;
        }
        $_anslist['name'] = $user->name;
        $_anslist['id'] = $data->id;
        $_anslist['post_id'] = $data->post_id;
        $_anslist['post_answers'] = $data->post_answers;
        $_anslist['file'] = $data->file;
        $_anslist['created_at'] = $data->created_at;
        
        $totalVoteToAns =  DB::select('SELECT total_vote_to_ans,total_comments_to_ans FROM `post_answers` WHERE id='.$data->id.' GROUP BY id');
        $_anslist['total_vote_to_ans']= $totalVoteToAns[0]->total_vote_to_ans;
        $_anslist['total_comments_to_ans']= $totalVoteToAns[0]->total_comments_to_ans;

        $ans_list = DB::table('users_comments_to_posts')->select('id','user_id','post_id','comments','comments','updated_at')->where('answer_id', $data->id)->get();
            $anslist=array();
            $comments=array();
            $comments['comments']=array();
            foreach($ans_list as $_list){ //comments foreach start.
                $user =  User::find($_list->user_id);
                if($user->pic_type == 'web'){
                    $anslist['commented_user_pic'] = "../".$user->pic;
                }else{
                    $anslist['commented_user_pic'] = $user->pic;
                }
                $anslist['commented_user_name'] = $user->name;
                $anslist['id'] = $_list->id;
                $anslist['comments'] = $_list->comments;
                $anslist['updated_at'] = $_list->updated_at;
                $comments['comments'][]= $anslist;  
            }//end comments foreach.
        
            $dataans_commt['answers'][] = array_merge($_anslist,$comments);
         
        }//end main foreach.
    $data_postquestions= array_merge($postquestionsQ,$dataans_commt,$editAnsArray);
    //dd($data_postquestions);
    $category =  DB::select('SELECT id, cat_name FROM categories');
    $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
    return view('postanswer', compact('data_postquestions'))->with(compact('category'))->with(compact('tags'));
}//End function.


    
}//End class.
