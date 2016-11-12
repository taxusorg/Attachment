<?php
return [
    'default' => 'public',
    
    'disks' => [
        'public' => [
            'filesystem' => 'local',
            'model' => 'App\Attachment',
        ]
    ]
];