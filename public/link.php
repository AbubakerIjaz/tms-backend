<?php
$targetFolder = __DIR__.'/../storage/app/public';
$linkFolder = __DIR__.'/storage';

if (file_exists($linkFolder)) {
    echo "The 'storage' link already exists.";
} else {
    if (symlink($targetFolder, $linkFolder)) {
        echo "Storage link created successfully!";
    } else {
        echo "Failed to create storage link. Please check folder permissions.";
    }
}
?>