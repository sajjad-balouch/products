<?php

return [
    'component_layout' => 'layouts.app',
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:5120'],
    ],
];
