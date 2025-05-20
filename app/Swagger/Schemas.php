<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="username", type="string", example="johndoe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe")
 * )
 */

 /**
 * @OA\Schema(
 *     schema="ManualHtmlResponse",
 *     required={"locale", "html"},
 *     @OA\Property(property="locale", type="string", example="en"),
 *     @OA\Property(property="html", type="string", description="Rendered HTML string")
 * )
 */

/**
 * @OA\Schema(
 *     schema="DownloadErrorResponse",
 *     description="Error response when file cannot be downloaded",
 *     @OA\Property(property="message", type="string", example="File not found or link expired.")
 * )
 */

 /**
 * @OA\Schema(
 *     schema="ActivityLogEntry",
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="username", type="string", example="john_doe")
 *     ),
 *     @OA\Property(property="action", type="string", example="merge"),
 *     @OA\Property(property="access_method", type="string", example="api"),
 *     @OA\Property(property="details", type="string", example="Merged 3 PDF files"),
 *     @OA\Property(property="ip_address", type="string", example="192.168.1.10"),
 *     @OA\Property(property="city", type="string", example="Bratislava"),
 *     @OA\Property(property="country", type="string", example="Slovakia"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-12-01T12:34:56Z")
 * )
 */
class Schemas {}
