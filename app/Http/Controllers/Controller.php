<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Primea Ticketing API",
 *     version="1.0.0",
 *     description="API documentation for Primea Ticketing System - A comprehensive event ticketing platform",
 *     @OA\Contact(
 *         email="support@primea.com"
 *     ),
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Events",
 *     description="API Endpoints for managing events"
 * )
 * @OA\Tag(
 *     name="Tickets",
 *     description="API Endpoints for managing tickets"
 * )
 * @OA\Tag(
 *     name="Orders",
 *     description="API Endpoints for managing orders"
 * )
 * @OA\Tag(
 *     name="Payments",
 *     description="API Endpoints for processing payments"
 * )
 * @OA\Tag(
 *     name="Scanning",
 *     description="API Endpoints for ticket scanning and validation"
 * )
 * @OA\Tag(
 *     name="Organizers",
 *     description="API Endpoints for managing event organizers"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
