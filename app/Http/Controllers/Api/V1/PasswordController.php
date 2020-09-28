<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator as Validate;

class PasswordController extends Controller
{
    /**
     * @OA\Post(
     ** path="/api/v1/forgotPassword",
     *   tags={"Password"},
     *   summary="Forgot Password",
     *   operationId="forgotPassword",
     *
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"email"},
     *               @OA\Property(
     *                  property="email",
     *                  type="string"
     *               ),
     *           )
     *       ),
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * forgotPassword api
     *
     * @return \Illuminate\Http\Response
     */
    public function forgot(Request $request) {
        $rules = [
            'email' => "required|email|exists:users,email",
        ];
        $messages = [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter valid email address.',
            'email.exists' => 'The selected email is not registered.',
        ];
        Validate::make($request->all(), $rules, $messages)->validate();
        $credentials = ['email' => $request->email];

        Password::sendResetLink($credentials);
        return response()->json(["message" => 'Reset password link sent on your email id.'], 200);
    }

    /**
     * @OA\Post(
     ** path="/api/v1/resetPassword",
     *   tags={"Password"},
     *   summary="Reset Password",
     *   operationId="resetPassword",
     *
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"email","token","password","password_confirmation"},
     *               @OA\Property(
     *                  property="email",
     *                  type="string"
     *               ),
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *               ),
     *              @OA\Property(
     *                  property="password",
     *                  format = "password",
     *                  type="string"
     *               ),
     *              @OA\Property(
     *                  property="password_confirmation",
     *                  format = "password",
     *                  type="string"
     *               ),
     *           )
     *       ),
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * forgotPassword api
     *
     * @return \Illuminate\Http\Response
     */
    public function reset() {
        $credentials = request()->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);


        $reset_password_status = Password::reset($credentials, function ($user, $password) {
            $user->password = bcrypt($password);
            $user->save();
        });

        if ($reset_password_status == Password::INVALID_TOKEN) {
            return response()->json(["message" => "The given data was invalid.", "errors" => ["token" => ["Invalid token provided."]]], 422);
        }

        return response()->json(["message" => "Password has been successfully changed"], 200);
    }
    /**
     * @OA\Post(
     *          path="/api/v1/changePassword/{id}",
     *          operationId="Change Password",
     *          tags={"Password"},
     *          summary="Change Password",
     *  @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *  @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"old_password","password","password_confirmation"},
     *               @OA\Property(
     *                  property="old_password",
     *                  format = "password",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="password",
     *                  format = "password",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="password_confirmation",
     *                  format = "password",
     *                  type="string"
     *               ),
     *           )
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
    public function change(Request $request,$id){
        $user = User::find($id);

        $this->validate($request, [
            'old_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    return $fail(__('The current password is incorrect.'));
                }
            }],
            'password'      => 'required|string|confirmed',
        ]);
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'message' => 'Successfully password changed!'
        ], 200);

    }
}
