<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use DB;
use App\Post;
use Redirect,Response;
use Auth;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WelcomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function index()
    {   //DB::enableQueryLog();
        $_userpostslist=array();
        $qlist=array();
     
        $ansArray=array();
        $user_postslist = DB::table('posts')->orderBy('id', 'desc')->pluck('id','post_title');
        foreach($user_postslist as $q => $id){ 
            
        $qlist=array("$q","$id");
        
        $narray_s =  DB::select('SELECT * FROM `post_answers` WHERE total_vote_to_ans =(select max(total_vote_to_ans) from post_answers where post_id='.$id.') and post_id='.$id.' GROUP BY post_id');
       //dd(DB::getQueryLog());
        $narray=array(); 
        foreach($narray_s as $ndata){
            $ansArray['id'] = $ndata->id;
            $ansArray['post_id'] = $ndata->post_id;
            $ansArray['post_answers_user_id'] = $ndata->post_answers_user_id;
            $ansArray['post_answers'] = $ndata->post_answers;
            $ansArray['file'] = $ndata->file;
            $ansArray['filetype'] = $ndata->filetype;
            $ansArray['total_vote_to_ans'] = $ndata->total_vote_to_ans;
            $ansArray['total_comments_to_ans'] = $ndata->total_comments_to_ans;
            $ansArray['post_ans_status'] = $ndata->post_ans_status;
            $ansArray['created_at'] = $ndata->created_at;
            $ansArray['updated_at'] = $ndata->updated_at;
            $user =  User::find($ndata->post_answers_user_id);
            $ansArray['answered_user_pic'] = $user->pic;
            $ansArray['name'] = $user->name;
            $narray[] = $ansArray;
        }
            $_userpostslist[] = array_merge($qlist,$narray);
           // dd($_userpostslist);
            $userpostslist = $this->paginate($_userpostslist);
        }
        
        
        if(@auth()->user()->is_admin == 1) {
        return redirect()->route('home');
        }else{
            $category =  DB::select('SELECT id, cat_name FROM categories');
            $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
            return view('welcome')->with(compact('userpostslist'))->with(compact('category'))->with(compact('tags'));
        }
        
    }//End Index function.
    
    
    
    //Pagination function...
    public function paginate($items, $perPage = 30, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }// End //Pagination function 
    
    
    //Auto Search Complete.
    public function autoComplete(Request $request)
    {
        $data = Post::select("post_title as name")->where("post_title","LIKE","%{$request->input('query')}%")->get();
        return response()->json($data);
    }
    
    public function autoCompleteTags(Request $request)
    {
        $searchResult = DB::table('tags')->where('tag_name','LIKE',"%{$request->input('tag_name')}%")->groupBy('id')->get();
        return response()->json($searchResult);
    }
   
   
    //Search Result.
    Public function searchResult(Request $request){
        $_userpostslist= array();
        $qlist=array();
        $userpostslist=array();
        $anslist=array();
         
        $searchResult = DB::table('posts')->where('post_title','LIKE','%'.$request->search."%")->orderBy('id', 'desc')->get();
        foreach($searchResult as $data){ 
        $qlist=array("$data->post_title","$data->id;"); 
        $narray_s = DB::select('SELECT * FROM `post_answers` WHERE post_id='.$data->id);
            $anslist=array();
            $_ansAll=array();
            foreach($narray_s as $_list){
            
            $user =  User::find($_list->post_answers_user_id);
            //dd($user);
        if($user['pic_type'] == 'web'){
             $anslist['answered_user_pic'] = "/allaboutseo/public/".$user['pic'];
        }else{
            $anslist['answered_user_pic'] = $user['pic'];
        }
            
            $anslist['name'] = $user['name'];
            $anslist['ans_id'] = $_list->id;
            $anslist['post_id'] = $_list->post_id;
            $anslist['post_answers'] = $_list->post_answers;
            $anslist['file'] = $_list->file;
            $anslist['total_vote_to_ans'] = $_list->total_vote_to_ans;
            $anslist['total_comments_to_ans'] = $_list->total_comments_to_ans;
            $anslist['updated_at'] = $_list->updated_at;
            
            $userpost = DB::table('posts')->where('id', $_list->post_id)->pluck('post_title');
            $anslist['post'] = $userpost[0];
            $_ansAll['questionAns'][]= $anslist;
            
            }
        $_userpostslist[] = array_merge($qlist,$_ansAll);
        }
        $userpostslist = $this->paginate($_userpostslist);
        $category =  DB::select('SELECT id, cat_name FROM categories');
        $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
        return view('searchresult', compact('userpostslist'))->with(compact('category'))->with(compact('tags'));
     
    }// End Search Result.
   
 
}//End Class.
