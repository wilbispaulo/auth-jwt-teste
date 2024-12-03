<?php

namespace core\library;

class Request
{
    private $postData;
    private array $filename = [];

    public function __construct(
        private array $files
    ) {
        if (strstr($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->postData = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
        } else if (strstr($_SERVER['CONTENT_TYPE'], 'application/json')) {
            $data = file_get_contents('php://input');
            $this->postData = json_decode($data, true);
        }

        if (count($_FILES) > 0) {
            foreach ($this->files as $index => $value) {
                $this->filename[$index] = $value;
            }
        }
    }

    public static function create()
    {
        return new static($_FILES);
    }

    public function getTempName(string $id): string|bool
    {
        return $this->filename[$id]['tmp_name'] ?? false;
    }

    public function getData(string $item): string|bool
    {
        return $this->postData[$item] ?? false;
    }

    public function getAll(): mixed
    {
        return $this->postData;
    }
}
