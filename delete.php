<?php

require __DIR__ . '/vendor/autoload.php';
require 'util.php';

/**
 * Deleta arquivo no Google Drive
 * @param GoogleClient $client Cliente do Google Drive.
 * @param string $fileId ID do arquivo no Google Drive.
 */
function deleteFile($client, $fileId)
{
    $service = new Google_Service_Drive($client);

    try {
        $service->files->delete($fileId);
    } catch (Exception $e) {
        print "Erro: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fileid'])) {
    $client = getClient();
    deleteFile($client, $_GET['fileid']);
}
