<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Album;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
   /**
     * @OA\Post(
     *          path="/api/v1/addAlbum/{userid}",
     *          operationId="Add User Album",
     *          tags={"Albums"},
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Add User Album",
     *      description="data of users album",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Name"},
     *               @OA\Property(
     *                  property="Name",
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function addAlbum(Request $request,$id){
        
        $validator = Validator::make($request->all(),[
            'Name' => 'required',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }
        $album =  new Album([
            'name' => $request->Name,
        ]);
        
        if($album->save()){
            $album->users()->sync($id);
            $postData = $this->getAlbumResponse($album->id);
            return response()->json([
                'message' => 'Album created successfully!',
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
        
        
        return response()->json(album::find($id));
    	
    }


    /**
     * @OA\Post(
     *          path="/api/v1/updateAlbum/{albumid}",
     *          operationId="Update User Album",
     *          tags={"Albums"},
     *      @OA\Parameter(
     *          name="albumid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Update User Album",
     *      description="data of users album",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Name"},
     *               @OA\Property(
     *                  property="Name",
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function updateAlbum(Request $request,$id){
        
        $validator = Validator::make($request->all(),[
            'Name' => 'required',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }
        $albumDetails = Album::where('id', $id)->update([
            'name' => $request->Name,
            
        ]);

        if($albumDetails){
            $albumData = $this->getAlbumResponse($id);
            return response()->json([
                'message' => 'Album updated successfully!',
                'data' => $albumData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
        
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getUserAlbum/{userid}",
     *          operationId="Get User Albums",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Get User Albums",
     *      description="data of user albums",
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

    public function getUserAlbum($id){
        
        //echo $id; exit;
        $user = User::findOrFail($id);
        $albumDetails = $user->albums()->get();
        $i=0;
        foreach($albumDetails as $albumDetail){
            $albumData[$i]['id'] = $albumDetail['id'];
            $albumData[$i]['name'] = $albumDetail['name'];
            $i++;
        }
        if(count($albumDetails)){
            return response()->json([
                'message' => 'User album list!',
                'data' => $albumData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No album available','isError' => true]);
        }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/deleteAlbum/{albumid}",
     *          operationId="Delete User Album Details",
     *          tags={"Albums"},
     *      @OA\Parameter(
     *          name="albumid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Delete User Album Details",
     *      description="delete data of user album",
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

    public function deleteAlbum($id){
        if(Album::find($id)->delete()){
            return response()->json([
                'message' => 'Successfully deleted album!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => false]);
        }
    	
    }

    function getAlbumResponse($postId){
        $album = Album::find($postId);
        $albumData['id'] = $album['id'];
        $albumData['Name'] = $album['name'];
        $albumData['UserId'] = (isset($album->users->first()->id)) ? $album->users->first()->id : '';
        return $albumData;
    }

}
