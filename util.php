<?php

/**
 * Pega credenciais do Google Drive API para autenticação OAuth
 * Altere as informações do arquivo
 * 
 * @return JSONObject Retorno das credenciais em formato de objeto
 */
function getCredentials()
{
    $configFile = file_get_contents('config.json');
    $configJson = json_decode($configFile);

    return $configJson;
}

/**
 * Cria instância de client para autenticar no Google Drive API
 * 
 * @return GoogleClient Cliente do Google
 */
function getClient()
{
    $client = new Google_Client();
    $credentials = getCredentials();

    $client->setClientId($credentials->client_id);
    $client->setClientSecret($credentials->client_secret);
    $client->refreshToken($credentials->refresh_token);

    return $client;
}
