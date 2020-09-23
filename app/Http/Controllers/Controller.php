<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="Integration Swagger in Laravel with Passport Auth Documentation",
     *      description="Implementation of Swagger with in Laravel",
     *      @OA\Contact(
     *          email="admin@admin.com"
     *      ),
     *      @OA\License(
     *          name="Apache 2.0",
     *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *      )
     * )
     *
     *
     *
     */
     /**
     * @OA\SecurityScheme(
     *     type="oauth2",
     *     description="Laravel passport oauth2 security",
     *     name="Password Based",
     *     in="header",
     *     scheme="https",
     *     securityScheme="passport",
     *     @OA\Flow(
     *         flow="password",
     *         authorizationUrl="http://127.0.0.1:8000/oauth/authorize",
     *         tokenUrl="http://127.0.0.1:8000/oauth/token",
     *         refreshUrl="http://127.0.0.1:8000/oauth/token/refresh",
     *         scopes={}
     *     )
     * )
     */
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}