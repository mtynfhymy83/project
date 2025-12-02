<?php

namespace App\DTOs\Book;

class BookDetailDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $userId = null,
    ) {}

    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            id: (int) $data['id'],
            userId: $userId,
        );
    }
}

class BookListDTO
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $search = null,
        public readonly ?string $sort = 'latest', // latest, popular, rating
        public readonly bool $freeOnly = false,
        public readonly bool $specialOnly = false,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            search: $data['search'] ?? null,
            sort: $data['sort'] ?? 'latest',
            freeOnly: (bool) ($data['free_only'] ?? false),
            specialOnly: (bool) ($data['special_only'] ?? false),
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($data['per_page'] ?? 20))),
        );
    }
}

class ReadContentDTO
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $pageNumber,
        public readonly ?int $userId = null,
    ) {}

    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            bookId: (int) $data['book_id'],
            pageNumber: (int) $data['page_number'],
            userId: $userId,
        );
    }
}
