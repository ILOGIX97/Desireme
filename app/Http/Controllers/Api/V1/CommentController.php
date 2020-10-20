<?php

namespace App\Http\Controllers\API\V1;

use App\Comment;
use App\Http\Controllers\Controller;
use App\User;
use App\Post;
use App\comment_like;
use App\comment_comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;



class CommentController extends Controller
{

    /**
     * @OA\Post(
     *          path="/api/v1/likeComment/{commentid}/{userid}",
     *          operationId="Like Post Comment",
     *          tags={"Post Comment"},
     *      @OA\Parameter(
     *          name="commentid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Like Post Comment",
     *      description="Like Post Comment",
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

    public function likeComment($commentid,$userid){

        //echo $request->ScheduleDateTime; exit;
        $comment_like =  new comment_like([
            'comment_id' => $commentid,
            'user_id' => $userid,
        ]);

        $users = comment_like::where('comment_id',$commentid)->where('user_id',$userid)->get();
        //$postData = $this->getCommentResponse($postid);
        if(count($users) == 0){
            if($comment_like->save()){
                $postData = $this->getCommentResponse($commentid);
                return response()->json([
                    'message' => 'Comment liked successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
        }else{

            if(comment_like::where('comment_id',$commentid)->where('user_id',$userid)->delete())
            {
                $postData = $this->getCommentResponse($commentid);
                return response()->json([
                    'message' => 'Comment disliked successfully!',
                    'data' => $postData,
                    'isError' => false,
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => false]);
            }
        }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/CommentComment/{commentid}/{userid}",
     *          operationId="Comment on Post Comment",
     *          tags={"Post Comment"},
     *      @OA\Parameter(
     *          name="commentid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Comment on Post Comment",
     *      description="Comment on Post Comment",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Comment"},
     *               @OA\Property(
     *                  property="Comment",
     *                  type="string"
     *               ),
     *           )
     *       ),
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

    public function CommentComment(Request $request,$commentid,$userid){

        //echo $request->ScheduleDateTime; exit;
        $validator = Validator::make($request->all(),[
            'Comment' => 'required',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        $comment_comment =  new comment_comment([
            'comment_id' => $commentid,
            'user_id' => $userid,
            'comment' => $request->Comment,
        ]);

        
        if($comment_comment->save()){
            $postData = $this->getCommentResponse($commentid);
            return response()->json([
                'message' => 'Comment commented successfully!',
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
    }

    function getCommentResponse($commentId){
        $postDetail = Comment::find($commentId);

        $likeDetails = comment_like::where('comment_id',$commentId)->get();
        $likeUsers = array();
        if(count($likeDetails) > 0){
            $i = 0;
            foreach($likeDetails as $likeDetail){
                $likeUsers[$i]['userid'] = $likeDetail['user_id'];
                $i++;
            }
        }

        $commentDetails = comment_comment::where('comment_id',$commentId)->get();
        $commentUsers = array();
        if(count($commentDetails) > 0){
            $i = 0;
            foreach($commentDetails as $commentDetail){
                $commentUsers[$i]['userid'] = $commentDetail['user_id'];
                $commentUsers[$i]['comment'] = $commentDetail['comment'];
                $i++;
            }
        }

        $postData['id'] = $postDetail['id'];
        $postData['title'] = $postDetail['comment'];
        $postData['created'] = \Carbon\Carbon::parse($postDetail['created_at'])->format('Y-m-d H:i:s');
        $postData['updated'] = \Carbon\Carbon::parse($postDetail['updated_at'])->format('Y-m-d H:i:s');
        $postData['likes'] = count($likeDetails);
        $postData['likeUsers'] = $likeUsers;
        $postData['comments'] = count($commentDetails);
        $postData['commentUsers'] = $commentUsers;
       

        return $postData;
    }

    

}
