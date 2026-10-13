<?php

namespace App\Model;

final class Book
{
    public function __construct(
        public int $id,
        public string $title
    ) {}
}