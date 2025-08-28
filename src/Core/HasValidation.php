<?php
namespace App\Core;

trait HasValidation {
    public function validate(array $rules, array $data) {
        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            return $validator->errors();
        }
        return [];
        // (new Validator())->validate($data, $rules);
    }
}