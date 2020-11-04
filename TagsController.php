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

class TagsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
       //Pagination function...
    public function paginate($items, $perPage = 5, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);

    }// End //Pagination function 
   
   //Get Category Related Data From Database.
    public function index($_id)
    {
        $id = decrypt($_id);
        //$id = $_id;
        
        $catData = DB::table('posts')->whereRaw("FIND_IN_SET($id,tags_id)")->get();
      
        $qlist=array();
        $m_userpostslist=array();
        foreach($catData as $data){
        $qlist=array("$data->post_title","$data->id"); 
        $narray_s = DB::select('SELECT * FROM `post_answers` WHERE post_id='.$data->id);
        
            $anslist=array();
            $_ansAll=array();
            
            foreach($narray_s as $_list){
            $user =  User::find($_list->post_answers_user_id);
            //dd($user);
            
            if($user->pic_type == 'web'){
            $anslist['answered_user_pic'] = "../".$user->pic;
            }else{
            $anslist['answered_user_pic'] = $user->pic;
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
            }//End Ans foreach.
            $m_userpostslist[] = array_merge($qlist,$_ansAll);
            
        }//End cat foreach.
        
        $userpostslist = $this->paginate($m_userpostslist);
        
        $category =  DB::select('SELECT id, cat_name FROM categories');
        $tagName =  DB::select('SELECT id,tag_name FROM tags where id='.$id);
        $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
        return view('tagsResult')->with(compact('userpostslist'))->with(compact('category'))->with(compact('tags'))->with(compact('tagName'));
    }//End Index function.
    
    //Show all tags in one page.
    public function tagslist()
    {
        $category =  DB::select('SELECT id, cat_name FROM categories');
        $tags =  DB::select('SELECT id,tag_name FROM tags LIMIT 10');
        $data_tags =  DB::select('SELECT id,tag_name FROM tags');
        return view('tagsList')->with(compact('category'))->with(compact('tags'))->with(compact('data_tags'));
    }

}//End Class.
