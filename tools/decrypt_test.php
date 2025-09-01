<?php
// Small debug helper to test decryption of a stored message file_blob
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;
use App\Services\EncryptionService;

$m = Message::whereNotNull('file_blob')->where('file_blob','<>','')->first();
if (!$m) {
    echo "NO_MESSAGE\n"; exit(0);
}

echo "FOUND_MESSAGE ID={$m->id} MIME={$m->file_mime}\n";
$enc = app(EncryptionService::class);
try {
    $plain = $enc->decrypt($m->file_blob, 'chat_file');
    $len = strlen($plain);
    echo "DECRYPT_OK length={$len}\n";
    $out = __DIR__ . DIRECTORY_SEPARATOR . 'decrypted_'. $m->id;
    file_put_contents($out, $plain);
    echo "WROTE_TO {$out}\n";
} catch (\Throwable $e) {
    echo "DECRYPT_ERR: " . $e->getMessage() . "\n";
}
