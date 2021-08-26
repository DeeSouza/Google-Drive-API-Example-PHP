<?php

require __DIR__ . '/vendor/autoload.php';
require 'util.php';

// Pasta principal de upload no Google Drive
define('MAIN_PATH_GOOGLE_DRIVE', '1m7NVIkJVjXazBKPbTypCQb8DceZBjoyL');

/**
 * Faz upload do arquivo selecionado no formulário para uma pasta do servidor local.
 * Após isso, a função uploadFileGoogleDrive é chamada para pegar o arquivo da pasta do servidor
 * e enviar para o Google Drive.
 * 
 * @param GoogleClient $client Cliente do Google
 * @param HtmlFile $formFile Objeto File com as informações do arquivo vindo do formulário
 * @param string $pathName Nome da pasta que será feita o upload no Google Drive (se for vazia, irá salvar na pasta principal)
 */
function uploadFileServer($client, $formFile, $pathName = '')
{
    $fileTemp  = $formFile["tmp_name"];
    $fileName = basename($formFile["name"]);
    $dirUpload = 'uploads';

    if (!is_dir($dirUpload)) mkdir($dirUpload);

    $filePath = $dirUpload . "/" . $fileName;

    // Upload servidor local
    move_uploaded_file($fileTemp, $filePath);

    // Invoca função para fazer upload no Google Drive
    uploadFileGoogleDrive($client, $filePath, $fileName, $pathName);

    // Remove arquivo do servidor
    unlink($filePath);
}

/**
 * Enviar arquivo para o Google Drive.
 * @param GoogleClient $client Cliente do Google
 * @param string $filePath Caminho do arquivo no servidor local
 * @param string $fileName Nome do arquivo no servidor local
 * 
 * @return boolean
 */
function uploadFileGoogleDrive($client, $filePath, $fileName, $pathName)
{
    // ID da pasta que deseja fazer o upload. Se deixar vazio, o arquivo será salvo na raiz do Google Drive.
    $service = new Google_Service_Drive($client);

    // Verifica se existe a pasta no Google Drive
    $checkExistsPath = checkExistsPath($service, $pathName);

    $fileUpload = new Google_Service_Drive_DriveFile();
    $fileUpload->setName($fileName);
    $fileUpload->setParents([ $checkExistsPath->path_id ]);

    // Faz o upload do arquivo no Google Drive
    $uploadDrive = $service->files->create(
        $fileUpload,
        [
            'data' => file_get_contents($filePath),
            'mimeType' => 'application/octet-stream'
        ]
    );

    if (!isset($uploadDrive['id'])) return false;

    // Pega informações do upload para manipular
    $fileMetadata = getFileGoogleDrive($service, $uploadDrive['id']);

    // Vai mostrar na tela as informações do upload para poder salvar num banco de dados, por exemplo
    echo '<pre>';
    print_r($fileMetadata);
    echo '</pre>';

    return true;
}

/**
 * Cria a pasta do cliente no Google Drive
 * 
 * @param GoogleClientService $service Serviço de upload no Google Drive
 * @param string $pathName Nome da pasta no Google Drive
 */
function createPathGoogleDrive($service, $pathName) 
{
    $folder = new Google_Service_Drive_DriveFile();
    $folder->setName($pathName);
    $folder->setMimeType('application/vnd.google-apps.folder');
    $folder->setParents([ MAIN_PATH_GOOGLE_DRIVE ]);

    // Cria uma pasta no Google Drive na pasta principal
    $folderDrive = $service->files->create(
        $folder,
        [
            'mimeType' => 'application/vnd.google-apps.folder'
        ]
    );

    return $folderDrive;
}

/**
 * Verifica se exista a pasta do cliente no Google Drive
 * Se não existir, ela é criada
 * 
 * @param GoogleClientService $service Serviço de upload no Google Drive
 * @param string $pathName Nome da pasta no Google Drive
 */
function checkExistsPath($service, $pathName) {
    $files = $service->files->listFiles();
    $hasPath = false;
    $pathId = '';

    foreach($files->getFiles() as $file) {
        if($file->mimeType === 'application/vnd.google-apps.folder' && $file->name === $pathName) {
            $hasPath = true;
            $pathId = $file->id;
            break;
        }
    }

    if(!$hasPath) {
        $createPath = createPathGoogleDrive($service, $pathName);
        $pathId = $createPath->id;
        $hasPath = true;
    }

    return (object) [
        'path_id' => $pathId
    ];
}

/**
 * Pega informações do arquivo no Google Drive
 * @param GoogleServiceDrive $service Serviço de conexão com o Google Drive
 * @param string ID do arquivo no Google Drive
 * 
 * @return Object Objeto com informações do upload
 */
function getFileGoogleDrive($service, $fileId)
{
    $file = $service->files->get($fileId, array("fields" => "webViewLink, webContentLink, id, name"));

    return (object) [
        'id' => $file->getId(),
        'name' => $file->getName(),
        'view_url' => $file->getWebViewLink(),
        'download_url' => $file->getWebContentLink(),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $client = getClient();
    uploadFileServer($client, $_FILES['file'], 'cliente-2');
}
