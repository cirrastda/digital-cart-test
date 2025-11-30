<?php

namespace App;

/**
 * @OA\Info(
 *   title="Digital Cart API",
 *   version="1.0.0"
 * )
 *
 * @OA\Server(
 *   url="http://localhost:8080/",
 *   description="Ambiente local"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="sanctum",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Token"
 * )
 *
 * @OA\Tag(name="Usuários")
 * @OA\Tag(name="Transações")
 */
class OpenApi
{
    // 
}