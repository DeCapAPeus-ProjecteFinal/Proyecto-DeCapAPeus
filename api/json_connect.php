<?php

$JSON_URL = $_SERVER['DOCUMENT_ROOT'] . '/../data/users.json';

// Obtener todos los usuarios
function getUsers($userName = null)
{
    global $JSON_URL;
    $data = json_decode(file_get_contents($JSON_URL), true);
    $users = $data['usuaris'] ?? [];

    if ($userName) {
        $users = array_filter($users, fn($u) => $u['nom_usuari'] === $userName);
        $users = array_values($users);
    }

    return $users;
}

// Obtener usuario por ID
function getUser($id)
{
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['id'] == $id) return $user;
    }
    return null;
}

// Añadir usuario
function addUser($userData)
{
    global $JSON_URL;
    $data = json_decode(file_get_contents($JSON_URL), true);
    if (!isset($data['usuaris'])) $data['usuaris'] = [];

    // Asigna un ID automatico
    $userData['id'] = count($data['usuaris']) ? max(array_column($data['usuaris'], 'id')) + 1 : 1;

    // Añade usuario al array existente
    $data['usuaris'][] = $userData;

    // Escribe en el fichero sin eliminar usuarios existentes
    file_put_contents($JSON_URL, json_encode($data, JSON_PRETTY_PRINT));
    return $userData;
}

// Actualizar usuario
function updateUser($id, $newData)
{
    global $JSON_URL;
    $data = json_decode(file_get_contents($JSON_URL), true);
    foreach ($data['usuaris'] as &$user) {
        if ($user['id'] == $id) {
            $user = array_merge($user, $newData);
            break;
        }
    }
    file_put_contents($JSON_URL, json_encode($data, JSON_PRETTY_PRINT));
    return getUser($id);
}
