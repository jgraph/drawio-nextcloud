<?php

return [
    "routes" => [
       ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],
       ["name" => "editor#create", "url" => "/ajax/new", "verb" => "POST"],

       //["name" => "editor#public_page", "url" => "/s/{shareToken}", "verb" => "GET"],
       //["name" => "editor#public_file", "url" => "/ajax/shared/{fileId}", "verb" => "GET"],

       ["name" => "viewer#public_page", "url" => "/s/{shareToken}", "verb" => "GET"],
       ["name" => "viewer#public_file", "url" => "/ajax/shared/{fileId}", "verb" => "GET"],

       ["name" => "settings#settings", "url" => "/ajax/settings", "verb" => "POST"],
       ["name" => "settings#getsettings", "url" => "/ajax/settings", "verb" => "GET"],
    ]
];
