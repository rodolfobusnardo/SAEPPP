<?php
// src/maintenance_check.php

/**
 * Script de verificação de modo de manutenção.
 *
 * Este script deve ser incluído no início de qualquer arquivo "handler"
 * que processe dados (ex: POST requests) para prevenir a escrita de dados
 * no banco de dados enquanto o modo de manutenção estiver ativo para
 * usuários não autorizados.
 *
 * Ele verifica duas condições:
 * 1. O modo de manutenção está ativado no banco de dados.
 * 2. O usuário logado atualmente (se houver) não é um SuperAdmin.
 *
 * Se ambas as condições forem verdadeiras, ele redireciona o usuário para
 * a página de manutenção e encerra a execução do script.
 */

// Garante que os arquivos necessários estejam disponíveis.
// O __DIR__ garante que o caminho funcione corretamente, não importa de onde o script seja incluído.
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

// Inicia a sessão para verificar o status de login do usuário.
start_secure_session();

// A verificação só deve ocorrer se houver uma conexão com o banco de dados.
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT maintenance_mode FROM settings WHERE config_id = 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $settings = $result->fetch_assoc();

            // Condição principal: modo de manutenção ativo E (usuário não está logado OU não é SuperAdmin)
            if ($settings['maintenance_mode'] == 1 && !is_super_admin()) {
                // Redireciona para a página de manutenção.
                // Usamos um caminho absoluto para garantir que funcione de qualquer diretório.
                header('Location: /maintenance.php');
                // Encerra a execução do script para impedir o processamento de dados.
                exit();
            }
        }
        $stmt->close();
    }
}
