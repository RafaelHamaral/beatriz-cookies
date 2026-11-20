<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Editor => 'Editor',
            self::Viewer => 'Leitor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Gerencia usuários, confirma pagamentos e acessa todo o painel.',
            self::Editor => 'Gerencia catálogo, imagens, mensagens, avaliações e atendimento.',
            self::Viewer => 'Consulta o painel, sem alterar dados.',
        };
    }
}
