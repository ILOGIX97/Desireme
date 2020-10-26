<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogController extends Controller
{
   /**
     * @OA\Post(
     *          path="/api/v1/addBlog",
     *          operationId="Add Blog",
     *          tags={"Blogs"},
     *      summary="Add Blog",
     *      description="data of Blog",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(
     *                  property="Title",
     *                  type="string"
     *               ),
     *                @OA\Property(
     *                  property="Category",
     *                  type="string"
     *               ),
     *                @OA\Property(
     *                  property="Content",
     *                  type="string",
     *               ),
     *                @OA\Property(
     *                  property="Image",
     *                  type="string"
     *               ),
     *          )
     *       ),
     *   ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *  )
     */

    public function addBlog(Request $request){
        //echo '<pre>'; print_r($request->all()); exit;
        $title = $request->Title;
        $category = $request->Category;
        $content = $request->Content;
        $image = $request->Image;
        $path = 'public/documents/blog/';
        $media = $this->createImage($image,$path);
        $data = DB::table('blogs')
               ->insert([
                'title' => $title,
                'category' => $category,
                'content'=>$content,
                'image'=>$media,
             ]);
        return response()->json([
            'message' => 'Blog created!',
            'data' => $data,
            'isError' => false
        ], 201);
            
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getBlogs/{start}/{limit}",
     *          operationId="Get Blogs",
     *          tags={"Blogs"},
     *      
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Get Blogs",
     *      description="data of Blogs",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *      security={ {"passport": {}} },
     *  )
     */

    public function getBlogs($start,$limit){
        $blogs = DB::table('blogs')->orderBy('id','DESC')->offset($start)->limit($limit)->get();
        $ID = 0;
        $blogData = array();
        foreach($blogs as $blog){
            $blogData[$ID]['title'] = $blog->title;
            $blogData[$ID]['caption'] = $blog->category;
            $blogData[$ID]['image'] = (!empty($blog->image) ? url('storage/'.$blog->image) : '');
            $blogData[$ID]['content'] = $blog->content;
            $blogData[$ID]['created'] = \Carbon\Carbon::parse($blog->created_at)->isoFormat('D MMMM YYYY');
            $blogData[$ID]['slug'] = str_replace(" ","-",$blog->title);
            $ID++;
        }

        if(count($blogs)){
            return response()->json([
                'message' => 'Blog list!',
                'count' => count($blogs),
                'data' => $blogData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Blog available','isError' => true]);
        }


    }

    /**
     * @OA\Post(
     *          path="/api/v1/searchBlog/{search}/{start}/{limit}",
     *          operationId="Post list",
     *          tags={"Blogs"},
     *      @OA\Parameter(
     *          name="search",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Get list of posts",
     *      description="Returns list of post",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *      security={ {"passport": {}} },
     *  )
     */
    public function searchBlog($search,$start,$limit){
        if(!empty($limit)){
            $blogs = DB::table('blogs')->where('title','LIKE', '%' . $search . '%')
            ->orWhere('category', 'LIKE','%' . $search . '%')
            ->orWhere('content', 'LIKE','%' . $search . '%')
            ->offset($start)->limit($limit)
            ->get();
        }else{
            $blogs = DB::table('blogs')->where('title','LIKE', '%' . $search . '%')
            ->orWhere('category', 'LIKE','%' . $search . '%')
            ->orWhere('content', 'LIKE','%' . $search . '%')
            ->get();
        }
        $allBlog = DB::table('blogs')->where('title','LIKE', '%' . $search . '%')
        ->orWhere('category', 'LIKE','%' . $search . '%')
        ->orWhere('content', 'LIKE','%' . $search . '%')
        ->get();
        $blogData = array();
        $ID = 0;
        foreach($blogs as $blog){
            $blogData[$ID]['title'] = $blog->title;
            $blogData[$ID]['caption'] = $blog->category;
            $blogData[$ID]['image'] = (!empty($blog->image) ? url('storage/'.$blog->image) : '');
            $blogData[$ID]['content'] = $blog->content;
            $blogData[$ID]['created'] = \Carbon\Carbon::parse($blog->created_at)->isoFormat('D MMMM YYYY');
            $blogData[$ID]['slug'] = str_replace(" ","-",$blog->title);
            $ID++;
        }

        return response()->json([
            'count' => count($allBlog),
            'data' => $blogData,
            'isError' => false
        ]);
    }

     /**
     * @OA\Post(
     *          path="/api/v1/getBlogDetail/{id}",
     *          operationId="Get Blog detail",
     *          tags={"Blogs"},
     *      summary="Get Blog detail",
     *      description="data of Blog",
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *      security={ {"passport": {}} },
     *  )
     */

    public function getBlogDetail($id){

        $blogs = DB::table('blogs')->where('id',$id)->get();
        $blogData = array();
        $ID = 0;
        foreach($blogs as $blog){
            $blogData[$ID]['title'] = $blog->title;
            $blogData[$ID]['caption'] = $blog->category;
            $blogData[$ID]['image'] = (!empty($blog->image) ? url('storage/'.$blog->image) : '');
            $blogData[$ID]['content'] = $blog->content;
            $blogData[$ID]['created'] = \Carbon\Carbon::parse($blog->created_at)->isoFormat('D MMMM YYYY');
            $blogData[$ID]['slug'] = str_replace(" ","-",$blog->title);
            $ID++;
        }
        if(count($blogs)){
            return response()->json([
                'message' => 'Blog Data!',
                'data' => $blogData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Blog available','isError' => true]);
        }

    }

    function createImage($image,$path){
        if (preg_match('/^data:image\/\w+;base64,/', $image)) {
            $ext = explode(';base64',$image);
            $ext = explode('/',$ext[0]);
            $ext = $ext[1];
            if (preg_match('/^data:image\/\w+;base64,/', $image)){
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
            }

            
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10).'.'.$ext;
            $full_path = $path . $imageName;
            Storage::put($full_path, base64_decode($image));
            $returnpath = str_replace("public/","",$path).$imageName;

        }else {
            $removeData = config('app.url').'storage/';
            $returnpath = str_replace($removeData,"",$image);
        }
        return $returnpath;

    }





    

}
