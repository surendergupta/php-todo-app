<?php
namespace App\Models;

class Todo
{
    public function __construct(
        private int $id,
        private string $title,
    ) {}

    // --- Getters ---
    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    // --- Setters (optional, if you allow editing) ---
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    // --- (Optional) convert to array for JSON response ---
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
        ];
    }
}
